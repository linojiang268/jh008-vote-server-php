<?php
namespace Jihe\Contracts\Repositories;

interface ActivityPlanRepository
{
    /**
     * add one activity plan
     *
     * @param $activityPlan
     *
     * return int insert id
     */
    public function add($activityPlan);

    /**
     * update activity plan
     *
     * @param int   $id                              activity plan id
     * @param array $activityPlan                    According to the structure
     *                                               of the activity plan data
     *
     * @return  boolean
     */
    public function updateOnce($id, $activityPlan);

    /**
     * update activity plan multiple
     *
     * @param array $conditions   update conditions
     * @param array $activityPlan update fields
     *
     * @return mixed
     */
    public function updateMultiple($conditions, $activityPlan);

    /**
     * @param  int $id activity plan id
     *
     * @return Boolean
     */
    public function deleteActivityPlanById($id);

    /**
     * @param  int $id activity id
     *
     * @return Boolean
     */
    public function deleteActivityPlanByActivityId($activityId);

    /**
     * get activity plan entity by given id of activity plan
     *
     * @param int $id id of activity plan
     *
     * @return \Jihe\Entities\activityPlan
     */
    public function findActivityPlanById($id);

    /**
     * get activity plan entity by given id of activity plan
     *
     * @param int $id id of activity plan
     *
     * @return array
     */
    public function findActivityPlanByActivityId($activityId);
}