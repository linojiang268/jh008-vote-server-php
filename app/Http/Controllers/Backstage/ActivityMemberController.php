<?php

namespace Jihe\Http\Controllers\Backstage;

use Jihe\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jihe\Services\ActivityMemberService;
use Jihe\Services\ActivityService;
use Validator;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Auth;

class ActivityMemberController extends Controller {

    public function setGroup(Request $request, Guard $auth, ActivityMemberService $activityMemberService) {
        $requestParams = $request->only('activity_id', 'member_ids', 'group_id');
        // validate request
        $validator = Validator::make($requestParams, [
                    'activity_id' => 'required|integer',
                    'member_ids' => 'required',
                    'group_id' => 'required|integer',
                        ], [
                    'activity_id.required' => '活动未标识',
                    'activity_id.integer' => '活动标识错误',
                    'member_ids.required' => '未指定成员ID',
                    'group_id.required' => '请填写分组数',
                    'group_id.integer' => '分组数格式错误',
        ]);

        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }

        list($activityId, $memberIds, $groupId) = [$requestParams['activity_id'], explode(',', $requestParams['member_ids']),
            $requestParams['group_id']];
        try {
            if ($memberIds && count($memberIds) > 0) {
                array_walk($memberIds, function($memberId) {
                    if (!is_numeric($memberId) || $memberId <= 0) {
                        throw new \Exception('活动成员ID不合法');
                    }
                });
            } else {
                throw new \Exception('活动成员ID不合法');
            }

            $activityMemberService->setGroup($activityId, $memberIds, $groupId);
            return $this->json("设置活动成员分组成功");
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * export members
     */
    public function exportMembers(Request $request, ActivityService $activityService, ActivityMemberService $memberService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动未标识',
            'activity.integer' => '活动标识错误',
        ]);
        $activityId = $request->input('activity');
        try {
            // check activity
            $activity = $activityService->getActivityById($activityId, Auth::user()->id);
        } catch (\Exception $ex) {
            return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('非法操作');
        }
        ob_start();
        $memberService->exportMembers($activity);
        return response()->make(ob_get_clean())
            ->header('Content-Type',        'application/vnd.ms-excel')
            ->header('Content-disposition', 'attachment; filename= members.xls');
    }

}
