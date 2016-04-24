<?php

namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Jihe\Entities\ActivityMember as ActivityMemberEntity;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\User as UserEntity;

class ActivityMember extends Model {

    use SoftDeletes;

    const UNGROUPED = 0;
    const ROLE_NORMAL = 0;
    const ROLE_MANAGER = 1;

    const CHECKIN_WAIT = 0;
    const CHECKIN_DONE = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'activity_members';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'activity_id',
        'user_id',
        'mobile',
        'name',
        'attrs',
        'group_id',
        'role',
    ];
    protected $hidden = ['deleted_at'];

    public function activity() {
        return $this->belongsTo(\Jihe\Models\Activity::class, 'activity_id', 'id');
    }

    public function user() {
        return $this->belongsTo(\Jihe\Models\User::class, 'user_id', 'id');
    }

    public function group() {
        return $this->belongsTo(\Jihe\Models\ActivityGroup::class, 'group_id', 'id');
    }

    public function checkins()
    {
        return $this->hasMany(\Jihe\Models\ActivityCheckIn::class, 'user_id', 'user_id');
    }

    public function toEntity()
    {
        $entity = (new ActivityMemberEntity())
            ->setId($this->id)
            ->setMobile($this->mobile)
            ->setName($this->name)
            ->setGroupId($this->group_id)
            ->setRole($this->role)
            ->setScore($this->score)
            ->setScoreMemo($this->score_memo)
            ->setCheckin($this->checkin);

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
        if ($this->score_attributes && ($scoreAttributes = json_decode($this->score_attributes, true))) {
            $entity->setScoreAttributes($scoreAttributes);
        } else {
            $entity->setScoreAttributes([]);
        }
        if ($this->relationLoaded('checkins') && $this->checkins) {
            $entity->setCheckins($this->checkins->map(function ($item, $key) {
                return $item->toEntity(); 
            }));
        } else {
            $entity->setCheckins(collect([]));
        }

        return $entity;
    }
}
