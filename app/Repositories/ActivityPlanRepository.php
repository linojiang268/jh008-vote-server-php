<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\ActivityPlanRepository as ActivityPlanRepositoryContract;
use Jihe\Entities\ActivityPlan as ActivityPlanEntity;
use Jihe\Models\ActivityPlan;


class ActivityPlanRepository implements ActivityPlanRepositoryContract
{
    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::add()
     */
    public function add($activityPlan)
    {
        if ($activityPlan == null) {
            return 0;
        }
        return ActivityPlan::create($activityPlan)->id;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::updateOnce()
     */
    public function updateOnce($id, $activityPlan)
    {
        $activityPlanDb = ActivityPlan::where('id', $id)->first();
        if (null == $activityPlanDb || empty($activityPlan)) {
            return false;
        }
        if ($activityPlan) {
            foreach ($activityPlan as $field => $value) {
                $activityPlanDb->$field = $value;
            }
        }

        return $activityPlanDb->save();
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::updateMultiple()
     */
    public function updateMultiple($conditions, $activityPlan)
    {
        $query = ActivityPlan::whereNotNull('id');
        if ($conditions) {
            foreach ($conditions as $key => $value) {
                if (is_array($value) && count($value) == 2) {
                    $query->where($key, $value[0], $value[1]);
                } elseif (is_string($value)) {
                    $query->where($key, $value);
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }

        return $query->update($activityPlan) > 0 ? true : false;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::deleteActivityPlanById()
     */
    public function deleteActivityPlanById($id)
    {
        return $this->updateOnce($id, [
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::deleteActivityPlanByActivityId()
     */
    public function deleteActivityPlanByActivityId($activityId)
    {
        $ret = $this->updateMultiple(['activity_id' => ['=', $activityId]], [
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);

        return $ret;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findActivityPlanById()
     */
    public function findActivityPlanById($id)
    {
        $activityPlan = ActivityPlan::where('id', $id)->whereNull('deleted_at')->first();
        if (empty($activityPlan)) {
            return null;
        }

        return $this->convertToEntity($activityPlan);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findActivityPlanByActivityId()
     */
    public function findActivityPlanByActivityId($activityId)
    {
        $query = ActivityPlan::where('activity_id', $activityId)->whereNull('deleted_at');
        $count = $query->count();
        $query->orderBy('begin_time', 'asc');
        $activityPlans = $query->get()->all();
        $activityPlans = array_map([$this, 'convertToEntity'], $activityPlans);

        return [$count, $activityPlans];
    }

    /**
     * {@inheritdoc}
     * @return ActivityPlanEntity | null
     */
    private function convertToEntity(ActivityPlan $activityPlan)
    {
        if ($activityPlan == null) {
            return null;
        }
        return $activityPlan->toEntity();
    }
}