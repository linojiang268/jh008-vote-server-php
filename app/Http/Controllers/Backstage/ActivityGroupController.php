<?php

namespace Jihe\Http\Controllers\Backstage;

use Jihe\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jihe\Services\ActivityGroupService;
use Validator;
use Illuminate\Contracts\Auth\Guard;

class ActivityGroupController extends Controller
{
    
    public function createGroups(Request $request, Guard $auth, ActivityGroupService $activityGroupService)
    {
        $requestParams = $request->only('activity_id','groups');
        // validate request
        $validator = Validator::make($requestParams, [
                'activity_id'	=> 'required|integer',
                'groups'        => 'required|integer',
        ], [
                'activity_id.required'   => '活动未标识',
                'activity_id.integer'    => '活动标识错误',
                'groups.required'        => '请填写分组数',
                'groups.integer'         => '分组数格式错误',
        ]);
        
        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }
        
        list($activityId, $groups) = [$requestParams['activity_id'], $requestParams['groups']];

        try {
            $activityGroupService->groupMembers($auth->user()->getAuthIdentifier(), $activityId, $groups);
            return $this->json("分组活动成员成功");
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    public function getGroups(Request $request, ActivityGroupService $activityGroupService)
    {
        $requestParams = $request->only('activity_id');
        // validate request
        $validator = Validator::make($requestParams, [
                'activity_id'	=> 'required|integer',
        ], [
                'activity_id.required'   => '活动未标识',
                'activity_id.integer'    => '活动标识错误',
        ]);
        
        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }
        
        $activityId = $requestParams['activity_id'];
        try {
            $groups = $activityGroupService->getGroups($activityId);
            return $this->json($groups);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
   
    public function deleteGroup(Request $request, Guard $auth, ActivityGroupService $activityGroupService)
    {
        $requestParams = $request->only('activity_id', 'group_id');
        // validate request
        $validator = Validator::make($requestParams, [
                'activity_id'	=> 'required|integer',
                'group_id'	=> 'required|integer',
        ], [
                'activity_id.required'   => '活动未标识',
                'activity_id.integer'    => '活动标识错误',
                'group_id.required'      => '组号未标识',
                'group_id.integer'       => '组号格式错误',
        ]);
        
        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }
        
        list($activityId, $groupId) = [$requestParams['activity_id'], $requestParams['group_id']];
        try {
            $activityGroupService->deleteGroup($auth->user()->getAuthIdentifier(), $activityId, $groupId);
            
            return $this->json("删除分组成功");
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    
}