<?php
namespace Jihe\Contracts\Repositories;

interface PromotionActivityManagerRepository
{
    /**
     * Find activity by activity name
     *
     * @param string $activityName.
     *
     * @return \Jihe\Entities\PromotionActivityManager. 
     */
    public function findOneActivity($activityName);

    /**
     * Find a manager by name
     *
     * @param string $name  manager name
     *
     * @return \Jihe\Entities\PromotionActivityManager.
     */
    public function findByName($name);
}
