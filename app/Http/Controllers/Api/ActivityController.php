<?php
namespace Jihe\Http\Controllers\Api;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Jihe\Entities\ActivityPlan;
use Jihe\Http\Controllers\Controller;
use Jihe\Models\ActivityApplicant;
use Jihe\Services\ActivityCheckInService;
use Jihe\Services\ActivityService;
use Jihe\Services\StorageService;
use Illuminate\Support\Facades\Auth;
use Jihe\Entities\Activity;
use Jihe\Entities\ActivityAlbumImage;
use Jihe\Entities\ActivityFile;
use Jihe\Utils\PaginationUtil;
use Jihe\Services\ActivityApplicantService;
use Jihe\Services\TeamMemberService;
use Jihe\Services\ActivityMemberService;
use Jihe\Services\PushService;

class ActivityController extends Controller
{
    /**
     * list all published activities in city
     */
    public function listActivitiesInCity(Request $request,
                                         ActivityService $activityService,
                                         ActivityMemberService $activityMemberService,
                                         TeamMemberService $teamMemberService)
    {
        // validate request
        $this->validate($request, [
            'city' => 'required|integer',
            'page' => 'integer',
            'size' => 'integer',
        ], [
            'city.required' => '城市未填写',
            'city.integer'  => '城市格式错误',
            'page.integer'  => '页码错误',
            'size.integer'  => '分页参数错误',
        ]);

        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));
        $city = $request->input('city');
        try {
            list($total, $activities) = $activityService->getPublishedActivitiesInCity($city, $page, $size);

            $activities = $this->assembleData($activities, $activityMemberService, $teamMemberService);
            return $this->json([
                'pages'      => PaginationUtil::count2Pages($total, $size),
                'activities' => $this->activitiesSort($activities),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * search activities via its name in some city
     */
    public function searchActivitiesInCity(Request $request,
                                           ActivityService $activityService,
                                           ActivityMemberService $activityMemberService,
                                           TeamMemberService $teamMemberService)
    {
        // validate request
        $this->validate($request, [
            'city'    => 'required|integer',
            'keyword' => 'required|string',
            'page'    => 'integer',
            'size'    => 'integer',
        ], [
            'city.required'    => '城市未填写',
            'city.integer'     => '城市格式错误',
            'keyword.required' => '城市未填写',
            'keyword.string'   => '城市格式错误',
            'page.integer'     => '分页page错误',
            'size.integer'     => '分页size错误',
        ]);

        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        try {
            list($total, $activities) = $activityService->searchActivities($request->input('keyword'),
                $request->input('city'),
                $page, $size);
            $activities = $this->assembleData($activities, $activityMemberService, $teamMemberService);
            return $this->json([
                'pages'      => PaginationUtil::count2Pages($total, $size),
                'activities' => $activities,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * find activities by team
     */
    public function listActivitiesByTeam(Request $request,
                                         ActivityService $activityService,
                                         ActivityMemberService $activityMemberService,
                                         TeamMemberService $teamMemberService)
    {
        // validate request
        $this->validate($request, [
            'team' => 'required|integer',
            'page' => 'integer',
            'size' => 'integer',
        ], [
            'team.required' => '社团未填写',
            'team.integer'  => '社团格式错误',
            'page.integer'  => '分页page错误',
            'size.integer'  => '分页size错误',
        ]);

        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        try {
            $params = $request->only('team', 'page', 'size');
            list($total, $activities) = $activityService->getPublishedActivityListByTeam($params['team'], $page, $size);
            $activities = $this->assembleData($activities, $activityMemberService, $teamMemberService);
            return $this->json([
                'pages'      => PaginationUtil::count2Pages($total, $size),
                'activities' => $activities,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * activity detail
     */
    public function getDetail(Request $request,
                              ActivityService $activityService,
                              ActivityApplicantService $activityApplicantService,
                              ActivityMemberService $activityMemberService,
                              TeamMemberService $teamMemberService)
    {
        // validate request
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动未填写',
            'activity.integer'  => '活动格式错误',
        ]);

        try {
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null) {
                return $this->jsonException('活动不存在');
            }
//            $activity = $activityService->findAllStatusActivitiesByIds($request->input('activity'));
//            $activity = isset($activity[0]) ? $activity[0] : null;
//            if ($activity == null) {
//                return $this->jsonException('活动不存在');
//            }
//            if($activity->getStatus() == Activity::STATUS_DELETE){
//                return $this->jsonException('活动已下架');
//            }

            list($count, $activityPlans) = $activityService->findActivityPlanByActivityId($activity->getId());
            $activityApplicant = $activityApplicantService->getUserApplicantInfo(Auth::user()->id, $activity->getId());
            $activity = $this->getActivityDetail($activity);
            $activity['activity_plans'] = $activityPlans;
            $activity['timeout_seconds'] = 0;
            if ($activityApplicant != null) {
                $activity['applicant_status'] = $activityApplicant['status'];
                if ($activityApplicant['status'] == ActivityApplicant::STATUS_PAY) {
                    if (strtotime($activityApplicant['expire_at']) > time()) {
                        $activity['timeout_seconds'] = strtotime($activityApplicant['expire_at']) - time();
                    }
                }
            } else {
                $activity['applicant_status'] = ActivityApplicant::STATUS_NORMAL;
            }
            $activities = $this->setRelatedData(Auth::user()->id,
                [$activity],
                $activityMemberService,
                $teamMemberService);

            return $this->json([
                'activity' => $activities[0],
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * activity detail
     */
    public function getNewDetail(Request $request,
                                 ActivityService $activityService,
                                 ActivityApplicantService $activityApplicantService,
                                 ActivityMemberService $activityMemberService,
                                 ActivityCheckInService $activityCheckInService,
                                 TeamMemberService $teamMemberService)
    {
        // validate request
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动未填写',
            'activity.integer'  => '活动格式错误',
        ]);

        try {
            $activityDb = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activityDb == null) {
                return $this->jsonException('活动不存在');
            }
//            $activityDb = $activityService->findAllStatusActivitiesByIds($request->input('activity'));
//            $activityDb = isset($activityDb[0]) ? $activityDb[0] : null;
//            if ($activityDb == null) {
//                return $this->jsonException('活动不存在');
//            }
//            if($activityDb->getStatus() == Activity::STATUS_DELETE){
//                return $this->jsonException('活动已下架');
//            }

            $activity = $this->getActivityDetail($activityDb);
            list($count, $activityPlans) = $activityService->findActivityPlanByActivityId($activityDb->getId());
            $activityPlans = array_map(function (ActivityPlan $activityPlan) {
                return $this->getActivityPlanDetailInList($activityPlan);
            }, $activityPlans);
            $activity['activity_plans'] = $activityPlans;
            $activityApplicant = $activityApplicantService->getUserApplicantInfo(Auth::user()->id, $activityDb->getId());
            $activity['activity_members_count'] = $activityMemberService->totalMemberOf($activityDb->getId());
            $activity['activity_album_count'] = $activityService->countApprovedAlbumImagesOfActivity($activityDb);
            $activity['activity_file_count'] = $activityService->countFilesOfActivity($activityDb);
            $activity['activity_check_in_list'] = $activityCheckInService->getCheckInList(Auth::user()->id, $activityDb->getId());
            $activity['timeout_seconds'] = 0;
            if ($activityApplicant != null) {
                $activity['applicant_status'] = $activityApplicant['status'];
                if ($activityApplicant['status'] == ActivityApplicant::STATUS_PAY) {
                    if (strtotime($activityApplicant['expire_at']) > time()) {
                        $activity['timeout_seconds'] = strtotime($activityApplicant['expire_at']) - time();
                    }
                }
            } else {
                $activity['applicant_status'] = ActivityApplicant::STATUS_NORMAL;
            }
            $activities = $this->setRelatedData(Auth::user()->id,
                [$activity],
                $activityMemberService,
                $teamMemberService);

            return $this->json([
                'activity' => $activities[0],
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * activity detail
     */
    public function getCheckInActivityDetail(Request $request,
                                             ActivityService $activityService,
                                             ActivityApplicantService $activityApplicantService,
                                             ActivityMemberService $activityMemberService,
                                             ActivityCheckInService $activityCheckInService,
                                             TeamMemberService $teamMemberService)
    {
        // validate request
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动未填写',
            'activity.integer'  => '活动格式错误',
        ]);

        try {
            $activityDb = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activityDb == null) {
                return $this->jsonException('活动不存在');
            }
            list($count, $activityPlans) = $activityService->findActivityPlanByActivityId($activityDb->getId());
            $activity = $this->getActivityDetail($activityDb);
            $activityPlans = array_map(function (ActivityPlan $activityPlan) {
                return $this->getActivityPlanDetailInList($activityPlan);
            }, $activityPlans);
            $activity['activity_plans'] = $activityPlans;
            $activity['applicant_status'] = $activityApplicantService->getUserApplicantStatus(Auth::user()->id,
                $activityDb->getId());
            $activity['activity_members_count'] = $activityMemberService->totalMemberOf($activityDb->getId());
            $activity['activity_album_count'] = $activityService->countApprovedAlbumImagesOfActivity($activityDb);
            $activity['activity_file_count'] = $activityService->countFilesOfActivity($activityDb);
            $activity['activity_check_in_list'] = $activityCheckInService->getCheckInList(Auth::user()->id, $activityDb->getId());
            $activities = $this->setRelatedData(Auth::user()->id,
                [$activity],
                $activityMemberService,
                $teamMemberService);

            return $this->json([
                'activity' => $activities[0],
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }


    public function getPaymentTimeoutSeconds(Request $request,
                                             ActivityService $activityService,
                                             ActivityApplicantService $activityApplicantService)
    {
        // validate request
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动未填写',
            'activity.integer'  => '活动格式错误',
        ]);
        try {
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null) {
                return $this->jsonException('活动不存在');
            }
            $result = [];
            $activityApplicant = $activityApplicantService->getUserApplicantInfo(Auth::user()->id, $activity->getId());
            $result['timeout_seconds'] = Activity::PAYMENT_TIMEOUT;
            if ($activityApplicant != null && $activityApplicant['status'] == ActivityApplicant::STATUS_PAY) {
                if (strtotime($activityApplicant['expire_at']) > time()) {
                    $result['timeout_seconds'] = strtotime($activityApplicant['expire_at']) - time();
                } else {
                    $result['timeout_seconds'] = 0;
                }
            }

            return $this->json([
                'timeout_seconds' => $result['timeout_seconds'],
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * my activities count
     */
    public function getMyActivitiesCount(ActivityService $activityService)
    {
        try {
            $page = $size = 1;
            list($allTotal, $activities1) = $activityService->findUserActivities(Auth::user()->id, 'All', $page, $size);
            list($notBeginningTotal, $activities2) = $activityService->findUserActivities(Auth::user()->id, 'NotBeginning', $page, $size);
            list($waitPayTotal, $activities3) = $activityService->findUserActivities(Auth::user()->id, 'WaitPay', $page, $size);
            list($auditingTotal, $activities4) = $activityService->findUserActivities(Auth::user()->id, 'Auditing', $page, $size);
            list($endTotal, $activities) = $activityService->findUserActivities(Auth::user()->id, 'End', $page, $size);

            return $this->json([
                'count' => [
                    'all'          => $allTotal - $waitPayTotal - $auditingTotal,
                    'notBeginning' => $notBeginningTotal,
                    'waitPay'      => $waitPayTotal,
                    'end'          => $endTotal,
                ],
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * my activities
     */
    public function getHomePageMyActivities(ActivityService $activityService,
                                            ActivityMemberService $activityMemberService,
                                            TeamMemberService $teamMemberService)
    {
        try {
            $activities = $activityService->findHomePageUserActivities(Auth::user()->id);
            if (empty($activities)) {
                $page = $size = 1;
                list($allTotal, $activities) = $activityService->findUserActivities(Auth::user()->id, 'All', $page, $size);
            }
            $activities = $this->assembleData($activities, $activityMemberService, $teamMemberService);

            return $this->json([
                'activities' => $this->myActivitiesSort($activities),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * my activities
     */
    public function getMyActivities(Request $request,
                                    ActivityService $activityService,
                                    ActivityMemberService $activityMemberService,
                                    TeamMemberService $teamMemberService)
    {
        // validate request
        $this->validate($request, [
            'type' => 'string',
            'page' => 'integer',
            'size' => 'integer',
        ], [
            'type.string'  => '列表类型格式错误',
            'page.integer' => '分页page错误',
            'size.integer' => '分页size错误',
        ]);
        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        try {
            list($total, $activities) = $activityService->findUserActivities(Auth::user()->id,
                array_get($request->all(), 'type', 'All'),
                $page,
                $size);

            $activities = $this->assembleData($activities, $activityMemberService, $teamMemberService);

            return $this->json([
                'pages'      => PaginationUtil::count2Pages($total, $size),
                'activities' => $activities,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /*
     * subscribe topic list
     */
    public function getMyTopics(ActivityService $activityService,
                                ActivityMemberService $activityMemberService,
                                TeamMemberService $teamMemberService,
                                PushService $pushService)
    {
        try {
            $topicsList = [];
            $addedActivitiesIds = $activityMemberService->getUserParticipateInActivities(Auth::user()->id);
            $addedActivities = $activityService->findNotEndActivitiesByIds($addedActivitiesIds);
            if ($addedActivities) {
                foreach ($addedActivities as $addedActivity) {
                    $topic = $pushService->getActivityTopics($addedActivity->getId());
                    $topicsList[] = $topic;
                }
            }
            $teams = $teamMemberService->listEnrolledTeams(Auth::user()->id, ['only_id' => true]);
            if ($teams) {
                $topicsList = array_merge($topicsList, $pushService->getTeamTopics($teams));
            }
            return $this->json([
                'topic_list' => $topicsList,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * find near by activities
     */
    public function listActivitiesByPoint(Request $request,
                                          ActivityService $activityService,
                                          ActivityMemberService $activityMemberService,
                                          TeamMemberService $teamMemberService)
    {
        // validate request
        $this->validate($request, [
            'lat'  => 'required|numeric',
            'lng'  => 'required|numeric',
            'dist' => 'integer',
            'page' => 'integer',
            'size' => 'integer',
        ], [
            'lat.required' => '纬度未填写',
            'lng.required' => '经度未填写',
            'lat.float'    => '纬度格式错误',
            'lng.float'    => '经度格式错误',
            'dist.integer' => '距离格式错误',
            'page.integer' => '分页page错误',
            'size.integer' => '分页size错误',
        ]);
        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));
        try {
            $dist = array_get($request->all(), 'dist', 5);
            $lat = $request->input('lat');
            $lng = $request->input('lng');
            if ($lat < -90 || $lat > 90) {
                return $this->jsonException('纬度取值错误');
            }
            if ($lng < -180 || $lng > 180) {
                return $this->jsonException('经度取值错误');
            }
            list($total, $activities) = $activityService->getNearbyActivities(
                [$lat, $lng],
                $dist, $page, $size);
            $activities = $this->assembleData($activities, $activityMemberService, $teamMemberService);
            return $this->json([
                'pages'      => PaginationUtil::count2Pages($total, $size),
                'activities' => $activities,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * search have album activity in team
     *
     * @param  Request         $request
     * @param  ActivityService $activityService
     *
     * @return string
     */
    public function listActivitiesByHasAlbum(Request $request, ActivityService $activityService, ActivityMemberService $activityMemberService)
    {
        // validate request
        $this->validate($request, [
            'team' => 'required|integer',
            'page' => 'integer',
            'size' => 'integer',
        ], [
            'team.required' => '社团未填写',
            'team.integer'  => '社团格式错误',
            'page.integer'  => '分页page错误',
            'size.integer'  => '分页size错误',
        ]);
        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));
        try {
            list($total, $activities) = $activityService->getHasAlbumActivities(
                $request->input('team'),
                $page, $size);
            $activitiesIds = [];
            $activities = array_map(function (Activity $activity) use (&$activitiesIds) {
                $activitiesIds[] = $activity->getId();
                return $this->getActivitySimpleDetailInList($activity);
            }, $activities);
            $checkAddedActivity = $activityMemberService->judgeActivitiesWhetherUserParticipatedIn($activitiesIds, Auth::user()->id);
            if (!empty($checkAddedActivity)) {
                foreach ($activities as $key => $activity) {
                    if (isset($checkAddedActivity[$activity['id']])) {
                        $activities[$key]['added_activity'] = $checkAddedActivity[$activity['id']];
                    } else {
                        $activities[$key]['added_activity'] = false;
                    }
                }
            }

            return $this->json([
                'pages'      => PaginationUtil::count2Pages($total, $size),
                'activities' => $activities,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * Current user does not score activity
     *
     * @param  Request         $request
     * @param  ActivityService $activityService
     *
     * @return string
     */
    public function getCurrentUserNoScoreActivities(ActivityService $activityService,
                                                    ActivityMemberService $activityMemberService,
                                                    TeamMemberService $teamMemberService)
    {
        try {
            $activityIds = $activityMemberService->getMemberWhereScoreIsNull(Auth::user()->id);
            if (empty($activityIds)) {
                return $this->json(['activities' => null]);
            }
            $activities = $activityService->getEndOfYesterdayActivitiesByIds($activityIds);
            $activities = array_map(function (Activity $activity) {
                return $this->getActivityNoScoreDetailInList($activity);
            }, $activities);
            $activities = $this->setRelatedData(Auth::user()->id,
                $activities,
                $activityMemberService,
                $teamMemberService);

            return $this->json(['activities' => $activities]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /*
     * recommend activities
     */
    public function recommendActivities(Request $request,
                                        ActivityService $activityService,
                                        ActivityMemberService $activityMemberService,
                                        TeamMemberService $teamMemberService)
    {
        // validate request
        $this->validate($request, [
            'city' => 'required|integer',
        ], [
            'city.required' => '城市未填写',
            'city.integer'  => '城市格式错误',
        ]);
        try {
            $tags = array_map(function ($tag) {
                return $tag->name;
            }, Auth::user()->tags->toBase()->all());
            $activities = $activityService->searchMyRecommendActivities($request->input('city'), $tags);
            $activities = $this->setRelatedData(Auth::user()->id, $activities, $activityMemberService, $teamMemberService);

            return $this->json([
                'activities' => $activities,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }


    /*
     * assemble list data
     */
    private function assembleData($activities, ActivityMemberService $activityMemberService, TeamMemberService $teamMemberService)
    {
        $assembledActivities = array_map(function (Activity $activity) {
            return $this->getActivityDetailInList($activity);
        }, $activities);

        $assembledActivities = $this->setRelatedData(Auth::user()->id, $assembledActivities, $activityMemberService, $teamMemberService);

        return $assembledActivities;
    }

    /*
     * get activity detail to array
     */
    private function getActivityDetail(Activity $activity)
    {
        if (empty($activity)) {
            return [];
        }
        $tmp = $this->getActivityDetailInList($activity);
        $item = [
            'contact'              => $activity->getContact(),
            'telephone'            => $activity->getTelephone(),
            'begin_time'           => $activity->getBeginTime(),
            'end_time'             => $activity->getEndTime(),
            'enroll_begin_time'    => $activity->getEnrollBeginTime(),
            'enroll_end_time'      => $activity->getEnrollEndTime(),
            'enroll_type'          => $activity->getEnrollType(),
            'enroll_limit'         => $activity->getEnrollLimit(),
            'roadmap'              => $activity->getRoadmap(),
            'update_step'          => $activity->getUpdateStep(),
            'images_url'           => $activity->getImagesUrlOfThumbnail(),
            'detail'               => $activity->getDetail(),
            'organizers'           => $activity->getOrganizers(),
            'will_payment_timeout' => $activity->willPaymentTimeout(),
        ];
        $item = array_merge($tmp, $item);
        return $item;
    }

    private function replaceAddress($address)
    {
        $str = '四川省';
        $pos = mb_strpos($address, $str);
        if ($pos === false) {
            return $str . $address;
        }
        return $address;
    }

    /**
     * list activity fields data to array
     */
    private function getActivityDetailInList(Activity $activity)
    {
        $tmp = $this->getActivitySimpleDetailInList($activity);
        $item = [
            'team_id'          => $activity->getTeam()->getId(),
            'sub_status'       => $activity->getSubStatus(),
            'cover_url'        => $activity->getCoverUrlOfThumbnail(),
            'qr_code_url'      => $activity->getQrCodeUrl(),
            'address'          => $this->replaceAddress($activity->getaddress()),
            'brief_address'    => $activity->getBriefAddress(),
            'enroll_fee_type'  => $activity->getEnrollFeeType(),
            'enroll_fee'       => number_format($activity->getEnrollFee() / 100, 2),
            'essence'          => $activity->getEssence(),
            'city'             => [
                'id'   => $activity->getCity()->getId(),
                'name' => $activity->getCity()->getName(),
            ],
            'team'             => [
                'id'           => $activity->getTeam()->getId(),
                'name'         => $activity->getTeam()->getName(),
                'logo_url'     => $activity->getTeam()->getLogoUrl(),
                'introduction' => $activity->getTeam()->getIntroduction(),
            ],
            'location'         => $activity->getLocation(),
            'status'           => $activity->getStatus(),
            'applicant_status' => $activity->getApplicantsStatus(),
            'enrolled_team'    => false,
            'enrolled_num'     => 0,
            'auditing'         => $activity->getAuditing(),
            'enroll_attrs'     => $activity->getEnrollAttrs(),
        ];
        return array_merge($tmp, $item);
    }

    /**
     * list simple activity fields data to array
     */
    private function getActivitySimpleDetailInList(Activity $activity)
    {
        if (empty($activity)) {
            return [];
        }
        if (null == $activity->getTeam()) {
            throw new \Exception('非法社团');
        }
        if (null == $activity->getCity()) {
            throw new \Exception('非法地域信息');
        }
        return [
            'id'           => $activity->getId(),
            'title'        => $activity->getTitle(),
            'publish_time' => $activity->getPublishTime(),
            'begin_time'   => $activity->getBeginTime(),
            'end_time'     => $activity->getEndTime(),
        ];
    }

    private function getActivityNoScoreDetailInList(Activity $activity)
    {
        $tmp = $this->getActivitySimpleDetailInList($activity);
        $item = [
            'team_id'       => $activity->getTeam()->getId(),
            'team'          => [
                'id'           => $activity->getTeam()->getId(),
                'name'         => $activity->getTeam()->getName(),
                'logo_url'     => $activity->getTeam()->getLogoUrl(),
                'introduction' => $activity->getTeam()->getIntroduction(),
            ],
            'location'      => $activity->getLocation(),
            'enrolled_num'  => 0,
            'enrolled_team' => false,
        ];
        return array_merge($tmp, $item);
    }

    /**
     * list approved albums of activity
     */
    public function listApprovedAlbumImages(Request $request, ActivityService $activityService)
    {
        $this->validate($request, [
            'activity'     => 'required|integer',
            'creator_type' => 'required|integer',
            'page'         => 'integer|min:1',
            'size'         => 'integer|min:1',
        ], [
            'activity.required'     => '活动未指定',
            'activity.integer'      => '活动错误',
            'creator_type.required' => '创建者类型未指定',
            'creator_type.integer'  => '创建者类型错误',
            'page.integer'          => '分页page错误',
            'page.min'              => '分页page错误',
            'size.integer'          => '分页size错误',
            'size.min'              => '分页size错误',
        ]);

        list($pageIndex, $pageSize) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null) {
                return $this->jsonException('活动非法');
            }

            list($pages, $images) = (ActivityAlbumImage::SPONSOR == $request->input('creator_type')) ?
                $activityService->getAlbumImagesOfSponsor(
                    $activity,
                    $pageIndex,
                    $pageSize)
                :
                $activityService->getApprovedAlbumImages(
                    $activity,
                    $pageIndex,
                    $pageSize);

            return $this->json(
                [
                    'pages'  => $pages,
                    'images' => array_map(function (ActivityAlbumImage $image) {
                        return [
                            'id'        => $image->getId(),
                            'image_url' => $image->getImageUrlOfThumbnail(),
                        ];
                    }, $images),
                ]
            );
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * list user's albums of activity
     */
    public function listAlbumImagesOfUser(Request $request, ActivityService $activityService, Guard $auth)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
            'page'     => 'integer|min:1',
            'size'     => 'integer|min:1',
        ], [
            'activity.required' => '活动未指定',
            'activity.integer'  => '活动错误',
            'page.integer'      => '分页page错误',
            'page.min'          => '分页page错误',
            'size.integer'      => '分页size错误',
            'size.min'          => '分页size错误',
        ]);

        list($pageIndex, $pageSize) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null) {
                return $this->jsonException('活动非法');
            }

            list($pages, $images) = $activityService->getUserAlbumImages($activity,
                $pageIndex,
                $pageSize,
                $auth->user()->getAuthIdentifier());

            return $this->json(
                [
                    'pages'  => $pages,
                    'images' => array_map(function (ActivityAlbumImage $image) {
                        return [
                            'id'        => $image->getId(),
                            'image_url' => $image->getImageUrlOfThumbnail(),
                        ];
                    }, $images),
                ]
            );
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     *  add album image of activity
     */
    public function addAlbumImage(Request $request, Guard $auth, ActivityService $activityService, StorageService $storageService)
    {
        $this->validate($request, [
            'image' => 'required|mimes:jpeg,png,jpg',
        ], [
            'image.required' => 'image未设置',
            'image.mimes'    => 'image错误',
        ]);

        /* @var $image \Symfony\Component\HttpFoundation\File\UploadedFile */
        $image = $request->file('image');
        $imageId = null;

        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null) {
                return $this->jsonException('活动非法');
            }

            // store the image file
            $imageId = $storageService->storeAsImage($image);
            $imageEntity = $activityService->addAlbumImage([
                'activity'     => $activity,
                'creator_type' => ActivityAlbumImage::USER,
                'creator'      => $auth->user()->getAuthIdentifier(),
                'image_id'     => $imageId,
            ]);

            @unlink($image);

            return $this->json(
                [
                    'id'        => $imageEntity->getId(),
                    'image_url' => $imageEntity->getImageUrlOfThumbnail(),
                ]
            );
        } catch (\Exception $ex) {
            @unlink($image);
            return $this->jsonException($ex);
        }
    }

    /**
     * remove user's albums of activity
     */
    public function removeAlbumImagesOfUser(Request $request, ActivityService $activityService, Guard $auth)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
            'images'   => 'required|array',
        ], [
            'activity.required' => '活动未指定',
            'activity.integer'  => '活动错误',
            'images.required'   => '相册未指定',
            'images.array'      => '相册错误',
        ]);

        if (!$this->validateAlbumImages($request->input('images'))) {
            return $this->jsonException('相册格式错误');;
        }

        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null) {
                return $this->jsonException('活动非法');
            }

            $activityService->removeAlbumImages($activity,
                $request->input('images'),
                $auth->user()->getAuthIdentifier());
            return $this->json('删除相册成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     *
     * @param array $images ids of album images
     */
    private function validateAlbumImages(array $images)
    {
        foreach ($images as $image) {
            if (filter_var($image, FILTER_VALIDATE_INT) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * set related data
     *
     * @param int   $user       user id
     * @param array $activities activity array
     *
     * @return bool
     */
    public function setRelatedData($user, $activities, ActivityMemberService $activityMemberService, TeamMemberService $teamMemberService)
    {
        if (empty($activities)) {
            return $activities;
        }
        $teams = [];
        $activityIds = [];
        foreach ($activities as $index => $activity) {
            if (isset($activity['team_id'])) {
                $teams[] = $activity['team_id'];
            }
            if (isset($activity['id'])) {
                $activityIds[] = $activity['id'];
            }
        }
        $activities = $this->setEnrolledTotal($activities, $activityIds, $activityMemberService);
        $activities = $this->setEnrolledTeam($user, $activities, $teams, $teamMemberService);

        return $activities;
    }

    /**
     * count added to activity user
     *
     * @param array $activities  get activities
     * @param array $activityIds activity id array
     *
     * @return array
     */

    private function setEnrolledTotal($activities, $activityIds, ActivityMemberService $activityMemberService)
    {
        $enrolledCounts = $activityMemberService->getActivityMembersCount($activityIds);
        if ($enrolledCounts) {
            foreach ($activities as $index => $activity) {
                if (null == $activityId = array_get($activity, 'id')) {
                    continue;
                }
                if (isset($enrolledCounts[$activityId])) {
                    $activities[$index]['enrolled_num'] = $enrolledCounts[$activityId];
                }
            }
        }

        return $activities;
    }

    /**
     * Checking an activity created by the current user team
     *
     * @param int   $user       login id
     * @param array $activities get activities
     * @param array $teams      team id array
     *
     * @return array
     */
    private function setEnrolledTeam($user, $activities, $teams, TeamMemberService $teamMemberService)
    {
        $teams = $teamMemberService->enrolled($user, $teams);
        if ($teams) {
            foreach ($activities as $index => $activity) {
                if (null == $team = array_get($activity, 'team_id')) {
                    continue;
                }
                if (isset($teams[$team])) {
                    $activities[$index]['enrolled_team'] = $teams[$team];
                } else {
                    $activities[$index]['enrolled_team'] = false;
                }
            }
        }
        return $activities;
    }

    /**
     * list files of activity
     */
    public function listFiles(Request $request, ActivityService $activityService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
            'page'     => 'integer|min:1',
            'size'     => 'integer|min:1',
        ], [
            'activity.required' => '活动未指定',
            'activity.integer'  => '活动错误',
            'page.integer'      => '分页page错误',
            'page.min'          => '分页page错误',
            'size.integer'      => '分页size错误',
            'size.min'          => '分页size错误',
        ]);

        list($page, $size) = $this->sanePageAndSize(
            $request->input('page'),
            $request->input('size'));

        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null) {
                return $this->jsonException('活动非法');
            }

            list($total, $files) = $activityService->listFiles($activity, $page, $size);

            return $this->json(
                [
                    'pages' => PaginationUtil::count2Pages($total, $size),
                    'files' => array_map(function (ActivityFile $file) {
                        return [
                            'id'         => $file->getId(),
                            'name'       => $file->getName(),
                            'memo'       => $file->getMemo(),
                            'size'       => $file->getSize(),
                            'extension'  => $file->getExtension(),
                            'url'        => $file->getUrl(),
                            'created_at' => is_null($file->getCreatedAt()) ? null : date('Y-m-d H:i:s', strtotime($file->getCreatedAt())),
                        ];
                    }, $files),
                ]
            );
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    private function getActivityPlanDetailInList(ActivityPlan $activityPlan)
    {
        return [
            'id'         => $activityPlan->getId(),
            'plan_text'  => $activityPlan->getPlanText(),
            'begin_time' => $activityPlan->getBeginTime(),
            'end_time'   => $activityPlan->getEndTime(),
        ];
    }

    private function activitiesSort($activities)
    {
        if ($activities) {
            $activitiesEnd = [];
            $activitiesNotEnd = [];
            foreach ($activities as $activity) {
                if ($activity['sub_status'] != Activity::SUB_STATUS_END) {
                    $activitiesNotEnd[] = $activity;
                } else {
                    $activitiesEnd[] = $activity;
                }
            }
            usort($activitiesNotEnd, function ($a, $b) {
                $x = strtotime($a['publish_time']);
                $y = strtotime($b['publish_time']);
                if ($x == $y) {
                    return 0;
                }
                return ($x > $y) ? -1 : 1;
            });

            return array_merge($activitiesNotEnd, $activitiesEnd);
        }
        return $activities;
    }

    private function myActivitiesSort($activities)
    {
        if ($activities) {
            usort($activities, function ($a, $b) {
                $x = $a['sub_status'];
                $y = $b['sub_status'];
                if ($x == $y) {
                    return 0;
                }
                return ($x > $y) ? 1 : -1;
            });
        }
        return $activities;
    }
}
