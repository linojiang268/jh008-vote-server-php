<?php

namespace Jihe\Services;

use Jihe\Contracts\Repositories\ActivityGroupRepository;
use Jihe\Services\ActivityMemberService;
use Jihe\Services\ActivityService;
use Jihe\Contracts\Services\Storage\StorageService;

class ActivityGroupService {

    private $activityGroupRepository;
    private $activityMemberService;
    private $storageService;
    private $activityService;

    public function __construct(ActivityGroupRepository $activityGroupRepository,
            ActivityMemberService $activityMemberService, StorageService $storageService,
            ActivityService $activityService) {
        $this->activityGroupRepository = $activityGroupRepository;
        $this->activityMemberService = $activityMemberService;
        $this->storageService = $storageService;
        $this->activityService = $activityService;
    }

    private function hasPermission($creatorId, $activityId) {
        return $this->activityService->checkActivityOwner($activityId, $creatorId);
    }

    public function generateGroups($activityId, $groups) {
        //clear first
        $this->activityGroupRepository->clear($activityId);

        $groupIds = [];
        for ($groupNo = 1; $groupNo <= $groups; $groupNo++) {
            $groupIds[] = $this->activityGroupRepository->add($activityId, $groupNo);
        }
        return $groupIds;
    }

    public function groupMembers($creatorId, $activityId, $groups) {
        //check permition
        if (!$this->hasPermission($creatorId, $activityId)) {
            throw new \Exception('你没有权限进行此操作');
        }

        //get all members of this activity
        $members = $this->activityMemberService->allMemberOf($activityId, NULL);
        $membersCount = count($members);
        $avg = intval($membersCount / $groups);
        if ($avg == 0) {
            throw new \Exception('人数不足，无法分组！');
        }
        $overflow = $membersCount % $groups;

        $memberIdset = array_column($members, 'id');
        $sliceGroups = [];
        $offset = 0;
        $length = $avg + (($overflow > 0) ? 1 : 0);
        for ($i = 0; $i < $groups; $i++, $offset += $length, $length = $avg + (($overflow > $i) ? 1 : 0)) {
            $sliceGroups[] = array_slice($memberIdset, $offset, $length);
        }

        //reset group id
        $this->activityMemberService->resetGroupId($activityId);

        //create groups
        $currentGroups = $this->generateGroups($activityId, $groups);

        //set group id
        for ($n = 0; $n < $groups; $n++) {
            $this->activityMemberService->setGroup($activityId, $sliceGroups[$n], $currentGroups[$n]);
        }
    }

    public function getGroups($activityId) {
        $groups = $this->activityGroupRepository->all($activityId);

        $membersUngrouped = $this->activityMemberService->membersNotInGroupIds($activityId, array_column($groups, 'id'));
        $total = count($membersUngrouped);
        $membersGrouped = [];
        foreach ($groups as $group) {
            $members = $this->activityMemberService->allMemberOf($activityId, $group['id']);
            $total += count($members);
            $membersGrouped[] = array_merge($group, ['members' => $members]);
        }

        return ['total' => $total,
            'ungrouped' => $membersUngrouped,
            'grouped' => $membersGrouped];
    }

    public function deleteGroup($creatorId, $activityId, $groupId, $forceDelete = false) {
        if (!$this->hasPermission($creatorId, $activityId)) {
            throw new \Exception('你没有权限进行此操作');
        }

        if (!$this->activityGroupRepository->exists($activityId, $groupId)) {
            throw new \Exception('此组不存在');
        }

        if (!$forceDelete) {
            $totalMembers = $this->activityMemberService->totalMemberOf($activityId, $groupId);
            if ($totalMembers > 0) {
                throw new \Exception('该组还有成员，不允许删除该组！');
            }
        }

        return $this->activityGroupRepository->delete($activityId, $groupId);
    }

}
