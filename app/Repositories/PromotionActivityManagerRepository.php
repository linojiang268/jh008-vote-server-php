<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\PromotionActivityManagerRepository as
    PromotionActivityManagerRepositoryContract;
use Jihe\Models\PromotionActivityManager;
use Jihe\Entities\PromotionActivityManager as PromotionActivityManagerEntity;

class PromotionActivityManagerRepository implements
    PromotionActivityManagerRepositoryContract
{
    /**
     * (non-PHPDoc)
     *
     * @see \Jihe\Contracts\Repositories\PromotionActivityManagerRepository::isActivityExists()
     */
    public function findOneActivity($activityName)
    {
        $manager = PromotionActivityManager::where('activity_name', $activityName) 
                                           ->first();
        
        return $this->convertToEntity($manager);
    }

    /**
     * (non-PHPDoc)
     *
     * @see \Jihe\Contracts\Repositories\PromotionActivityManagerRepository::findByName()
     */
    public function findByName($name)
    {
        $manager = PromotionActivityManager::where([
            'name'      => $name,
            'status'    => PromotionActivityManagerEntity::STATUS_NORMAL,
        ])->first();

        return $this->convertToEntity($manager);
    }

    private function convertToEntity($promotionActivityManager)
    {
        return $promotionActivityManager ? $promotionActivityManager->toEntity() : null;
    }
}
