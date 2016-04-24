<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\ActivityPlan as ActivityPlanEntity;

class ActivityPlan extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'activity_plan';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'activity_id',
        'begin_time',
        'end_time',
        'plan_text',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    public function toEntity() {
        $activityPlan = (new ActivityPlanEntity())
            ->setId($this->id)
            ->setActivityId($this->activity_id)
            ->setBeginTime($this->begin_time)
            ->setEndTime($this->end_time)
            ->setPlanText($this->plan_text);

        return $activityPlan;
    }
}