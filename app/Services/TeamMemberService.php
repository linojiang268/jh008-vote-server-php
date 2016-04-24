<?php
namespace Jihe\Services;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository;
use Jihe\Contracts\Repositories\TeamMemberRepository;
use Jihe\Contracts\Repositories\TeamRepository;
use Jihe\Contracts\Repositories\UserRepository;
use Jihe\Dispatches\DispatchesMessage;
use Jihe\Entities\Message;
use Jihe\Entities\Team;
use Jihe\Entities\TeamGroup;
use Jihe\Entities\TeamMember;
use Jihe\Entities\TeamMemberEnrollmentPermission;
use Jihe\Entities\TeamMemberEnrollmentRequest;
use Jihe\Entities\User;
use Jihe\Services\Excel\ExcelReader;
use Jihe\Services\Excel\ExcelWriter;
use Jihe\Utils\PushTemplate;
use Jihe\Utils\SmsTemplate;

class TeamMemberService
{
    use DispatchesJobs, DispatchesMessage;
    /**
     * @var \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository
     */
    private $requests;

    /**
     * @var \Jihe\Contracts\Repositories\TeamMemberRepository
     */
    private $members;

    /**
     * @var \Jihe\Contracts\Repositories\UserRepository
     */
    private $users;

    /**
     * @var \Jihe\Contracts\Repositories\TeamRepository
     */
    private $teams;

    public function __construct(TeamMemberEnrollmentRequestRepository $requests,
                                TeamMemberRepository $members,
                                UserRepository $users,
                                TeamRepository $teams)
    {
        $this->members  = $members;
        $this->requests = $requests;
        $this->users    = $users;
        $this->teams    = $teams;
    }

    /**
     * a user requests to enroll some team
     *
     * @param User $user            user who requests for enrollment
     * @param Team $team            the team to enroll
     * @param array $requests       enrollment requirements. including:
     *                              - name         (Optional) nick name in the team
     *                              - memo         (Optional) memo
     *                              - requirements (Optional) of array type. contains answer to team requirements,
     *                                             which is keyed by team requirement id and valued by corresponding
     *                                             answer
     *                              - group        (Optional) group of given team, if not given, TeamGroup::UNGROUPED
     *                                             will be used
     *
     * @return array                a 2-element array, where
     *                              [0] is result code, where
     *                                  1 if the request is queued for auditing
     *                                  2 if the request is accepted
     *                              [1] message
     *
     * @throws \Exception
     */
    public function requestForEnrollment(User $user, Team $team, array $requests = [])
    {
        // Check user's requirements against team's requirements
        $requirements = array_get($requests, 'requirements');
        if (!$this->teamRequirementsMet($team->getRequirements(), $requirements)) {
            throw new \Exception('加入条件未填写完整');
        }

        if ($this->enrolled($user->getId(), $team->getId())) {
            throw new \Exception('已是社团成员,请勿重复申请');
        }

        // update nick name of the request initiator with user's nick name if not set
        //!array_get($requests, 'name') && array_set($requests, 'name', $user->getNickName());

        $permission = $this->requests->findPermission($user->getMobile(), $team->getId());
        if (!$permission) { // no permission set
            if ($team->acceptsWorldwideEnrollmentRequest()) {
                $this->acceptAsTeamMember($user, $team, $requests);

                return [2, '您已经成为社团成员'];
            } else {
                $this->pendAsEnrollmentRequest($user, $team, $requests);

                return [1, '您的申请已接受，请等待审核'];
            }
        }

        // check whether the user is in the blacklist
        if ($permission->prohibited()) { // the permission says no
            throw new \Exception('您的申请未通过');
        }

        // if there's no memo, copy from permission
        !array_get($requests, 'memo') && array_set($requests, 'memo', $permission->getMemo());
        // if there's still no name, copy from permission
        !array_get($requests, 'name') && array_set($requests, 'name', $permission->getName());

        // the user is in the whitelist -- add as team member and copy memo if its not set
        $this->acceptAsTeamMember($user, $team, $requests);
        return [2, '您已经成为社团成员'];
    }

    // check user's filled requirements($filledRequirements) against
    // team's requirements($expectedRequirements). If team's requirements cannot be met,
    // false will be returned.
    private function teamRequirementsMet(array $expectedRequirements = null, array $filledRequirements = null)
    {
        if (empty($expectedRequirements)) { // no requirements expected
            return true;                    // everything will pass through
        }

        if (empty($filledRequirements)) {  // requirements not filled
            return false;
        }

        // at present, each requirement is mandatory, so
        // the number of expected and filled requirements should equal
        if (count($expectedRequirements) != count($filledRequirements)) {
            return false;
        }

        foreach ($expectedRequirements as $expectedRequirement) {
            /* @var $expectedRequirement \Jihe\Entities\TeamRequirement */
            // requirement is supposed to be filled with non-blank text
            if (!array_key_exists($expectedRequirement->getId(), $filledRequirements) ||
                empty($filledRequirements[$expectedRequirement->getId()])) {
                return false;
            }
        }

        return true;
    }

    /**
     * check whether given user is enrolled in given team
     *
     * @param int $user          id of the user
     * @param int|array $team    id or ids of team
     *
     * @return bool|array        return boolean when given int, 
     *                           true if the user is enrolled in the team, 
     *                           false otherwise;
     *                           return array when given array,
     *                           each of array is team_id with boolean
     */
    public function enrolled($user, $team)
    {
        // return array when given array of team id
        if (is_array($team)) {
            return $this->members->exists($user, $team);
        }

        // return boolean when 
        $teams = $this->members->exists($user, [$team]);
        return $teams[$team];
    }

    // enroll the user as team member
    public function acceptAsTeamMember(User $user, Team $team, array $requests, $notify = false)
    {
        // sane team group
        !array_get($requests, 'group') && array_set($requests, 'group', TeamGroup::UNGROUPED);

        $id = $this->members->add($user->getId(), $team->getId(), $requests);

        // nofity team when member joined
        $this->teams->updateNotifiedAt($team->getId(), ['members']);

        // notify team member's acceptance
        // TODO: tightly coupling is not recommended here
        $notify && $this->notifyOnEnrollmentRequestApproved($user, $team);
        return $id;
    }

    private function notifyOnEnrollmentRequestApproved(User $user, Team $team)
    {
        $this->sendToTeamMembers($team, [$user->getMobile()], [
            'content' => SmsTemplate::generalMessage(SmsTemplate::TEAM_MEMBER_ENROLLMENT_REQUEST_APPROVED, $team->getName())
        ], [
            'sms' => true
        ]);
    }

    // pend the request, and wait for auditing later
    private function pendAsEnrollmentRequest(User $user, Team $team, array $requests)
    {
        // check whether the request is in pending queue or not
        if ($this->hasPendingEnrollmentRequest($user, $team)) {
            return true;
        }

        // clean any pre-existing request, or we can move them to history if we need
        // track user's requests
        $this->requests->removeFinishedRequestsOf($user->getId());

        // sane team group
        !array_get($requests, 'group') && array_set($requests, 'group', TeamGroup::UNGROUPED);
        return $this->requests->add($user->getId(), $team->getId(), $requests);
    }

    /**
     * find team member
     * @param $user
     * @param $team
     * @return \Jihe\Entities\TeamMember|null
     */
    public function getTeamMember($user, $team)
    {
        return $this->members->findTeamMember($user, $team);
    }

    /**
     * get enrollment request by its id
     *
     * @param int $request     id of the request
     * @param Team $team
     * @return TeamMemberEnrollmentRequest|null
     */
    public function getEnrollmentRequest($request, Team $team)
    {
        return $this->requests->findRequest($request, $team->getId());
    }

    /**
     * get pending enrollment request by its id
     *
     * @param int|array $request     id of the request
     * @param Team $team
     * @return TeamMemberEnrollmentRequest|null
     */
    public function getPendingEnrollmentRequest($request, Team $team)
    {
        $request = $this->requests->findPendingRequest($request, $team->getId());
        if ($request != null) {
            if (is_array($request)) {
                foreach ($request as $req) {
                    $req->setTeam($team);
                }
            } else {
                $request->setTeam($team);
            }
        }

        return $request;
    }

    /**
     * get rejected enrollment request by its id
     *
     * @param array|int $request   id of the request
     * @param Team $team
     * @return TeamMemberEnrollmentRequest|array|null
     */
    public function getRejectedEnrollmentRequest($request, Team $team)
    {
        return $this->requests->findRejectedRequest($request, $team->getId());
    }

    public function updateEnrollmentRequest($request, array $updates)
    {
        $updates = array_filter($updates, function ($update) {
            return !is_null($update);  // only eliminate null values
        });
        return $this->requests->update($request, $updates);
    }

    /**
     * reject enrollment request
     *
     * @param TeamMemberEnrollmentRequest $request     request to update
     * @param string $reason                           the reason why the request is rejected
     *
     * @return bool                                    true on success, false otherwise
     */
    public function rejectEnrollmentRequest(TeamMemberEnrollmentRequest $request, $reason = '')
    {
        // can only reject pending enrollment
        if ($request->getStatus() == TeamMemberEnrollmentRequest::STATUS_PENDING) {
            $success = $this->requests->updateStatusToRejected($request->getId(), $reason);

            $this->notifyOnEnrollmentRequestRejected($request);
            return $success;
        }

        return false;
    }

    /**
     * reject enrollment request
     *
     * @param array  $requests              array of TeamMemberEnrollmentRequest to update
     * @param string $reason                the reason why the request is rejected
     *
     * @return bool                         true on success, false otherwise
     */
    public function rejectEnrollmentRequests(array $requests, $reason = '')
    {
        $requestIds = array_map(function (TeamMemberEnrollmentRequest $request) {
            return $request->getId();
        }, $requests);

        $success = $this->requests->updateStatusToRejected($requestIds, $reason);

        $this->notifyOnEnrollmentRequestsRejected($requests);
        return $success;

        return false;
    }

    private function notifyOnEnrollmentRequestRejected(TeamMemberEnrollmentRequest $request)
    {
        $team = $request->getTeam();
        $this->sendToUsers([$request->getInitiator()->getMobile()], [
            'content' => SmsTemplate::generalMessage(SmsTemplate::TEAM_MEMBER_ENROLLMENT_REQUEST_REJECTED,
                                                     $team->getName(), $team->getContactPhone())
        ], [
            'sms' => true
        ]);
    }

    private function notifyOnEnrollmentRequestsRejected(array $requests)
    {
        foreach ($requests as $request) {
            $this->notifyOnEnrollmentRequestRejected($request);
        }
    }

    /**
     * approve enrollment request
     *
     * @param TeamMemberEnrollmentRequest $request
     *
     * @throws \Exception    team's requirements cannot be met
     * @return bool
     */
    public function approveEnrollmentRequest(TeamMemberEnrollmentRequest $request)
    {
        if (!$this->teamRequirementsMet($request->getTeam()->getRequirements(),
                                        $request->getRequirements())) {
            throw new \Exception('加入条件未填写完整,同意加入社团失败');
        }

        // can only approve pending request
        if ($request->getStatus() != TeamMemberEnrollmentRequest::STATUS_PENDING) {
            return false;
        }

        if ($this->requests->updateStatusToApproved($request->getId())) {
            return $this->acceptAsTeamMember($request->getInitiator(), $request->getTeam(), [
                'name'  => $request->getName(),
                'memo'  => $request->getMemo(),
                'group' => $request->getGroup() ? $request->getGroup()->getId() : TeamGroup::UNGROUPED,
                'requirements' => $request->getRequirements() ?: []
            ], true);
        }

        return false;
    }

    public function hasPendingEnrollmentRequest(User $user, Team $team)
    {
        return $this->requests->pendingRequestExists($user->getId(), $team->getId());
    }

    /**
     * stat team's pending enrollment requests
     *
     * @param array $teams  team ids
     *
     * @return array
     */
    public function statPendingEnrollmentRequests(array $teams = null)
    {
        return $this->requests->statPendingEnrollmentRequests($teams);
    }

    /**
     * list all pending enrollment requests for a team
     *
     * @param int $team    id of the team
     * @param int $page    page number
     * @param int $size    page size
     *
     * @return array    [0] total pages
     *                  [1]array of pending enrollment requests
     */
    public function getPendingEnrollmentRequestsOfTeam($team, $page, $size)
    {
        return $this->requests->findPendingRequestsForTeam($team, $page, $size);
    }

    /**
     * list all rejected enrollment requests for a team
     *
     * @param int $team    id of the team
     * @param int $page    page number
     * @param int $size    page size
     *
     * @return array    [0] total pages
     *                  [1] array of pending enrollment requests
     */
    public function getRejectedEnrollmentRequestsOfTeam($team, $page, $size)
    {
        return $this->requests->findRejectedRequestsForTeam($team, $page, $size);
    }

    /**
     * list all pending enrollment requests for an initiator
     *
     * @param int $initiator   id of the initiator
     *
     * @return array  array of pending enrollment requests
     */
    public function getPendingEnrollmentRequestsOfInitiator($initiator)
    {
        return $this->requests->findPendingRequestsForInitiator($initiator);
    }

    /**
     * change individual team members' group
     *
     * @param int $team        team id
     * @param array $members   member ids
     * @param int $toGroup     to-group id
     */
    public function changeMemberGroup($team, array $members, $toGroup)
    {
        $this->members->updateGroup($team, $members, $toGroup);
    }

    /**
     * change team members's group(in a same group) to another group
     *
     * @param int $team        team id
     * @param int $fromGroup   from-group id
     * @param int $toGroup     to-group id
     */
    public function changeGroupOfGroupedMembers($team, $fromGroup, $toGroup)
    {
        $this->members->updateGroupOfGroupedMembers($team, $fromGroup, $toGroup);
    }

    /**
     * a team member quits one of his/her teams
     *
     * @param int $team      team id
     * @param int $member    member's id
     *
     * @throws \Exception
     */
    public function quitTeam($team, $member)
    {
        if (!$this->enrolled($member, $team)) {
            throw new \Exception('非社团成员,退出失败');
        }

        $this->members->delete($team, $member);
    }
    
    /**
     * find teams that adding the user in their whitelist
     * 
     * @param int $user       user id
     * @param string $mobile  user mobile
     */
    public function findTeamsWhitelistedUser($user, $mobile)
    {
        $teams = $this->requests->findTeamsWhitelistedUser($mobile);
        if (empty($teams)) {
            return [];
        }
        
        // exclude those teams that the user already enrolled in
        $enrolledTeams = $this->members->listEnrolledTeams($user);
        if (empty($enrolledTeams)) {
            return $teams;
        }
        
        foreach ($enrolledTeams as $enrolledTeam) {
            if (isset($teams[$enrolledTeam])) {
                unset($teams[$enrolledTeam]);
            }
        }
        
        return $teams;
    }

    /**
     * find request permission for enrollment
     *
     * @param string $mobile     user's mobile
     * @param int $team          team id
     * @return TeamMemberEnrollmentPermission|null
     */
    public function findEnrollmentPermission($mobile, $team)
    {
        return $this->requests->findPermission($mobile, $team);
    }

    /**
     * list members in given team
     *
     * @param int $team        team id
     * @param int $page        page number
     * @param int $size        page size
     * @param array $criteria  - group       group id
     *                         - keyword     name or mobile#
     *                         - visibility  TeamMember::VISIBILITY_ALL or TeamMember::VISIBILITY_TEAM or null
     * @return mixed
     */
    public function listMembers($team, $page, $size, array $criteria = [])
    {
        // determine what the keyword is
        if ($keyword = array_get($criteria, 'keyword')) {
            $keyword = trim($keyword);
            if (!empty($keyword)) {
                // keyword can be either name or mobile#
                if (preg_match('/^1\d{10}$/', $keyword)) {  // it's a mobile#
                    $criteria['mobile'] = $keyword;
                } else { // treat it as name
                    $criteria['name'] = $keyword;
                }
            }

            unset($criteria['keyword']);
        }

        // if there's no visibility restriction, remove it
        if (null === array_get($criteria, 'visibility')) {
            unset($criteria['visibility']);
        }

        return $this->members->listMembers($team, $page, $size, $criteria);
    }
    
    /**
     * list enrolled team ids of given user
     *
     * @param int $user        user id
     * @param int $page
     * @param int $size
     * @param array $option  option, keys taken:
     *                        - only_id  true(default)
     *                        - paging   false(default)
     * @return array
     */
    public function listEnrolledTeams($user, $page = null, $size = null, array $option = [])
    {
        return $this->members->listEnrolledTeams($user, $page, $size, $option);
    }

    /**
     * export members in given team as excel
     *
     * @param int $team        team id
     * @param array $criteria  - group       group id
     *                         - keyword     name or mobile#
     *                         - visibility  TeamMember::VISIBILITY_ALL or TeamMember::VISIBILITY_TEAM or null
     * @return mixed
     */
    public function exportMembers($team, array $criteria = [])
    {
        $writer = ExcelWriter::fromScratch();
        $page = $pages = 1;
        do {
            list($pages, $members) = $this->listMembers($team, $page, 500, $criteria);
            $writer->write($this->morphMembers($members));
        } while ($page < $pages);

        $writer->save();
    }

    private function morphMembers(array $members)
    {
        return array_map(function (TeamMember $member) {
            return [
                $member->getUser()->getMobile(),
                $member->getUser()->getNickName(),
                $member->getEntryTime(),
                //$member->getGroup() ? $member->getGroup()->getName() : '未分组',
            ];
        }, $members);
    }

    /**
     * update member's basic information
     *
     * @param int $member
     * @param int $team
     * @param array $updates       delta updates
     * @return bool
     * @throws \Exception
     */
    public function update($member, $team, array $updates)
    {
        if (!$this->enrolled($member, $team)) {
            throw new \Exception('非社团成员,修改失败');
        }

        $updates = array_filter($updates, function ($update) {
            return !is_null($update);  // only eliminate null values
        });
        return $this->members->update($member, $team, $updates);
    }

    /**
     * add a new enrollment permission
     *
     * @param array $permission     detail of an enrollment permission, fields are:
     *                              - mobile    (mandatory) mobile
     *                              - team      (mandatory) team id
     *                              - name      (optional)  name in the team
     *                              - memo      (optional)  memo
     *                              - status    (optional) TeamMemberEnrollmentPermission::STATUS_PERMITTED or
     *                                                     TeamMemberEnrollmentPermission::STATUS_PROHIBITED
     * @param Team $team
     * @param boolean   $notify     need to notify
     * @return int                  id of the permission
     */
    public function addEnrollmentPermission(array $permission, Team $team, $notify = false)
    {
        // make sure that the status given is valid if it is provided
        if (array_has($permission, 'status')) {
            if (!TeamMemberEnrollmentPermission::isValidStatus(array_get($permission, 'status'))) {
                throw new \InvalidArgumentException('invalid permission status');
            }
        } else {
            $permission['status'] = TeamMemberEnrollmentPermission::STATUS_PERMITTED;
        }

        $result = $this->requests->addPermission($permission);
        if ($notify && $result && $permission['status'] == TeamMemberEnrollmentPermission::STATUS_PERMITTED) {
            $this->notifyOnImportedIntoWhitelist([$permission['mobile']], $team);
        }

        return $result;
    }

    /**
     * update enrollment permission
     *
     * @param int $permission  permission id
     * @param array $updates   things to update, keys taken:
     *                         - memo
     *                           memo to the user(denoted by his/her mobile)
     *                         - status
     *                           should be one of TeamMemberRequestPermission::STATUS_PERMITTED
     *                           or STATUS_PROHIBITED
     *
     * @return bool           true if updated successfully. false otherwise.
     */
    public function updateEnrollmentPermission($permission, array $updates)
    {
        return $this->requests->updatePermission($permission, $updates);
    }

    /**
     * delete given permission
     *
     * @param int $permission  permission's id
     *
     * @return bool            true if the deleted. false otherwise
     */
    public function deleteEnrollmentPermission($permission)
    {
        return $this->requests->deletePermission($permission);
    }

    /**
     * import enrollment permissions from Excel
     *
     * @param string $excel         excel file's path
     * @param Team $team            team
     * @param array $options        accepts all options defined in ExcelReader::read()
     *                              and extended options are:
     *                              - on_error_stop    (bool) when false(default), the importing
     *                                                 won't stop when encountering an error
     *
     * @param ExcelReader $reader
     *
     * @see \Jihe\Services\Excel\ExcelReader::read()
     * @return array                failed rows
     */
    public function importEnrollmentPermissions($excel, Team $team, array $options = [], ExcelReader $reader = null)
    {
        $failedRows = [];  // rows failed to import
        $reader = $reader ?: new ExcelReader();
        // merge options with default value
        $options = array_merge([
            'from_row' => 2,       // skip the first row, which is a header
            'on_error_stop' => false,
            'batch_size'  => 20,
        ], $options);

        $stopOnError = array_get($options, 'on_error_stop');
        $reader->read($excel, function ($data, $row) use(&$failedRows, $stopOnError, $team) {
            if (!is_array(current($data))) { // make it array of rows if it's not
                $data = [$data];
            }

            if (!$this->doImportEnrollmentPermissions($data, $row, $failedRows, $stopOnError, $team)) {
                return $stopOnError;
            }
        }, $options);

        return $failedRows;
    }

    private function doImportEnrollmentPermissions(array $data, $row, &$failedRows, $stopOnError, Team $team)
    {
        $dataToProcess = [];
        foreach ($data as $index => $rowData) {
            unset($data[$index]);
            list($mobile, $name, $memo, $status) = array_pad($rowData, 4, null);
            $status = $this->saneImportedEnrollmentStatus($status);
            $mobile = strval($mobile);  // $mobile may be read out as floating number

            // check row
            if (!preg_match('/^1\d{10}$/', $mobile)) {
                $failedRows[$row++] = [ implode("\t", $rowData), '手机号错误' ];
                if ($stopOnError) {
                    break;
                }
                continue;
            }

            $dataToProcess[$mobile] = [
                'mobile' => $mobile,
                'team'   => $team->getId(),
                'name'   => $name,
                'memo'   => $memo,
                'status' => $status,
            ];
        }

        if (empty($dataToProcess)) { // no data to process, all wrong and recorded
            return false;
        }

        $permissions = $this->findEnrollmentPermission(array_keys($dataToProcess), $team->getId());
        if (!empty($permissions)) { // process existing permissions
            foreach ($permissions as $mobile => $permission) {
                $row = $dataToProcess[$mobile];
                unset($dataToProcess[$mobile]);

                if ($permission->getMemo() == $row['memo'] && $permission->getStatus() == $row['status']) {
                    // nothing changed, skip it
                    continue;
                } else {
                    $this->updateEnrollmentPermission($permission->getId(), [
                        'memo'   => $row['memo'],
                        'name'   => $row['name'],
                        'status' => $row['status'],
                    ]);
                }
            }
        }

        if (!empty($dataToProcess)) { // process new permissions
            foreach ($dataToProcess as $row) { // todo: batch insert
                $this->addEnrollmentPermission([
                    'mobile' => $row['mobile'],
                    'team'   => $row['team'],
                    'name'   => $row['name'],
                    'memo'   => $row['memo'],
                    'status' => $row['status'],
                ], $team, false);
            }

            $mobiles = array_keys($dataToProcess); // we only have new users notified next
            $this->notifyOnImportedIntoWhitelist($mobiles, $team);
        }


        return true;
    }

    private function notifyOnImportedIntoWhitelist($mobiles, Team $team)
    {
        $toPush = [];
        $toSms  = [];

        $mappings = $this->users->findIdsByMobiles($mobiles);
        foreach ($mappings as $mobile => $userId) {
             if (!is_null($userId)) { // using push
                $toPush[] = $mobile;
             } else { // not registered, sending sms
                $toSms[] = $mobile;
             }

            unset($mappings[$mobile]);
        }

        if (!empty($toPush)) { // push message
            $this->sendToUsers($toPush, [
                'title'   => $team->getName(),
                'content' => PushTemplate::generalMessage(PushTemplate::IMPORTED_INTO_WHITE_LIST, $team->getContact(), $team->getName()),
                'type'    => Message::TYPE_TEAM,
                'attributes' => [
                    'team_id' => $team->getId(),
                ]
            ], [
                'push' => true,
                'record' => true,
                'record_attributes' => ['team' => $team->getId()],
            ]);
            
            $this->sendToUsers($toPush, [
                    'content' => SmsTemplate::generalMessage(SmsTemplate::IMPORTED_INTO_WHITE_LIST, $team->getName(), $team->getContact())
            ], [
                    'sms' => true
            ]);
        }

        if (!empty($toSms)) { // send sms
            $this->sendToUsers($toSms, [
                'content' => SmsTemplate::generalMessage(SmsTemplate::IMPORTED_INTO_WHITE_LIST, $team->getName(), $team->getContact())
            ], [
                'sms' => true
            ]);
        }
    }

    // sane enrollment status from imported excel file
    // missing or text without '禁用' is treated as permitted. And the
    // reset will be treated as prohibited
    private function saneImportedEnrollmentStatus($status)
    {
        if (!$status || false === strpos($status, '禁用')) {
            return TeamMemberEnrollmentPermission::STATUS_PERMITTED;
        }

        return TeamMemberEnrollmentPermission::STATUS_PROHIBITED;
    }

    /**
     * blacklist someone so that he/she won't be able to request for specific
     * team's enrollment
     *
     * @param TeamMemberEnrollmentRequest $request
     * @param Team $team
     * @param string $memo
     *
     * @return bool          true on success, false otherwise
     */
    public function blacklistEnrollmentRequest(TeamMemberEnrollmentRequest $request, Team $team, $name = '', $memo = null)
    {
        // mark the request as Rejected
        if (!$this->requests->updateStatusToRejected($request->getId(), '拉黑')) {
            return false;
        }

        // send out notification
        $this->notifyOnEnrollmentRequestBlackListed($request, $team);

        $mobile = $request->getInitiator()->getMobile();
        $permission = $this->requests->findPermission($mobile, $team->getId());
        if (!$permission) { // permission not set before, add one
            $this->requests->addPermission([
                'mobile'  => $mobile,
                'team'    => $team->getId(),
                'name'    => $name,
                'memo'    => $memo,
                'status'  => TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
            ]);
            // addPermission will have the newly added permission id returned back,
            // which is boring to receive. here we simply assume its success and return true.
            return true;
        }

        // permission exists and we'll update it
        return $this->requests->updatePermission($permission->getId(), [
            'status' => TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
            'memo'   => $memo ?: $permission->getMemo(),
        ]);
    }

    private function notifyOnEnrollmentRequestBlackListed(TeamMemberEnrollmentRequest $request, Team $team)
    {
        $this->sendToUsers([$request->getInitiator()->getMobile()], [
            'content' => SmsTemplate::generalMessage(SmsTemplate::TEAM_MEMBER_ENROLLMENT_REQUEST_BE_BLACKLISTING,
                                                     $team->getName())
        ], [
            'sms' => true
        ]);
    }

    /**
     * the opposite behavior of blacklisting. whitelist someone from blacklist
     * so that he/she will be able to request for specific team's enrollment sometimes later
     *
     * @param string $mobile
     * @param Team $team
     *
     * @return bool          true on success, false otherwise
     */
    public function whiteBlacklistedEnrollmentRequest($mobile, Team $team)
    {
        $permission = $this->requests->findPermission($mobile, $team->getId());
        if (!$permission) { // permission not set before
            // do nothing, since it's not in the black list
            return true;
        }

        if ($permission->prohibited()) { // it's indeed in the blacklist
            // release the poor guy
            return $this->requests->deletePermission($permission->getId());
//            return $this->requests->updatePermission($permission->getId(), [
//                'status' => TeamMemberEnrollmentPermission::STATUS_PERMITTED,
//                'memo'   => $memo ?: $permission->getMemo(),
//            ]);
        }

        // it's not blacklisted, do nothing
        return true;
    }

    /**
     * list whitelist for a team
     *
     * @param int $team    id of the team
     * @param int $page    page number
     * @param int $size    page size
     *
     * @return array       [0] total pages
     *                     [1] whitelist in given page
     */
    public function getPermittedEnrollmentPermissionsFor($team, $page, $size)
    {
        return $this->requests->findPermittedPermissionsFor($team);
    }

    /**
     * list black for a team
     *
     * @param int $team    id of the team
     * @param int $page    page number
     * @param int $size    page size
     *
     * @return array     [0] total pages
     *                   [1] blacklist in given page
     */
    public function getProhibitedEnrollmentPermissionsFor($team, $page, $size)
    {
        return $this->requests->findProhibitedPermissionsFor($team, $page, $size);
    }
    
    /**
     * count total number of team members
     * 
     * @param int|array $team   team id or array of team id
     * @return int
     */
    public function countMembers($team) 
    {
        return $this->members->countMembers($team);
    }
    
    /**
     * list teams given user requested
     * 
     * @param int $user
     * @param array $options   options, keys taken:
     *                           - only_id false(default)
     */
    public function listRequestedTeams($user, array $options = [])
    {
        return $this->requests->findPendingRequestedTeams($user, $options);
    }
}
