<?php
namespace Jihe\Http\Controllers\Backstage;

use Jihe\Entities\TeamGroup;
use Jihe\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jihe\Services\TeamGroupService;

class TeamGroupController extends Controller
{
    /**
     * create a new group
     */
    public function createGroup(Request $request, TeamGroupService $groupService)
    {
        $this->validate($request, [
            'name'   => 'required|max:32',
        ], [
            'name.required'     => '社团分组未指定',
            'name.max'          => '社团分组名过长',
        ]);
        
        $team = $request->input('team');
        try {
            $groupService->add($request->input('name'), $team);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('添加社团分组成功');
    }

    /**
     * update team group
     */
    public function updateGroup(Request $request, TeamGroupService $groupService)
    {
        $this->validate($request, [
            'group'  => 'required|integer',
            'name'   => 'required|max:32',
        ], [
            'group.required'    => '社团分组未指定',
            'group.integer'     => '社团分组错误',
            'name.required'     => '社团分组名未指定',
            'name.max'          => '社团分组名过长',
        ]);
        
        $team = $request->input('team');
        if (null == $group = $groupService->getGroup($request->input('group'))) {
            return $this->jsonException('社团分组非法');
        }
        if ($group->getTeam()->getId() != $team->getId()) {
            return $this->jsonException('非法的社团分组');
        }

        try {
            $groupService->update($team->getId(), $group->getId(), $request->input('name'));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('更新社团分组成功');
    }

    /**
     * delete team group
     */
    public function deleteGroup(Request $request, TeamGroupService $groupService)
    {
        $this->validate($request, [
            'group'  => 'required|integer',
            'to'     => 'integer',
        ], [
            'group.required'    => '社团分组未指定',
            'group.integer'     => '社团分组错误',
            'to.integer'        => '团员移动的新组非法',
        ]);
        
        $team = $request->input('team');
        if (null == $group = $groupService->getGroup($request->input('group'))) {
            return $this->jsonException('社团分组非法');
        }
        if ($group->getTeam()->getId() != $team->getId()) {
            return $this->jsonException('非法的社团分组');
        }

        $to = TeamGroup::UNGROUPED;
        if ($request->has('to')) {
            if (null == $toGroup = $groupService->getGroup($request->input('to'))) {
                return $this->jsonException('社团分组非法');
            }
            if ($toGroup->getTeam()->getId() != $team->getId()) {
                return $this->jsonException('非法的分组');
            }

            $to = $toGroup->getId();
        }

        try {
            $groupService->delete($team->getId(), $group->getId(), $to);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('删除社团分组成功');
    }

    /**
     * list team groups
     */
    public function listGroups(Request $request, TeamGroupService $groupService)
    {
        $team = $request->input('team');

        try {
            return $this->json(['groups' => array_map(function (TeamGroup $group) {
                return [
                    'id'   => $group->getId(),
                    'name' => $group->getName(),
                ];
            }, $groupService->getGroupsOf($team))]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
}