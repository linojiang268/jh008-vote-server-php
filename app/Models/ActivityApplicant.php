<?php

namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\ActivityApplicant as ActivityApplicantEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\Activity as ActivityEntity;

class ActivityApplicant extends Model {

    const STATUS_INVALID = -1;
    const STATUS_NORMAL = 0;
    const STATUS_AUDITING = 1;
    const STATUS_PAY = 2;
    const STATUS_SUCCESS = 3;
    
    const STATUS_PAY_EXPIRED = 4;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'activity_applicants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_no',
        'activity_id',
        'user_id',
        'mobile',
        'name',
        'attrs',
        'expire_at',
        'channel',
        'remark',
        'status',
    ];

    public function user() {
        return $this->belongsTo(\Jihe\Models\User::class, 'user_id', 'id');
    }

    public function activity() {
        return $this->belongsTo(\Jihe\Models\Activity::class, 'activity_id', 'id');
    }

    public function toEntity()
    {
        $entity = (new ActivityApplicantEntity())
            ->setId($this->id)
            ->setName($this->name)
            ->setOrderNo($this->order_no)
            ->setMobile($this->mobile)
            ->setChannel($this->channel)
            ->setRemark($this->remark)
            ->setStatus($this->status)
            ->setApplicantTime($this->created_at);

        if ($this->relationLoaded('activity') && $this->activity) {
            $entity->setActivity($this->activity->toEntity());
        } else {
            $entity->setActivity((new ActivityEntity())->setId($this->activity_id));
        }
        if ($this->relationLoaded('user') && $this->user) {
            $entity->setUser($this->user->toEntity());
        } else {
            $entity->setUser((new UserEntity())->setId($this->user_id));
        }

        if ($this->attrs && ($attrs = json_decode($this->attrs, true))) {
            $entity->setAttrs($attrs);
        } else {
            $entity->setAttrs([]);
        }

        $this->expire_at ? $entity->setExpireAt(new \DateTime($this->expire_at)) : null;

        return $entity;
    }

    public static function parseStatusToString($status) {
        switch ($status) {
            case ActivityApplicant::STATUS_INVALID:
                return '失效';
            case ActivityApplicant::STATUS_NORMAL:
                return '初始';
            case ActivityApplicant::STATUS_AUDITING:
                return '审核中';
            case ActivityApplicant::STATUS_PAY:
                return '待付款';
            case ActivityApplicant::STATUS_SUCCESS:
                return '成功报名';
            default :
                return '未知';
        }
    }

}
