<?php

namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\ActivityEnrollPayment as ActivityEnrollPaymentEntity;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\User as UserEntity;

class ActivityEnrollPayment extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'activity_enroll_payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'activity_id',
        'user_id',
        'fee',
        'channel',
        'order_no',
        'trade_no',
        'payed_at',
        'status',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['payed_at', 'created_at', 'updated_at'];

    public function activity()
    {
        return $this->belongsTo(\Jihe\Models\Activity::class, 'activity_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(\Jihe\Models\User::class, 'user_id', 'id');
    }

    public function toEntity()
    {
        $entity = (new ActivityEnrollPaymentEntity())
            ->setId($this->id)
            ->setFee($this->fee)
            ->setChannel($this->channel)
            ->setOrderNo($this->order_no)
            ->setTradeNo($this->trade_no)
            ->setPayedAt($this->payed_at)
            ->setCreatedAt($this->created_at)
            ->setStatus($this->status);
        
        if ($this->relationLoaded('activity')) {
            $entity->setActivity($this->activity->toEntity());
        } else {
            $entity->setActivity((new ActivityEntity())->setId($this->activity_id));
        }

        if ($this->relationLoaded('user')) {
            $entity->setUser($this->user->toEntity());
        } else {
            $entity->setUser((new UserEntity())->setId($this->user_id));
        }

        return $entity;
    }
}
