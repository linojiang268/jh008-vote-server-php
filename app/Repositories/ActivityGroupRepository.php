<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\ActivityGroupRepository as ActivityGroupRepositoryContract;
use Jihe\Models\ActivityGroup;

class ActivityGroupRepository implements ActivityGroupRepositoryContract
{
    /**
     *
     * @param array $groupModels  array of \Jihe\Models\ActivityGroup
     * @return array              array of \Jihe\Entities\ActivityGroup
     */
    private function convertToEntities($groupModels)
    {
        $entities = [];
        
        foreach ($groupModels as $groupModel) {
            array_push($entities, $groupModel->convertToEntity());
        }
        
        return $entities;
    }

    public function add($activityId, $groupName = null)
    {
        return ActivityGroup::create([
                    'activity_id'   => $activityId,
                    'name'          => $groupName != null ? strval($groupName) : '',
                ])->id;
    }

    public function delete($activityId, $groupId)
    {
        return ActivityGroup::where('id', $groupId)
                        ->where('activity_id', $activityId)
                        ->delete();
    }
    
    public function clear($activityId)
    {
        return ActivityGroup::where('activity_id', $activityId)
                        ->delete();
    }
    
    public function all($activityId)
    {
        return ActivityGroup::where('activity_id', $activityId)
                            ->get()
                            ->toArray();
    }

    public function update($groupId, $name)
    {
        return TeamGroup::where('id', $groupId)
                             ->update([
                                 'name' => $name,
                             ]);
    }

    public function exists($activityId, $groupId)
    {
        return null != ActivityGroup::where('id', $groupId)
                        ->where('activity_id', $activityId)
                        ->value('id');
    }
}
