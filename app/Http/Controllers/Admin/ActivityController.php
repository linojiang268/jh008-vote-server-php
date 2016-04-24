<?php
namespace Jihe\Http\Controllers\Admin;

use Jihe\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jihe\Services\ActivityService;
use Illuminate\Support\Facades\Auth;
use Jihe\Entities\Activity;
use Jihe\Services\ActivityApplicantService;
use Jihe\Services\TeamMemberService;
use Jihe\Utils\StringUtil;
use Jihe\Services\PushService;
use Jihe\Entities\Message;

class ActivityController extends Controller
{
    /**
     * update activity
     */
    public function update(Request $request, ActivityService $activityService)
    {
        // validate reques
        $this->validate($request, [
            'id'                => 'required|integer',
            'tags'              => 'string',
        ], [
            'id.required'             => '活动序号未填写',
            'id.integer'              => '活动序号格式错误',
            'tags.string'             => '标签格式错误',
        ]);

        try {
            $params = $request->only('id', 'tags');
            if (!$activityService->checkActivityExists($params['id'])) {
                return $this->jsonException('该活动不存在!');
            }
            $params = array_filter($params);
            $params = $this->checkUpdateParams($params);

            $ret = $activityService->updateActivity($params['id'], Auth::user()->id, $params);
            if (!$ret) {
                return $this->jsonException('活动创建失败!');
            }
            return $this->json(['result' => $ret]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

    }

    private function checkUpdateParams($params)
    {
        if ($params) {
            if (isset($params['location'])) {
                $params['location'] = StringUtil::safeJsonDecode($params['location']);
                if ($params['location'] === false) {
                    return $this->jsonException('活动地点坐标错误!');
                }
            }
            if (isset($params['roadmap'])) {
                $params['roadmap'] = StringUtil::safeJsonDecode($params['roadmap'], true);
                if ($params['roadmap'] === false) {
                    return $this->jsonException('活动地点坐标错误!');
                }
            }
            if (isset($params['images_url'])) {
                if (StringUtil::safeJsonDecode($params['images_url']) === false) {
                    return $this->jsonException('轮播图错误!');
                }
            }
            if (isset($params['enroll_attrs'])) {
                if (StringUtil::safeJsonDecode($params['enroll_attrs']) === false) {
                    return $this->jsonException('报名申请资料字段错误!');
                }
            }
            if (isset($params['tags'])) {
                if (StringUtil::safeJsonDecode($params['tags']) === false) {
                    return $this->jsonException('标签字段错误!');
                }
            }
        }
        return $params;
    }

    /**
     * get activity detail by id
     */
    public function getActivityById(Request $request, ActivityService $activityService)
    {
        // validate request
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动未填写',
            'activity.integer'  => '活动格式错误',
        ]);

        try {
            $param = $request->only('activity');
            $activity = $activityService->findAllStatusActivitiesByIds($param['activity'], Auth::user()->id);
            return $this->json([
                'activity' => $this->getActivityDetail($activity),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * delete activity by id
     */
    public function deleteActivityById(Request $request, ActivityService $activityService)
    {
        // validate reques
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动序号未填写',
            'activity.integer'  => '活动序号格式错误',
        ]);

        try {
            $param = $request->only('activity');
            if (!$activityService->checkActivityExists($param['activity'])) {
                return $this->jsonException('该活动不存在!');
            }

            $ret = $activityService->activityDelete($param['activity'], Auth::user()->id);

            return $this->json(['result' => $ret]);

        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * restore activity by id
     */
    public function restoreActivityById(Request $request, ActivityService $activityService)
    {
        // validate reques
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动序号未填写',
            'activity.integer'  => '活动序号格式错误',
        ]);

        try {
            $param = $request->only('activity');
            if (!$activityService->checkActivityExists($param['activity'])) {
                return $this->jsonException('该活动不存在!');
            }
            $ret = $activityService->activityRestore($param['activity'], Auth::user()->id);

            return $this->json(['result' => $ret]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    public function searchActivityTitleByTagsAndStatus(Request $request,
                                                    ActivityService $activityService,
                                                    ActivityApplicantService $activityApplicantService,
                                                    TeamMemberService $teamMemberService)
    {
        $this->validate($request, [
            'keyword'   => 'string',
            'tags'   => 'integer',
            'status' => 'integer',
            'page'      => 'integer',
            'size'      => 'integer',
        ], [
            'keyword.string'    => '标题关键字格式错误',
            'tags.integer'   => '未添加标签参数格式错误',
            'status.integer' => '已停用参数格式错误',
            'page.integer'      => '分页page错误',
            'size.integer'      => '分页size错误',
        ]);
        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        try {
            list($total, $activities) = $activityService->searchActivityTitleByTagsAndStatus(
                array_get($request->all(), 'keyword', null),
                array_get($request->all(), 'tags', 0),
                array_get($request->all(), 'status', 0),
                $page,
                $size
            );
            $activities = $this->assembleData($activities, $activityApplicantService, $teamMemberService);
            return $this->json([
                'total_num'  => $total,
                'activities' => $activities,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /*
     * assemble list data
     */
    private function assembleData($activities,
                                  ActivityApplicantService $activityApplicantService,
                                  TeamMemberService $teamMemberService)
    {
        $assembledActivities = array_map(function (Activity $activity) {
            return $this->getActivityDetailInList($activity);
        },
            $activities);
        $assembledActivities = $this->setRelatedData($assembledActivities, $activityApplicantService, $teamMemberService);

        return $assembledActivities;
    }

    /*
     * get activity detail to array
     */
    private function getActivityDetail($activity)
    {
        if (empty($activity)) {
            return [];
        }
        $tmp = $this->getActivityDetailInList($activity);
        $iteam = [
            'contact'           => $activity->getContact(),
            'telephone'         => $activity->getTelephone(),
            'detail'            => $activity->getDetail(),
            'auditing'          => $activity->getAuditing(),
            'images_url'        => $activity->getImagesUrl(),
            'enroll_begin_time' => $activity->getEnrollBeginTime(),
            'enroll_end_time'   => $activity->getEnrollEndTime(),
            'enroll_type'       => $activity->getEnrollType(),
            'enroll_limit'      => $activity->getEnrollLimit(),
            'enroll_attrs'      => $activity->getEnrollAttrs(),
            'roadmap'           => $activity->getRoadmap(),
            'update_step'       => $activity->getUpdateStep(),
            'status'            => $activity->getStatus(),
        ];

        return array_merge($tmp, $iteam);
    }

    /**
     * list activity fields data to array
     */
    private function getActivityDetailInList($activity)
    {
        $tmp = $this->getActivitySimpleDetailInList($activity);
        $Item = [
            'team_id'         => $activity->getTeam()->getId(),
            'sub_status'      => $activity->getSubStatus(),
            'cover_url'       => $activity->getCoverUrl(),
            'qr_code_url'     => $activity->getQrCodeUrl(),
            'address'         => $activity->getaddress(),
            'brief_address'   => $activity->getBriefAddress(),
            'enroll_fee_type' => $activity->getEnrollFeeType(),
            'enroll_fee'      => number_format($activity->getEnrollFee() / 100, 2),
            'essence'         => $activity->getEssence(),
            'city'            => [
                'id'   => $activity->getCity()->getId(),
                'name' => $activity->getCity()->getName(),
            ],
            'team'            => [
                'id'           => $activity->getTeam()->getId(),
                'name'         => $activity->getTeam()->getName(),
                'logo_url'     => $activity->getTeam()->getLogoUrl(),
                'introduction' => $activity->getTeam()->getIntroduction(),
            ],
            'location'        => $activity->getLocation(),
            'status'          => $activity->getStatus(),
            'tags'          => $activity->getTags(),
            'enrolled_team'   => false,
            'enrolled_num'    => 0,
        ];
        return array_merge($tmp, $Item);
    }

    /**
     * list simple activity fields data to array
     */
    private function getActivitySimpleDetailInList($activity)
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

    /**
     *  set related data
     *
     * @param                          $activities
     * @param ActivityApplicantService $activityApplicantService
     * @param TeamMemberService        $teamMemberService
     *
     * @return array
     */
    public function setRelatedData($activities,
                                   ActivityApplicantService $activityApplicantService)
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
        $activities = $this->setEnrolledTotal($activities, $activityIds, $activityApplicantService);

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

    private function setEnrolledTotal($activities, $activityIds, ActivityApplicantService $activityApplicantService)
    {
        $enrolledCounts = $activityApplicantService->getActivityApplicantsCount($activityIds);
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

    /*
     * push upgrade msg to platform
     */
    public function pushPlatformUpgrade(Request $request, PushService $pushService)
    {
        $this->validate($request, [
            'platform'   => 'required|string',
            'content'    => 'required|string',
            'compulsory' => 'required|boolean',
            'version'    => 'required|string',
            'url'        => 'string',
        ], [
            'platform.required'   => '推送平台未填写',
            'platform.string'     => '推送平台类型错误',
            'content.required'    => '推送内容未填写',
            'content.string'      => '推送内容类型错误',
            'compulsory.required' => '升级约束未填写',
            'compulsory.string'   => '升级约束类型错误',
            'version.required'    => '版本号未填写',
            'version.string'      => '版本号类型错误',
            'url.required'        => '升级链接未填写',
            'url.string'          => '升级链接类型错误',
        ]);

        try {
            $platform = $request->input('platform');
            $content = $request->input('content');
            $compulsory = $request->input('compulsory');
            $version = $request->input('version');
            $url = array_get($request->all(), 'url', null);

            if(!in_array($platform, ['ios', 'android'])){
                return $this->jsonException('非法的平台信息');
            }
            if($platform == 'ios'){
                $topic = PushService::TO_IOS_TOPIC;
            }else{
                $topic = PushService::TO_ANDROID_TOPIC;
            }

            $msg = [
                'content' => $content,
                'type'    => Message::TYPE_VERSION_UPGRADE,
                'attributes' => [
                    'compulsory' => $compulsory,
                    'version' => $version,
                ]
            ];
            if($url != null){
                $msg['attributes']['url'] = $url;
            }
            $pushService->pushToTopic($topic, $msg);

            return $this->json(['result' => true]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

}