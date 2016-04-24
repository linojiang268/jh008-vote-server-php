<?php

namespace Jihe\Http\Controllers\Api;

use Illuminate\Contracts\Auth\Guard;
use Jihe\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jihe\Services\ActivityService;
use Jihe\Services\ActivityMemberService;
use Validator;
use Jihe\Exceptions\ExceptionCode;

class ActivityMemberController extends Controller
{

    public function getGroupMemberLocation(Request $request, Guard $auth, ActivityMemberService $activityMemberService)
    {
        try {
            $requestParams = $request->only('activity_id', 'lat', 'lng');
            // validate request
            $validator = Validator::make($requestParams, [
                'activity_id' => 'required|integer',
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
            ], [
                'activity_id.required' => '活动未标识',
                'activity_id.integer' => '活动标识错误',
                'lat.required' => '未获取到纬度信息',
                'lat.float' => '纬度格式错误',
                'lng.required' => '未获取到经度信息',
                'lng.float' => '经度格式错误',
            ]);

            if ($validator->fails()) {
                return $this->jsonException($validator->errors()->first());
            }

            if (abs($requestParams['lat']) >= 90 || abs($requestParams['lat']) < 0.0001) {
                return $this->jsonException("错误的纬度");
            }

            if (abs($requestParams['lng']) >= 180 || abs($requestParams['lng']) < 0.0001) {
                return $this->jsonException("错误的经度");
            }

            $userId = $auth->user()->getAuthIdentifier();
            $myMemberInfo = $activityMemberService->getActivityMemberInfo($userId, $requestParams['activity_id']);
            if (empty($myMemberInfo) || $myMemberInfo['group_id'] == \Jihe\Models\ActivityMember::UNGROUPED) {
                return $this->json("没有查询到成员分组信息", ExceptionCode::ACTIVITY_NONE_GROUP_INFO);
            }

            if (!$activityMemberService->updateLocation($userId, $requestParams['activity_id'], $requestParams['lat'], $requestParams['lng'])) {
                //throw new \Exception("更新位置信息失败");
            }

            $groupMembers = $activityMemberService->allMemberOf($requestParams['activity_id'], $myMemberInfo['group_id']);
            $groupMembersLocationInfo = [];
            foreach ($groupMembers as $groupMember) {
                if ($groupMember['user_id'] != $userId) {
                    $groupMembersLocationInfo[] = [
                        'user_id' => $groupMember['user_id'],
                        'name' => $groupMember['name'],
                        'role' => $groupMember['role'],
                        'lat' => $groupMember['lat'],
                        'lng' => $groupMember['lng'],
                        'avatar_url' => $groupMember['user']['avatar_url'],
                    ];
                }
            }

            return $this->json(['members' => $groupMembersLocationInfo]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     *  member score activity
     */
    public function score(Request $request, Guard $auth, ActivityService $activityService, ActivityMemberService $activityMemberService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
            'score' => 'required|integer|min:1|max:5',
            'attributes' => 'array',
            'memo' => 'string'
        ], [
            'activity.required' => '活动未指定',
            'activity.integer' => '活动错误',
            'score.required' => '分数未指定',
            'score.integer' => '分数错误',
            'score.min' => '分数错误',
            'score.max' => '分数错误',
            'attributes' => '选项错误',
            'memo' => '备注错误',
        ]);

        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null) {
                return $this->jsonException('活动非法');
            }

            $activityMemberService->score(
                $activity,
                $auth->user()->getAuthIdentifier(),
                [
                    'score' => $request->input('score'),
                    'score_attributes' => $request->input('attributes'),
                    'score_memo' => $request->input('memo'),
                ]);
            return $this->json('评分成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    public function getActivityMembers(Request $request, ActivityMemberService $activityMemberService)
    {
        $requestParams = $request->only('activity_id', 'page', 'size');
        $validator = Validator::make($requestParams, [
            'activity_id' => 'required|integer',
            'page' => 'required|integer',
            'size' => 'integer',
        ], [
            'activity_id.required' => '活动未标识',
            'activity_id.integer' => '活动标识错误',
            'page.required' => '分页未标识',
            'page.integer' => '分页格式错误',
            'size.integer' => '分页大小格式错误',
        ]);

        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }
        list($page, $size) = $this->sanePageAndSize($requestParams['page'], $requestParams['size']);
        list($count, $members) = $activityMemberService->getActivityMemberList($requestParams['activity_id'], $page, $size);

        $pages = intval(ceil($count / $size));
        return $this->json(['pages' => $pages, 'members' => $members]);
    }


}
