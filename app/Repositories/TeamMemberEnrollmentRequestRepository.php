<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository as TeamMemberEnrollmentRequestRepositoryContract;
use Jihe\Models\Team;
use Jihe\Models\TeamMemberEnrollmentPermission;
use Jihe\Models\TeamMemberEnrollmentRequirement;
use Jihe\Entities\TeamMemberEnrollmentRequest as TeamMemberEnrollmentRequestEntity;
use Jihe\Entities\TeamGroup as TeamGroupEntity;
use Jihe\Models\TeamMemberEnrollmentRequest;
use Jihe\Entities\Team as TeamEntity;

class TeamMemberEnrollmentRequestRepository implements TeamMemberEnrollmentRequestRepositoryContract
{
    /**
     * @see \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::add()
     */
    public function add($user, $team, array $requests = null)
    {
        $requests = $requests ?: [];
        $requests['initiator_id'] = $user;
        $requests['team_id'] = $team;
        $requests['group_id'] = array_get($requests, 'group', TeamGroupEntity::UNGROUPED);
        $requests['status'] = TeamMemberEnrollmentRequestEntity::STATUS_PENDING;

        $requirements = array_get($requests, 'requirements');
        array_forget($requests, ['group', 'requirements']);

        // save enrollment request
        $request = TeamMemberEnrollmentRequest::create($requests);
        // save requirements if needed
        if (!empty($requirements)) {
            $request->requirements()->saveMany($this->morphRequirements($user, $requirements));
        }

        return $request->id;
    }

    // morph key-value paired requirements to TeamMemberEnrollmentRequirement instance
    private function morphRequirements($user, array $requirements = [])
    {
        $instances = [];
        foreach ($requirements as $requirement => $answer) {
            // $requirement corresponds the identifier of the question/requirement
            // which is required by the team leader as a requirement to enroll its members
            array_push($instances, new TeamMemberEnrollmentRequirement([
                'user_id'        => $user,
                'requirement_id' => $requirement,
                'value'          => $answer,
            ]));
        }

        return $instances;
    }

    /**
     * @see \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::removeFinishedRequestsOf()
     */
    public function removeFinishedRequestsOf($initiator)
    {
        $requests = array_map(function (TeamMemberEnrollmentRequest $request) {
            return $request->id;
        }, TeamMemberEnrollmentRequest::where('initiator_id', $initiator)
            ->where('status', '<>', TeamMemberEnrollmentRequestEntity::STATUS_PENDING)
            ->get(['id'])->all());

        if (empty($requests)) {
            return;
        }

        // remove requirements
        TeamMemberEnrollmentRequirement::whereIn('request_id', $requests)->delete();

        // remove finished requests
        TeamMemberEnrollmentRequest::where('initiator_id', $initiator)
             ->where('status', '<>', TeamMemberEnrollmentRequestEntity::STATUS_PENDING)
             ->delete();
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::update()
     */
    public function update($request, array $updates)
    {
        return 1 == TeamMemberEnrollmentRequest::where('id', $request)->update($updates);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::findPendingRequest()
     */
    public function findPendingRequest($request, $team)
    {
        return $this->findRequestWithStatus($request, $team, TeamMemberEnrollmentRequestEntity::STATUS_PENDING);
    }

    public function findRejectedRequest($request, $team)
    {
        return $this->findRequestWithStatus($request, $team, TeamMemberEnrollmentRequestEntity::STATUS_REJECTED);
    }

    private function findRequestWithStatus($request, $team, $status)
    {
        $query = TeamMemberEnrollmentRequest::with('initiator')
                                            ->where('status', $status)
                                            ->where('team_id', $team);
        if (is_array($request)) {
            return array_map(function ($request) {
                return $request->toEntity();
            }, $query->whereIn('id', $request)->get()->all());
        }

        $request = $query->where('id', $request)
                         ->first();

        return $request ? $request->toEntity() : null;
    }


    public function findRequest($request, $team)
    {
        $request = TeamMemberEnrollmentRequest::with('initiator')->where('id', $request)
            ->where('team_id', $team)
            ->first();

        return $request ? $request->toEntity() : null;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::pendingRequestExists()
     */
    public function pendingRequestExists($user, $team)
    {
        return null != TeamMemberEnrollmentRequest::where('team_id', $team)
            ->where('status', TeamMemberEnrollmentRequestEntity::STATUS_PENDING)
            ->where('initiator_id', $user)
            ->value('id');
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::statPendingEnrollmentRequests()
     */
    public function statPendingEnrollmentRequests(array $teams = null)
    {
        $pStats = \DB::table('team_member_enrollment_requests')
                    ->select('team_id', \DB::raw('SUM(1) as pending_requests'))
                    ->where('status', TeamMemberEnrollmentRequestEntity::STATUS_PENDING)
                    ->groupBy('team_id');
        if (!empty($teams)) {
            if (!is_array($teams)) { // morph it as array
                $teams = [$teams];
            }
            $pStats->whereIn('team_id', $teams);
        }

        $pStats = $pStats->get();
        if (empty($pStats)) {
            return $pStats;
        }

        $stats = [];
        foreach ($pStats as $index => $stat) {
            $stats[$stat->team_id] = [
                'pending_requests' => $stat->pending_requests,
            ];
            unset($pStats[$index]);
        }

        if (!empty($stats)) {
            $teams = Team::with('creator')
                         ->whereIn('id', array_keys($stats))
                         ->where('status', TeamEntity::STATUS_NORMAL)
                         ->get(['id', 'name', 'contact_phone', 'creator_id']);
            foreach ($teams as $team) {
                $stats[$team->id]['team'] = [
                    'id' => $team->id,
                    'name' => $team->name,
                    'contactPhone' => $team->contact_phone,
                    'mobile' => $team->creator->mobile,
                ];
            }
        }

        return array_filter($stats, function ($stat) {
            return isset($stat['team']);
        });
    }

    /**
     * @see \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::findPendingRequestsForInitiator()
     */
    public function findPendingRequestsForInitiator($initiator)
    {
        return array_map(function (TeamMemberEnrollmentRequest $request) {
            return $request->toEntity();
        }, TeamMemberEnrollmentRequest::with('team', 'requirements')->where('initiator_id', $initiator)
            ->where('status', TeamMemberEnrollmentRequestEntity::STATUS_PENDING)
            ->get()->all());
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::findPendingRequestsForTeam()
     */
    public function findPendingRequestsForTeam($team, $page = 1, $size = 15)
    {
        return $this->findRequestsForTeam($team, TeamMemberEnrollmentRequestEntity::STATUS_PENDING, $page, $size);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::findRejectedRequestsForTeam()
     */
    public function findRejectedRequestsForTeam($team, $page = 1, $size = 15)
    {
        return $this->findRequestsForTeam($team, TeamMemberEnrollmentRequestEntity::STATUS_REJECTED, $page, $size);
    }

    // find requests for team of specific status
    private function findRequestsForTeam($team, $status, $page, $size)
    {
        $query = TeamMemberEnrollmentRequest::with('initiator', 'requirements')
            ->where('team_id', $team)
            ->where('status', $status);
        /* @var $query \Illuminate\Database\Query\Builder */

        $total = $query->getCountForPagination()->count();
        $pages = ceil($total / $size);
        if ($page > $pages) {
            $page = $pages;
        }

        $requests = $query->forPage($page, $size)->get()->all();

        return [$pages, array_map(function (TeamMemberEnrollmentRequest $request) {
            return $request->toEntity();
        }, $requests)];
    }

    /**
     * @see \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::updateStatusToRejected()
     */
    public function updateStatusToRejected($request, $reason)
    {
        $requests = is_array($request) ? $request : [$request];

        return count($requests) == TeamMemberEnrollmentRequest::whereIn('id', $requests)
            ->where('status', TeamMemberEnrollmentRequestEntity::STATUS_PENDING)
            ->update([
                'status' => TeamMemberEnrollmentRequestEntity::STATUS_REJECTED,
                'reason' => $reason
            ]);
    }

    /**
     * @see \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::updateStatusToApproved()
     */
    public function updateStatusToApproved($request)
    {
        $requests = is_array($request) ? $request : [$request];

        return count($requests) == TeamMemberEnrollmentRequest::whereIn('id', $requests)
            ->where('status', TeamMemberEnrollmentRequestEntity::STATUS_PENDING)
            ->update([
                'status' => TeamMemberEnrollmentRequestEntity::STATUS_APPROVED
            ]);
    }

    /**
     * @see \Jihe\Contracts\Repositories\TeamMemberRequestRepository::findPermission()
     */
    public function findPermission($mobile, $team)
    {
        if (!is_array($mobile)) {
            $permission = TeamMemberEnrollmentPermission::where('mobile', $mobile)
                                                        ->where('team_id', $team)
                                                        ->first();

            return $permission ? $permission->toEntity() : null;
        }



        $existings = TeamMemberEnrollmentPermission::whereIn('mobile', $mobile)
                                                   ->where('team_id', $team)
                                                   ->get()->all();

        $permissions = [];
        foreach($existings as $permission) {
            $permissions[$permission->mobile] = $permission->toEntity();
        }
        return $permissions;
    }
    
    /**
     * @see \Jihe\Contracts\Repositories\TeamMemberRequestRepository::findTeamsWhitelistedUser()
     */
    public function findTeamsWhitelistedUser($mobile)
    {
        $permissions = TeamMemberEnrollmentPermission::with('team')->where('mobile', $mobile)
                                                ->where('status', \Jihe\Entities\TeamMemberEnrollmentPermission::STATUS_PERMITTED)
                                                ->get()->all();
        $teams = [];
        foreach ($permissions as $permission) {
            $teams[$permission->team_id] = $permission->team->toEntity();
        }

        return $teams;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberRequestRepository::addPermission()
     */
    public function addPermission(array $permission)
    {
        $permission['team_id'] = $permission['team'];
        unset($permission['team']);

        return TeamMemberEnrollmentPermission::create($permission)->id;
    }

    /**
     * @see \Jihe\Contracts\Repositories\TeamMemberRequestRepository::updatePermission()
     */
    public function updatePermission($permission, array $updates)
    {
        // only 'status' and 'memo' are accepted from $updates
        $normalized = [];
        array_has($updates, 'status') && $normalized['status'] = array_get($updates, 'status');
        array_has($updates, 'memo')   && $normalized['memo']   = array_get($updates, 'memo');
        array_has($updates, 'name')   && $normalized['name']   = array_get($updates, 'name');

        if (empty($normalized)) {
            return false;
        }

        return 1 == TeamMemberEnrollmentPermission::where('id', $permission)
                                                  ->update($normalized);
    }

    /**
     * @see \Jihe\Contracts\Repositories\TeamMemberRequestRepository::updatePermission()
     */
    public function deletePermission($permission)
    {
        return 1 == TeamMemberEnrollmentPermission::where('id', $permission)
                                                  ->delete();
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberRequestRepository::findProhibitedPermissionsFor()
     */
    public function findProhibitedPermissionsFor($team, $page = 1, $size = 15)
    {
        return $this->findPermissionsFor($team, \Jihe\Entities\TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
                                         $page, $size);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberRequestRepository::findPermittedPermissionsFor()
     */
    public function findPermittedPermissionsFor($team, $page = 1, $size = 15)
    {
        return $this->findPermissionsFor($team, \Jihe\Entities\TeamMemberEnrollmentPermission::STATUS_PERMITTED,
                                         $page, $size);
    }

    private function findPermissionsFor($team, $status, $page, $size)
    {
        $query = TeamMemberEnrollmentPermission::where('team_id', $team)
                                               ->where('status', $status);
        $total = $query->getCountForPagination()->count();
        $pages = ceil($total / $size);
        if ($page > $pages) {
            $page = $pages;
        }

        $permissions = $query->forPage($page, $size)->get()->all();
        return [$pages, array_map(function (TeamMemberEnrollmentPermission $permission) {
            return $permission->toEntity();
        }, $permissions)];
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::findPendingRequestedTeams()
     */
    public function findPendingRequestedTeams($user, array $options = [])
    {
        $query = TeamMemberEnrollmentRequest::where('team_member_enrollment_requests.initiator_id', $user)
                                            ->where('team_member_enrollment_requests.status', TeamMemberEnrollmentRequestEntity::STATUS_PENDING)
                                            ->orderBy('team_member_enrollment_requests.created_at', 'asc')
                                            ->orderBy('team_member_enrollment_requests.id', 'asc');
        
        $query->join('teams', 'teams.id', '=', 'team_member_enrollment_requests.team_id')
              ->where('teams.status', TeamEntity::STATUS_NORMAL);

        if (array_get($options, 'only_id', false)) {
            return array_map(function (TeamMemberEnrollmentRequest $request) {
                return $request->team->id;
            }, $query->get()->all());
        }

        return array_map(function (TeamMemberEnrollmentRequest $request) {
            return $request->team->toEntity();
        }, $query->get()->all());
    }
}
