<?php

namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\PromotionActivityManager as PromotionActivityManagerEntity;

class PromotionActivityManager extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'promotion_activity_managers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'activity_name',
        'activity_desc',
        'template_segment',
        'name',
        'password',
        'status',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    public function toEntity()
    {
        return (new PromotionActivityManagerEntity())
            ->setId($this->id)
            ->setActivityName($this->activity_name)
            ->setActivityDesc($this->activity_desc)
            ->setTemplateSegment($this->template_segment)
            ->setName($this->name)
            ->setPassword($this->password)
            ->setStatus($this->status);
    }
}
