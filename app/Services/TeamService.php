<?php
namespace Jihe\Services;

use Jihe\Contracts\Repositories\TeamRequestRepository;
use Jihe\Contracts\Repositories\TeamRepository;
use Jihe\Entities\TeamRequest;
use Jihe\Entities\Team;
use Jihe\Entities\TeamRequirement;
use Jihe\Entities\TeamCertification;
use Jihe\Contracts\Services\Qrcode\QrcodeService;
use Jihe\Dispatches\DispatchesSearchIndexRefresh;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Dispatches\DispatchesMessage;
use Jihe\Utils\SmsTemplate;
use Jihe\Services\StorageService;

class TeamService
{
    use DispatchesJobs, DispatchesSearchIndexRefresh, DispatchesMessage;
    
    /**
     * a team leader is restricted to create at most one team.
     */
    const MAX_ALLOWED_CREATED_TEAMS = 1;

    /**
     * detail of a team can only be updated once
     */
    const MAX_ALLOWED_UPDATED_TIMES = 10;
    
    /**
     * the size of team qrcode generaled
     */
    const TEAM_QRCODE_SIZE = 500;
    
    /**
     * the qrcode logo scale size of generaled
     */
    const TEAM_QRCODE_LOGO_SCALE_SIZE = 100;
    
    /**
     * @var \Jihe\Contracts\Repositories\TeamRequestRepository
     */
    private $teamRequestRepository;
    
    /**
     * @var \Jihe\Contracts\Repositories\TeamRepository
     */
    private $teamRepository;

    /**
     * @var \Jihe\Services\CityService
     */
    private $cityService;
    
    /**
     * @var \Jihe\Services\StorageService
     */
    private $storageService;
    
    /**
     * 
     * @var \Jihe\Contracts\Services\Qrcode\QrcodeService
     */
    private $qrcodeService;
    
    /**
     *
     * @var \Jihe\Services\TeamMemberService
     */
    private $teamMemberService;

    public function __construct(TeamRequestRepository $teamRequestRepository,
                                TeamRepository $teamRepository,
                                CityService $cityService,
                                StorageService $storageService,
                                QrcodeService $qrcodeService,
                                TeamMemberService $teamMemberService)
    {
        $this->teamRequestRepository = $teamRequestRepository;
        $this->teamRepository = $teamRepository;
        $this->cityService = $cityService;
        $this->storageService = $storageService;
        $this->qrcodeService = $qrcodeService;
        $this->teamMemberService = $teamMemberService;
    }

    public function getDefaultLogo()
    {
        return config('storage.alioss')['base_url'] . '/app_icons/team/default_logo.png';
    }
    
    /**
     * Before actually get a team enrolled into the system, a request for that should be issued
     * by a team leader (and thus becomes the creator of that team once the request is approved).
     * This service receives the request for new team creation.
     * 
     * @param array $enrollmentRequest  detail of a request for enrollment, keys taken:
     *                                  - initiator     (entity) who initiates this request
     *                                  - city          (int) the team will be in which city
     *                                  - name          (string) name of the team
     *                                  - logo          (string) url of the team's logo
     *                                  - address       (string) detailed address of the team
     *                                  - contact_phone (string) contact number
     *                                  - contact       (string) name of the contact
     *                                  - contact_hidden (boolean) whether hide contact of team
     *                                  - introduction  (string) brief introduction
     *
     * @throws \Exception         if the enrollment request is rejected immediately
     *
     * @return int                id of the accepted enrollment request
     */
    public function requestForEnrollment(array $enrollmentRequest)
    {
        // morph city from id to instance
        $city = $this->getCity(array_get($enrollmentRequest, 'city'));
        if ($city == null) {
            throw new \Exception('非法城市');
        }
        array_set($enrollmentRequest, 'city', $city);
        
        array_set($enrollmentRequest, 'initiator', $enrollmentRequest['initiator']);

        /* @var $request \Jihe\Entities\TeamRequest */
        $request = $this->morphToRequest($enrollmentRequest);

        // check whether initiator can request for creating a new team
        $this->ensureEnrollmentRequestAcceptable($request->getInitiator()->getId());

        $id = $this->teamRequestRepository->add($request);
        if ($id) {
            // notify request is pending
            $this->notifyOnRequestForEnrollment($request);
        }
        return $id;
    }
    
    private function notifyOnRequestForEnrollment(TeamRequest $request)
    {
        $this->sendToUsers([$request->getInitiator()->getMobile()], [
            'content' => SmsTemplate::generalMessage(SmsTemplate::TEAM_ENROLLMENT_REQUEST_PENDING),
        ], [
            'sms' => true,
        ]);
    }

    private function getCity($city = null)
    {
        if ($city) {
            return $this->cityService->getCity($city);
        }

        return null;
    }
    
    /**
     * Once a team is requested, some update may be required. Update falls into two categories:
     * 1). Updates that can be performed on the fly;
     * 2). Updates that in need of auditing.
     *
     * In the first case, updates will immediately be applied. And for the latter, it's queued
     * for next approving/rejecting. This service provides the ability for the second case.
     *
     * @param array $updateRequest  detail of a request for team update, keys taken:
     *                              - team          mandatory (int) id of the team to be updated
     *                              - initiator     (entity) who initiates this request
     *                              - city          (int) the team will be in which city
     *                              - name          (string) name of the team
     *                              - logo_id       (string) url of the team's logo
     *                              - address       (string) detailed address of the team
     *                              - contact_phone (string) contact number
     *                              - contact       (string) name of the contact
     *                              - contact_hidden (boolean) whether hide contact of team
     *                              - introduction  (string) brief introduction
     * @throws \Exception          if the update request is rejected on the spot
     * @return int                 the id of request
     */
    public function requestForUpdate(array $updateRequest)
    {
        if (array_has($updateRequest, 'city')) { // city id given
            $city = $this->getCity(array_get($updateRequest, 'city'));
            if ($city == null) {
                throw new \Exception('非法城市');
            }
            array_set($updateRequest, 'city', $city);
        }
        
        array_set($updateRequest, 'initiator', array_get($updateRequest, 'initiator'));

        // team must be exists
        $team = $this->teamRepository->findTeam(array_get($updateRequest, 'team'));
        if (null == $team) {
            throw new \Exception('非法社团');
        }
        array_set($updateRequest, 'team', $team);
        
        /* @var $request \Jihe\Entities\TeamRequest */
        $request = $this->morphToRequest($updateRequest);

        // ensure that team can be updated
        $this->ensureUpdateRequestAcceptable($request->getTeam()->getId());

        // persist the update request
        // since we'll save an snapshot of the team to updated as,
        // we first populate the update request from its related team.
        $result = $this->teamRequestRepository->add($this->populateUpdateRequest($request, $team));
        if ($request) {
            $this->notifyOnUpdateRequestPending($request);
        }
        
        return $result;
    }

    /**
     * update some info of team
     *
     * @param array $updateTeam  detail for team update, keys taken:
     *                              - team          mandatory (int) id of the team to be updated
     *                              - contact_phone (string) contact number
     *                              - contact       (string) name of the contact
     *                              - contact_hidden (boolean) whether hide contact of team
     * @return boolean
     */
    public function update(array $updateTeam)
    {
        $team = $this->getUpdatableTeam(array_get($updateTeam, 'team'));
        if (is_null($team)) {
            throw new \Exception('社团不存在');
        }

        $team->setContactPhone(
            array_get($updateTeam, 'contact_phone', $team->getContactPhone()));
        $team->setContact(
            array_get($updateTeam, 'contact', $team->getContact()));
        $team->setContactHidden(
            array_get($updateTeam, 'contact_hidden', $team->getContactHidden()));

        return $this->teamRepository->update($team);
    }
    
    private function notifyOnUpdateRequestPending(TeamRequest $request)
    {
        $this->sendToUsers([$request->getInitiator()->getMobile()], [
            'content' => SmsTemplate::generalMessage(SmsTemplate::TEAM_UPDATE_REQUEST_PENDING),
        ], [
            'sms' => true,
        ]);
    }

    public function canRequestForUpdate(Team $team)
    {
        try {
            $this->ensureUpdateRequestAcceptable($team->getId());
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }
    
    /**
     * approve enrollment request
     *
     * @param int $request         id of enrollment request
     * @param string $memo         memo why approve the enrollment request 
     * @return bool                true if the request is approved. false otherwise
     */
    public function approveEnrollmentRequest($request, $memo = null)
    {
        // the given request should be a pending one
        $request = $this->getPendingRequest($request);
        
        // safety guard: ensure enrollment request can be approved
        $this->ensureEnrollmentRequestApprovable($request->getInitiator()->getId());

        // approve the enrollment request
        if ($this->teamRequestRepository->updatePendingRequestToApproved($request->getId(), $memo)) {
            // enroll the team after approval
            $team = $this->createTeamFromEnrollmentRequest($request);
            
            $teamId = $this->teamRepository->add($team);
            $team->setId($teamId);
            
            // inspect qrcode when team created
            $this->inspectQrcode($team);
            
            $this->teamRepository->update($team);
            
            // insert team creator to team member
            $result = $this->teamMemberService->acceptAsTeamMember($team->getCreator(), $team, []);
            
            //refresh team search index
            if ($result) {
                // refresh team search index && notify team request approved 
                $this->notifyOnEnrollmentRequestApproved($team);
            }
            return !empty($request);
        }

        return false;
    }
    
    private function notifyOnEnrollmentRequestApproved(Team $team)
    {
        $this->dispatchTeamSearchIndexRefresh($team->getId());
        
        $this->sendToUsers([$team->getCreator()->getMobile()], [
            'content' => SmsTemplate::generalMessage(SmsTemplate::TEAM_ENROLLMENT_REQUEST_APPROVED, $team->getName()),
        ], [
            'sms' => true,
        ]);
    }
    
    /**
     * inspect new qrcode url into Team entity
     * 
     * @param Team $team \Jihe\Entities\Team
     */
    private function inspectQrcode(Team $team)
    {
        // general qrcode file
        $qrcode = $this->generateTeamQrcode($team);
        
        // replace qrcode url of team
        if (null != $team->getQrCodeUrl()) {
            $this->removeImage($team->getQrCodeUrl());
        }
        $team->setQrCodeUrl($this->uploadQrcode($qrcode));
    }
    
    /**
     * 
     * @param \Jihe\Entities\Team $team
     * @param array $option       option, keys taken:
     *                             - size
     *                             - logo_scale_size
     * @return array
     */
    public function generateTeamQrcode(Team $team, $option = [])
    {
        $logoUrl = $team->getLogoUrl();
        
        return $this->qrcodeService->generate(
                                        sprintf(url('/wap/team/detail?team_id=%d'), $team->getId()), 
                                        [
                                            'size'              => self::TEAM_QRCODE_SIZE,
                                            'logo'              => $logoUrl,
                                            'logo_format'       => (0 == strcasecmp(
                                                                                    '.png', 
                                                                                    substr($team->getLogoUrl(), -4))) 
                                                                         ? 'png' : 'jpeg',
                                            'logo_scale_width'  => array_get($option, 'size') ?: self::TEAM_QRCODE_LOGO_SCALE_SIZE,
                                            'logo_scale_height' => array_get($option, 'logo_scale_size') ?: self::TEAM_QRCODE_LOGO_SCALE_SIZE,
                                        ]);
    }
    
    /**
     * 
     * @param string $qrcode  content of qrcode
     */
    private function uploadQrcode($qrcode)
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'team_' . uniqid() . time() . '.png';
        file_put_contents($path, $qrcode);
        $ret = $this->storageService->storeAsImage($path);
        @unlink($path);
        return $ret;
    }
    
    /**
     * reject enrollment request
     *
     * @param int $request      id of enrollment request
     * @param string $memo      memo why reject the enrollment request 
     * @return bool             true if enrollment request is rejected. false otherwise
     */
    public function rejectEnrollmentRequest($request, $memo = null)
    {
        // the given request should be a pending one
        $request = $this->getPendingRequest($request);

        $result = $this->teamRequestRepository->updatePendingRequestToRejected($request->getId(), $memo);
        if ($result) {
            $this->notifyOnEnrollmentRequestRejected($request);
        }
        
        return $result;
    }
    
    private function notifyOnEnrollmentRequestRejected(TeamRequest $request)
    {
        $this->sendToUsers([$request->getInitiator()->getMobile()], [
            'content' => SmsTemplate::generalMessage(SmsTemplate::TEAM_ENROLLMENT_REQUEST_REJECTED, $request->getName()),
        ], [
            'sms' => true,
        ]);
    }
    
    /**
     * approve update request
     *
     * @param int $request     id of update request
     * @param string $memo     memo why approve the update request
     * @throws \Exception      if the team to be updated does not exist, or not editable
     * @return bool            true if update request is approved. false otherwise
     */
    public function approveUpdateRequest($request, $memo = null)
    {
        // get the request to be approved, it must be in pending state
        $request = $this->getPendingRequest($request);

        // check update request can be approved
        $this->ensureUpdateRequestApprovable($request->getTeam()->getId());
    
        if ($this->teamRequestRepository->updatePendingRequestToApproved($request->getId(), $memo)) {
            // update team
            $team = $this->getUpdatableTeam($request->getTeam()->getId());
            $oldLogoUrl = $team->getLogoUrl();
            
            // populate updateTime with updateRequest
            $updateTeam = $this->updateTeamWithUpdateRequest($request, $team);
//            $updateTeam->setStatus(null);
            
            if ($this->needReplaced($updateTeam->getLogoUrl(), $oldLogoUrl)) {
                // delete dest image's url and only retain source image url
                if ($oldLogoUrl != $this->getDefaultLogo()) {
                    $this->removeImage($oldLogoUrl);
                }
                
                // re-general qrcode when team logo changed
                $this->inspectQrcode($updateTeam);
            }
            
            $result = $this->teamRepository->update($updateTeam);
            if ($result) {
                // refresh team search index
                // add message
                $this->notifyOnUpdateRequestApproved($team);
            }
            
            return $result;
        }

        return false;
    }
    
    private function notifyOnUpdateRequestApproved(Team $team)
    {
        $this->dispatchTeamSearchIndexRefresh($team->getId());
        
        $this->sendToUsers([$team->getCreator()->getMobile()], [
            'content' => SmsTemplate::generalMessage(SmsTemplate::TEAM_UPDATE_REQUEST_APPROVED, $team->getName()),
        ], [
            'sms' => true,
        ]);
    }
    
    /**
     * check whether the dest image needed to be replaced
     *
     * @param string $sourceImage  new url of image
     * @param string $destImage    be replaced and unusered url of image
     */
    private function needReplaced($sourceImage, $destImage)
    {
        // no image need to deleted
        if (empty($destImage)) {
            return false;
        }
    
        // image not changed, so not need deleted
        if (!empty($sourceImage) && trim($sourceImage) == trim($destImage)) {
            return false;
        }
    
        return true;
    }
    
    /**
     * remove the image on storage
     * 
     * @param String $imageUrl  image url on storage
     */
    private function removeImage($imageUrl)
    {
        $this->storageService->remove($imageUrl);
    }
    
    /**
     * reject update request
     *
     * @param int $request   id of update request
     * @param string $memo   memo why reject the update request
     * @return bool          true if update request is rejected. false otherwise
     */
    public function rejectUpdateRequest($request, $memo = null)
    {
        // get the request to be rejected, it must be in pending state
        $request = $this->getPendingRequest($request);
    
        $result = $this->teamRequestRepository->updatePendingRequestToRejected($request->getId(), $memo);
        if ($result) {
            $this->notifyOnUpdateRequestRejected($request);
        }
        
        return $result;
    }
    
    private function notifyOnUpdateRequestRejected(TeamRequest $request)
    {
        $this->sendToUsers([$request->getInitiator()->getMobile()], [
            'content' => SmsTemplate::generalMessage(SmsTemplate::TEAM_UPDATE_REQUEST_REJECTED, $request->getTeam()->getName()),
        ], [
            'sms' => true,
        ]);
    }
    
    /**
     * get teams by given id of team creator
     * 
     * @param int $creator  id of team creator
     * @return array        array of \Jihe\Entities\Team
     */
    public function getTeamsByCreator($creator)
    {
        return $this->teamRepository->findTeamsCreatedBy($creator, ['city']);
    }
    
    /**
     * get teams
     *
     * @param int $page            the offset page of teams
     * @param int $size            the limit of teams size
     * @param array $criteria|[]   criteria, keys:
     *                               - city|null  (int)city of teams
     *                               - name|null  keywords of team's name used by search
     *                               - tagged     boolean, null(default)
     *                               - freeze     boolean, null(default)
     *                               - forbidden  boolean, null(default)
     * @return array array  of \Jihe\Entities\Team
     */
    public function getTeams($page, $size, array $criteria = [])
    {
        return $this->teamRepository->findTeams($page, $size, [], $criteria);
    }
    
    /**
     * get team info by given id of team
     * 
     * @param int $team  id of team
     * @return \Jihe\Entities\Team
     */
    public function getTeam($team)
    {
        return $this->teamRepository->findTeam($team, ['city', 'creator', 'requirements']);
    }

    /**
     * check whether given team exists
     *
     * @param int $team   id of team
     * @return bool       true if team exists. false otherwise
     */
    public function exists($team)
    {
        return $this->teamRepository->exists($team);
    }
    
    /**
     * get pending enrollment request
     *
     * At present, a leader is restricted to have at most one enrollment request. He/she can
     * issue another request if and only if the last enrollment request is processed (either
     * approved or rejected).
     * 
     * @param int $initiator  id of application for creation
     *
     * @return \Jihe\Entities\TeamRequest|null
     */
    public function getPendingEnrollmentRequest($initiator)
    {
        return $this->teamRequestRepository->findPendingEnrollmentRequest($initiator, ['city']);
    }
    
    /**
     * get pending update request
     *
     * At present, a leader is restricted to have at most one update request. He/she can
     * issue another request if and only if the last update request is processed (either
     * approved or rejected).
     * 
     * @param int $team  id of application for update
     * @return \Jihe\Entities\TeamRequest|null
     */
    public function getPendingUpdateRequest($team)
    {
        return $this->teamRequestRepository->findPendingUpdateRequest($team, ['city', 'team']);
    }
    
    /**
     * get requests that user has uninspected
     *
     * @param int $initiator  id of team initiator
     * @return array \Jihe\Entities\TeamRequest
     */
    public function getUninspectedRequests($initiator)
    {
        return $this->teamRequestRepository->findUninspectedRequests($initiator, ['city']);
    }
    
    /**
     * user inspect request
     *
     * @param int $request  id of team request
     * @return boolean      true if inspect successfully
     */
    public function inspectRequest($request)
    {
        return $this->teamRequestRepository->updateRequestToInspected($request);
    }
    
    /**
     * change requirements of team to given requirements
     * 
     * @param int $team            id of team
     * @param int $joinType        joinType of team
     * @param array $requirements  requestments of team, keys:
     *                               - id          id of requirement
     *                               - requirement content of requirement
     * @throws \Exception
     * @return boolean             true if update successfully, otherwise false
     */
    public function updateTeamRequirements($team, $joinType, array $requirements)
    {
        $team = $this->getTeam($team);
        if (null == $team) {
            throw new \Exception('社团不存在');
        }

        $result = $this->teamRepository->update($team->setJoinType($joinType));
        if (!$result) {
            return false;
        }

        // requirements not needed where
        if (Team::JOIN_TYPE_ANY == $joinType) {
            $requirements = [];
        }
        
        $splitResult = $this->splitRequirements($this->getRequirements($team), $requirements);
        
        $result = $this->teamRepository
                       ->addRequirements($team->getId(), $splitResult['addRequirements'])
                  &&
                  $this->teamRepository
                       ->deleteRequirements($splitResult['deleteIds']);
        
        if ($result) {
            //refresh team search index
            $this->dispatchTeamSearchIndexRefresh($team->getId());
        }
        
        return $result;
    }
    
    /**
     * split requirements to addition and deletion requirements array
     *
     * @param array $previousRequirements    array of previous requirements
     * @param array $requirements            needUpdateArray of requirements
     * @return array                         both array of need addition requirement id
     *                                       and array of need deletion requirement entity
     */
    private function splitRequirements($previousRequirements, array $requirements)
    {
        // get previous requirements of team
        $oldRequirementIds = array_map(function (TeamRequirement $requirement) {
            return $requirement->getId();
        },
        $previousRequirements);
    
        // existed ids of requirements param
        $requirementIds = [];
        // requirements that id not exists
        $additionRequirements = [];

        foreach ($requirements as $requirement) {
            if (null != ($id = array_get($requirement, 'id'))) {
                $requirementIds[] = $id;
                continue;
            }

            $additionRequirements[] = $this->morphToRequirement($requirement);
        }

        return [
            'deleteIds'       => array_values(array_diff($oldRequirementIds, $requirementIds)),
            'addRequirements' => $additionRequirements,
        ];
    }
    
    /**
     * get requirements of team by given team id
     * 
     * @param int $team  id of team
     * @throws \Exception
     */
    public function getTeamRequirements($team)
    {
        $team = $this->getTeam($team);
        if (null == $team) {
            throw new \Exception('社团不存在');
        }
        
        // team join type is anyone
        if ($team->acceptsWorldwideEnrollmentRequest()) {
            return [
                       'joinType'     => Team::JOIN_TYPE_ANY,
                       'requirements' => [],
                   ];
        }
        
        // team join type is need verify
        return [
                   'joinType'     => Team::JOIN_TYPE_VERIFY,
                   'requirements' => $this->getRequirements($team),
               ];
    }
    
    /**
     * 
     * @param \Jihe\Entities\Team $team
     */
    private function getRequirements($team)
    {
        return $this->teamRepository->findRequirements($team->getId());
    }
    
    /**
     * get pending teams for certificationss
     *
     * @param int $page         index of page
     * @param int $size         size of page
     * @return array \Jihe\Entities\Team
     */
    public function getPendingTeamsForCertification($page, $size)
    {
        return $this->teamRepository->findPendingTeamsForCertification($page, $size, ['city']);
    }
    
    /**
     * request and change certifications of team to given certifications
     *
     * @param id $team               id of team
     * @param array $certifications  certifications of team, keys:
     *                                 - id               (int)id of certification
     *                                 - certification_id (string)id of certification 
     * @throws \Exception
     * @return boolean               true if request successfully, otherwise false
     */
    public function requestTeamCertifications($team, array $certifications)
    {
        $team = $this->getTeam($team);
        if (null == $team) {
            throw new \Exception('社团不存在');
        }
        
        $splitResult = $this->splitCertifications($this->getCertifications($team), $certifications);
        
        if ($this->teamRepository
                 ->addCertifications($team->getId(), $splitResult['addCertifications'])
            &&
            $this->teamRepository
                 ->deleteCertifications($splitResult['deleteIds'])) 
        {
            $result = $this->teamRepository->updateTeamToPendingCertification($team->getId());
            if ($result) {
                $this->notifyOnTeamCertificationRequested($team);
            }
            return $result;
        }
    }
    
    private function notifyOnTeamCertificationRequested(Team $team)
    {
        $this->sendToUsers([$team->getCreator()->getMobile()],[
            'content' => SmsTemplate::generalMessage(SmsTemplate::TEAM_CERTIFICATION_REQUEST_PENDING),
        ] ,[
            'sms' => true,
        ]);
    }
    
    private function notifyOnTeamCertificationApproved(Team $team)
    {
        $this->sendToUsers([$team->getCreator()->getMobile()],[
            'content' => SmsTemplate::generalMessage(SmsTemplate::TEAM_CERTIFICATION_REQUEST_APPROVED, $team->getName()),
        ] ,[
            'sms' => true,
        ]);
    }
    
    private function notifyOnTeamCertificationRejected(Team $team)
    {
        $this->sendToUsers([$team->getCreator()->getMobile()],[
            'content' => SmsTemplate::generalMessage(SmsTemplate::TEAM_CERTIFICATION_REQUEST_REJECTED, $team->getName()),
        ] ,[
            'sms' => true,
        ]);
    }
    
    /**
     * split certifications to addition and deletion certifications array
     *
     * @param array $previousCertifications  array of previous certifications
     * @param array $certifications          needUpdateArray of certifications
     * @return array                         both array of need addition certification id
     *                                       and array of need deletion certification entity
     */
    private function splitCertifications($previousCertifications, array $certifications)
    {
        // get previous certifications of team
        $oldCertificationIds = array_map(function (TeamCertification $certification) {
                                            return $certification->getId();
                                        },
                                        $previousCertifications);
    
            // existed ids of certifications param
            $certificationIds = [];
            // certifications that id not exists
            $additionCertifications = [];
    
            foreach ($certifications as $certification) {
                if (null != ($id = array_get($certification, 'id'))) {
                    $certificationIds[] = $id;
                    continue;
                }
    
                $additionCertifications[] = $this->morphToCertification($certification);
            }
    
            return [
                'deleteIds'         => array_values(array_diff($oldCertificationIds, $certificationIds)),
                'addCertifications' => $additionCertifications,
            ];
    }
    
    /**
     * get certifications of team by given team id
     *
     * @param int $team  id of team
     * @throws \Exception
     */
    public function getTeamCertifications($team)
    {
        $team = $this->getTeam($team);
        if (null == $team) {
            throw new \Exception('社团不存在');
        }
    
        return $this->getCertifications($team);
    }
    
    /**
     * 
     * @param \Jihe\Entities\Team $team
     */
    private function getCertifications($team)
    {
        return $this->teamRepository->findCertifications($team->getId());
    }
    
    /**
     * approve team certification
     *
     * @param int $team    id of team
     * @throws \Exception  if the team to be certified does not exist
     * @return bool        true if team certification is approved. false otherwise
     */
    public function approveTeamCertification($team)
    {
        $team = $this->getTeam($team);
        if (null == $team) {
            throw new \Exception('社团不存在');
        }
        
        $result = $this->teamRepository->updateTeamToCertification($team->getId());
        if ($result) {
            //refresh team search index
            $this->dispatchTeamSearchIndexRefresh($team->getId());
            $this->notifyOnTeamCertificationApproved($team);
        }
        
        return $result;
    }
    
    /**
     * reject team certification
     *
     * @param int $team    id of team
     * @throws \Exception  if the team to be certified does not exist
     * @return bool        true if team certification is rejected. false otherwise
     */
    public function rejectTeamCertification($team)
    {
        $team = $this->getTeam($team);
        if (null == $team) {
            throw new \Exception('社团不存在');
        }
        
        $result = $this->teamRepository->updateTeamToUnCertification($team->getId());
        if ($result) {
            //refresh team search index
            $this->dispatchTeamSearchIndexRefresh($team->getId());
            $this->notifyOnTeamCertificationRejected($team);
        }
        
        return $result;
    }
    
    /**
     * check whether pending application for team creation exists 
     * 
     * @param int $initiator  id of the initiator, who is requesting for a team enrollment
     * @return boolean        true if pending enrollment request exists.
     *                        false otherwise
     */
    private function hasPendingEnrollmentRequest($initiator)
    {
        return $this->teamRequestRepository->hasPendingEnrollmentRequest($initiator);
    }
    
    /**
     * check whether pending application for team update exists
     * 
     * @param int $team   id of the team
     * @return boolean    true if pending update requests exists. false otherwise
     */
    private function hasPendingUpdateRequest($team)
    {
        return $this->teamRequestRepository->hasPendingUpdateRequest($team);
    }
    
    /**
     * 
     * @param int $initiator  who initiates the request
     * @throws \Exception
     */
    private function ensureEnrollmentRequestAcceptable($initiator)
    {
        // reject if there's pending enrollment requests
        if ($this->hasPendingEnrollmentRequest($initiator)) {
            throw new \Exception('社团申请正在处理中，请勿重复提交');
        }
        
        // ensure that enrollment request can be approved later
        $this->ensureEnrollmentRequestApprovable($initiator);
    }
    
    /**
     * ensure that enrollment request can be approved later
     * 
     * @param int $initiator   who initiates the request
     * @throws \Exception
     */
    private function ensureEnrollmentRequestApprovable($initiator)
    {
        // a team leader can have at most MAX_ALLOWED_CREATED_TEAMS teams
        $numberOfCreatedTeams = $this->teamRepository->getNumberOfTeamsCreatedBy($initiator);
        if ($numberOfCreatedTeams >= self::MAX_ALLOWED_CREATED_TEAMS) {
            throw new \Exception(sprintf('您已创建了%d个社团,不能创建更多社团', $numberOfCreatedTeams));
        }
    } 
    
    /**
     * 
     * @param int $team     id of the team
     * @throws \Exception
     */
    private function ensureUpdateRequestAcceptable($team)
    {
        // reject if pending update request for the team exists
        if ($this->hasPendingUpdateRequest($team)) {
            throw new \Exception('社团更新申请正在处理中，请勿重复提交');
        }
        
        // ensure that update request can be approved later
        $this->ensureUpdateRequestApprovable($team);
    }
    
    /**
     * ensure that the update request can be approved later
     * 
     * @param int $team     id of the team
     * @throws \Exception
     */
    private function ensureUpdateRequestApprovable($team)
    {
        // team can update at most MAX_ALLOWED_UPDATED_TIMES times
        if ($this->teamRequestRepository->getNumberOfUpdatedTimes($team) >= self::MAX_ALLOWED_UPDATED_TIMES) {
            throw new \Exception('社团资料修改次数已达上限');
        }
    }
    
    /**
     * populate update request with its related team
     * 
     * @param \Jihe\Entities\TeamRequest $request   team update request
     * @param \Jihe\Entities\Team        $team      associated team
     *
     * @throws \Exception
     * @return \Jihe\Entities\TeamRequest
     */
    private function populateUpdateRequest(TeamRequest $request, Team $team)
    {
        $request->setCity($request->getCity() ?: $team->getCity())
                ->setName($request->getName() ?: $team->getName())
                ->setEmail($request->getEmail() ?: $team->getEmail())
                ->setLogoUrl($request->getLogoUrl() ?: $team->getLogoUrl())
                ->setAddress($request->getAddress() ?: $team->getAddress())
                ->setContactPhone($request->getContactPhone() ?: $team->getContactPhone())
                ->setContact($request->getContact() ?: $team->getContact())
                ->setContactHidden($request->getContactHidden() ?: $team->getContactHidden())
                ->setIntroduction($request->getIntroduction() ?: $team->getIntroduction());

        return $request;
    }
    
    /**
     * get pending request by its id
     *
     * @param int $request         id of request
     * @throws \Exception
     * @return \Jihe\Entities\TeamRequest
     */
    private function getPendingRequest($request)
    {
        $request = $this->teamRequestRepository->findRequest($request);
        
        // rule#1. request must exist
        if (null == $request) {
            throw new \Exception('申请不存在');
        }
        
        // rule#2. the request should be in pending state
        if (TeamRequest::STATUS_PENDING != $request->getStatus()) {
            throw new \Exception('申请已处理');
        }
        
        return $request;
    }
    
    /**
     * get pending requests
     *
     * @param int $page         index of page
     * @param int $size         size of page
     * @return array \Jihe\Entities\TeamRequest
     */
    public function getPendingRequests($page, $size)
    {
        return $this->teamRequestRepository->findPendingRequests($page, $size, ['city', 'team']);
    }
    
    /**
     *
     * @param int $team             id of team
     * @throws \Exception
     * @return \Jihe\Entities\Team
     */
    private function getUpdatableTeam($team)
    {
        $team = $this->teamRepository->findTeam($team);
    
        // rule#1. illegal if team is not exists
        if (null == $team) {
            throw new \Exception('社团不存在');
        }
    
        // rule#2. illegal if team is not in NORMAL state
        if (Team::STATUS_NORMAL != $team->getStatus()) {
            throw new \Exception('社团资料不可更新');
        }
    
        return $team;
    }

    /**
     * check whether the team is owned by given creator
     * @param $user
     * @param $team
     *
     * @return bool   true if user can manipulate the team, false otherwise.
     */
    public function canManipulate($user, $team)
    {
        if (null === $team = $this->teamRepository->findTeam($team)) {
            return false; // you cannot manipulate something that does not exist
        }

        // only team's creator can manipulate his/her team
        return $team->getCreator()->getId() == $user;
    }
    
    /**
     * update team's properties
     * 
     * @param int $team
     */
    public function updateProperties($team, array $properities)
    {
        $result = $this->teamRepository->updateProperties($team, $properities);
        if ($result) {
            //refresh team search index
            $this->dispatchTeamSearchIndexRefresh($team);
        }
        
        return $result;
    }

    /**
     *
     * @param int $team           id of team
     * @param array $notices      array of $notices, values taken:
     *                             - activities
     *                             - members
     *                             - news
     *                             - albums
     *                             - notices
     * @return boolean
     */
    public function notify($team, array $notices = [])
    {
        return $this->teamRepository->updateNotifiedAt($team, $notices);
    }
    
    /**
     * 
     * @param \Jihe\Entities\TeamRequest $request
     * @return \jihe\Entities\Team
     */
    private function createTeamFromEnrollmentRequest(TeamRequest $request)
    {
        return (new Team())
               ->setCreator($request->getInitiator())
               ->setCity($request->getCity())
               ->setName($request->getName())
               ->setEmail($request->getEmail())
               ->setLogoUrl($request->getLogoUrl())
               ->setAddress($request->getAddress())
               ->setContactPhone($request->getContactPhone())
               ->setContact($request->getContact())
               ->setContactHidden($request->getContactHidden())
               ->setIntroduction($request->getIntroduction());
    }

    /**
     * 
     * @param TeamRequest $request
     * @param Team $team
     * @return \Jihe\Entities\Team
     */
    private function updateTeamWithUpdateRequest(TeamRequest $request, Team $team)
    {
        return $team->setCity($request->getCity())
                ->setName($request->getName())
                ->setEmail($request->getEmail())
                ->setLogoUrl($request->getLogoUrl())
                ->setAddress($request->getAddress())
                ->setContactPhone($request->getContactPhone())
                ->setContact($request->getContact())
                ->setContactHidden($request->getContactHidden())
                ->setIntroduction($request->getIntroduction());
    }

    /**
     * 
     * @param array $request      params of request
     * @return \Jihe\Entities\TeamRequest
     */
    private function morphToRequest(array $request)
    {
        return (new TeamRequest())
               ->setTeam(array_get($request, 'team'))
               ->setInitiator(array_get($request, 'initiator'))
               ->setCity(array_get($request, 'city'))
               ->setName(array_get($request, 'name'))
               ->setEmail(array_get($request, 'email'))
               ->setLogoUrl(array_get($request, 'logo_id'))
               ->setAddress(array_get($request, 'address'))
               ->setContactPhone(array_get($request, 'contact_phone'))
               ->setContact(array_get($request, 'contact'))
               ->setContactHidden(array_get($request, 'contact_hidden'))
               ->setIntroduction(array_get($request, 'introduction'));
    }
    
    /**
     * 
     * @param array $requirement             params of requirement
     * @return \Jihe\Entities\TeamRequirement
     */
    private function morphToRequirement(array $requirement)
    {
        return (new TeamRequirement())
               ->setRequirement(array_get($requirement, 'requirement'));
    }
    
    /**
     *
     * @param array $certification             params of certification
     * @return \Jihe\Entities\TeamCertification
     */
    private function morphToCertification(array $certification)
    {
        return (new TeamCertification())
                ->setCertificationUrl(array_get($certification, 'certification_id'))
                ->setType(array_get($certification, 'type'));
    }

    /**
     * get teams of given team ids
     *
     * @param array $teams
     */
    public function getTeamsOf(array $teams = [])
    {
        return $this->teamRepository->findTeamsOf($teams);
    }
}
