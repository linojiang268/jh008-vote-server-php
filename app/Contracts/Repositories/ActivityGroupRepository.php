<?php

namespace Jihe\Contracts\Repositories;

interface ActivityGroupRepository
{
    /**
     * @param int $activityId
     * @param string $groupName
     * @return int group id
     */
    public function add($activityId, $groupName = null);

    /**
     * @param int $activityId
     * @param int $groupId
     * @return bool true if success
     */
    public function delete($activityId, $groupId);

    /**
     * @param int $activityId
     * @return bool true if success
     */
    
    public function exists($activityId, $groupId);
    
    public function clear($activityId);
    
    /**
     * @param int $activityId
     * @return array|null all groups of activity
     */
    public function all($activityId);

    /**
     * @param int $groupId which group id to be changed to
     * @param string $name group name
     * @return bool true if success
     */
    public function update($groupId, $name);
}
