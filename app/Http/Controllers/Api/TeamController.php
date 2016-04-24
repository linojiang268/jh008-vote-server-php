<?php
namespace Jihe\Http\Controllers\Api;

use Illuminate\Http\Request;
use Jihe\Entities\TeamMember;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\ActivityApplicantService;
use Jihe\Services\TeamService;
use Validator;
use Jihe\Entities\Team;
use Jihe\Entities\TeamRequirement;
use Jihe\Services\TeamMemberService;
use Jihe\Services\ActivityService;
use Jihe\Services\ActivityMemberService;
use Illuminate\Contracts\Auth\Guard;
use Jihe\Contracts\Services\Search\SearchService;

class TeamController extends Controller
{
    /**
     * get list of teams
     */
    public function getTeams(Request $request, Guard $auth, TeamService $teamService,
                             TeamMemberService $teamMemberService, ActivityService $activityService)
    {
        $this->validate($request, [
            'city' => 'required|integer',
            'name' => 'max:32',
            'page' => 'integer',
            'size' => 'integer',
        ], [
            'city.required' => '城市未填写',
            'city.integer'  => '城市格式错误',
            'name.max'      => '社团名称错误',
            'page.integer'  => '分页page错误',
            'size.integer'  => '分页size错误',
        ]);
        
        try {
            list($page, $size) = $this->sanePageAndSize(
                                        $request->input('page'), 
                                        $request->input('size'));
            
            list($pages, $teams) = $teamService->getTeams(
                                                 $page, $size,
                                                 [
                                                     'city'      => $request->input('city'),
                                                     'name'      => $request->input('name'),
                                                     'forbidden' => false,
                                                 ]);
            
            return $this->json([
                                'pages' => $pages,
                                'teams' => $this->getTeamWithRelateAttributes(
                                                  $teams, $auth->user()->id, 
                                                  $teamMemberService, $activityService),
                              ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    private function getTeamWithRelateAttributes($teams, $user, TeamMemberService $teamMemberService,
                                                 ActivityService $activityService)
    {
        if (0 == count($teams)) {
            return [];
        }

        // init array of team ids and array of team attributes
        $teamIds = [];
        $teamWithAttributes = [];
        /* @var $team \Jihe\Entities\Team */
        foreach ($teams as $team) {
            array_push($teamIds, is_array($team) ? $team['id'] : $team->getId());
            array_push($teamWithAttributes, [
                'id'            => is_array($team) ? $team['id'] : $team->getId(),
                'creator_id'    => is_array($team) ? $team['creator_id'] : $team->getCreator()->getId(),
                'name'          => is_array($team) ? $team['name'] : $team->getName(),
                'introduction'  => is_array($team) ? $team['introduction'] : $team->getIntroduction(),
                'certification' => is_array($team) ? $team['certification'] : $team->getCertification(),
                'logo_url'      => is_array($team) ? $team['logo_url'] : $team->getLogoUrlOfThumbnail(),
                'qr_code_url'   => is_array($team) ? $team['qr_code_url'] : $team->getQrCodeUrl(),
            ]);
        }

        // get other param relate to team
        $teamWithEnrolled = $teamMemberService->enrolled($user, $teamIds);
        $teamWithEnrolled = is_array($teamWithEnrolled) ? $teamWithEnrolled : [$teamWithEnrolled];
        $teamWithActivityNum = $activityService->getTeamsActivitiesCount($teamIds);
        $teamWithActivityNum = is_array($teamWithActivityNum) ? $teamWithActivityNum : [$teamWithActivityNum];
        $teamWithMemberNum = $teamMemberService->countMembers($teamIds);
        $teamWithMemberNum = is_array($teamWithMemberNum) ? $teamWithMemberNum : [$teamWithMemberNum];

        // morph other param to array of team attributes
        return array_values(array_map(function (array $team, $enrolled, $activityNum, $memberNum) {
            $team['joined'] = $enrolled;
            $team['activity_num'] = $activityNum;
            $team['member_num'] = $memberNum;
            return $team;
        }, $teamWithAttributes, $teamWithEnrolled, $teamWithActivityNum, $teamWithMemberNum));
    }
    
    /**
     * get list of relate teams
     */
    public function getRelateTeams(Request $request, Guard $auth, 
                                   TeamMemberService $teamMemberService, 
                                   ActivityService $activityService, SearchService $searchService)
    {
        $this->validate($request, [
            'city'       => 'required|integer',
            'only_count' => 'boolean',
        ], [
            'city.required'      => '城市未指定',
            'city.integer'       => '城市错误',
            'only_count.boolean' => '返回指定错误',
        ]);
        
        try {
            $enrolledTeams = $teamMemberService->listEnrolledTeams(
                                                 $auth->user()->id, null, null,
                                                 ['only_id' => false, 'paging' => false]);
            $requestedTeams = $teamMemberService->listRequestedTeams(
                                                  $auth->user()->id);
            $recommendedTeams = $searchService->getRecommendTeam(
                                                $request->input('city'), 
                                                array_map(function ($tag) {
                                                    return $tag->name;
                                                }, $auth->user()->tags->toBase()->all()));
            $invitedTeams = $teamMemberService->findTeamsWhitelistedUser($auth->user()->getAuthIdentifier(), $auth->user()->mobile);
            
            if ($request->input('only_count', false)) {
                return $this->json([
                    'enrolled_teams'    => count($enrolledTeams),
                    'requested_teams'   => count($requestedTeams),
                    'recommended_teams' => count($recommendedTeams),
                    'invited_teams'     => count($invitedTeams),
                ]);
            }
            
            return $this->json([
                    'enrolled_teams' => $this->getTeamWithRelateAttributes(
                                               $enrolledTeams, $auth->user()->id,
                                               $teamMemberService, $activityService),
                    'requested_teams' => $this->getTeamWithRelateAttributes(
                                                $requestedTeams, $auth->user()->id,
                                                $teamMemberService, $activityService),
                    'recommended_teams' => $this->getTeamWithRelateAttributes(
                                                  $recommendedTeams, $auth->user()->id,
                                                  $teamMemberService, $activityService),
                    'invited_teams' => $this->getTeamWithRelateAttributes(
                                              $invitedTeams, $auth->user()->id,
                                              $teamMemberService, $activityService),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * get relate teams of user(joined, invited, joined activities' team)
     */
    public function getTeamsOfUser(Guard $auth, TeamMemberService $teamMemberService, TeamService $teamService,
                                   ActivityMemberService $activityMemberService, ActivityApplicantService $activityApplicantService)
    {
        try {
            $enrolledTeamIds = $teamMemberService->listEnrolledTeams(
                $auth->user()->getAuthIdentifier(), null, null, [
                    'only_id' => true, 'paging' => false,
                ]
            );

            $requestedTeamIds = $teamMemberService->listRequestedTeams(
                $auth->user()->id, ['only_id' => true]);

            $invitedTeamIds = array_keys($teamMemberService->findTeamsWhitelistedUser(
                $auth->user()->getAuthIdentifier(), $auth->user()->mobile));

            $teamIdsOfJoinedActivities = $activityMemberService->getTeamsOfJoinedActivities($auth->user()->toEntity());

            $teamIdsOfRequestedActivities = $activityApplicantService->getTeamsOfRequestedActivities($auth->user()->toEntity());

            $teamIds = array_merge($enrolledTeamIds, $requestedTeamIds, $invitedTeamIds, $teamIdsOfJoinedActivities, $teamIdsOfRequestedActivities);
            list($total, $teams) = $teamService->getTeamsOf($teamIds);

            return $this->json([
                'teams' => $this->morphTeamWithUserRelation(
                    $teams, $enrolledTeamIds,
                    $requestedTeamIds, $invitedTeamIds),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    private function morphTeamWithUserRelation($teams, array $joinedTeamIds = [],
                                         array $requesTeamIds = [], array $invitedTeamIds = [])
    {
        return array_map(function (Team $team) use ($joinedTeamIds, $requesTeamIds, $invitedTeamIds) {
            return [
                'id'            => $team->getId(),
                'creator_id'    => $team->getCreator()->getId(),
                'name'          => $team->getName(),
                'introduction'  => $team->getIntroduction(),
                'certification' => $team->getCertification(),
                'logo_url'      => $team->getLogoUrlOfThumbnail(),
                'qr_code_url'   => $team->getQrCodeUrl(),
                'joined'        => in_array($team->getId(), $joinedTeamIds),
                'requested'     => in_array($team->getId(), $requesTeamIds),
                'in_whitelist'  => in_array($team->getId(), $invitedTeamIds),
            ];
        }, $teams);
    }
    
    /**
     * 
     * @param Team $team     \Jihe\Entities\Team
     * @return array
     */
    private function morphToTeamArray(Team $team)
    {
        if (empty($team)) {
            return null;
        }

        return [
                'id'                    => $team->getId(),
                'name'                  => $team->getName(),
                'introduction'          => $team->getIntroduction(),
                'certification'         => $team->getCertification(),
                'logo_url'              => $team->getLogoUrlOfThumbnail(),
                'qr_code_url'           => $team->getQrCodeUrl(),
                'join_type'             => $team->getJoinType(),
                'join_requirements'     => array_map(function (TeamRequirement $requirement) {
                                                        return [
                                                                'id'          => $requirement->getId(),
                                                                'requirement' => $requirement->getRequirement(),
                                                        ];
                                                    }, $team->getRequirements()),
                'activities_updated_at' => is_null($team->getActivitiesUpdatedAt()) ? 0 : strtotime($team->getActivitiesUpdatedAt()),
                'members_updated_at'    => is_null($team->getMembersUpdatedAt()) ? 0 : strtotime($team->getMembersUpdatedAt()),
                'news_updated_at'       => is_null($team->getNewsUpdatedAt()) ? 0 : strtotime($team->getNewsUpdatedAt()),
                'albums_updated_at'     => is_null($team->getAlbumsUpdatedAt()) ? 0 : strtotime($team->getAlbumsUpdatedAt()),
                'notices_updated_at'    => is_null($team->getNoticesUpdatedAt()) ? 0 : strtotime($team->getNoticesUpdatedAt()),
        ];
    }
    
    /**
     * get info of team
     * 
     * @param Request $request
     * @param TeamService $teamService
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeam(Request $request, TeamService $teamService, Guard $auth,
                            TeamMemberService $teamMemberService)
    {
        // validate request
        $validator = Validator::make($request->only('team'), [
            'team' => 'required|integer',
        ], [
            'team.required' => '社团未填写',
            'team.integer'  => '社团错误',
        ]);
    
        /* @var $validator \Illuminate\Validation\Validator  */
        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }
    
        try {
            /* @var $authUser \Jihe\Entities\User  */
            $authUser = $auth->user()->toEntity();
            $team = $teamService->getTeam($request->input('team'));
            
            $teamAttributes = $this->morphToTeamArray($team);
            
            if ($authUser->getId() == $team->getCreator()->getId() || 
                $teamMemberService->enrolled($authUser->getId(), $team->getId())) {
                $teamAttributes['joined'] = true;

                $member = $teamMemberService->getTeamMember($authUser->getId(), $team->getId());
                /* @var $member \Jihe\Entities\TeamMember */
                $teamAttributes['visibility'] = $member ? $member->getVisibility() : TeamMember::VISIBILITY_ALL;
            } else {
                $teamAttributes['joined'] = false;
                
                if ($teamMemberService->hasPendingEnrollmentRequest($authUser, $team)) {
                    $teamAttributes['requested'] = true;
                } else {
                    $teamAttributes['requested'] = false;
                    
                    $permission = $teamMemberService->findEnrollmentPermission($authUser->getMobile(), $team->getId());
                    if ($permission) {
                        if ($permission->permitted()) {
                            $teamAttributes['in_whitelist'] = true;
                        } elseif ($permission->prohibited()) {
                            $teamAttributes['in_blacklist'] = true;
                        } else {
                            $teamAttributes['in_whitelist'] = false;
                            $teamAttributes['in_blacklist'] = false;
                        }
                    } else {
                        $teamAttributes['in_whitelist'] = false;
                        $teamAttributes['in_blacklist'] = false;
                    }
                }
            }
            
            $teamAttributes['member_num'] = $teamMemberService->countMembers($team->getId());
            
            return $this->json($teamAttributes);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
}