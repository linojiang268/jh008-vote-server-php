<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\TeamGroup as TeamGroupEntity;
use Jihe\Entities\User as UserEntity;

class TeamMember extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'team_members';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'team_id', 'user_id', 'group_id', 'visibility', 'name', 'role', 'memo', 'status' ];

    public function team()
    {
        return $this->belongsTo(\Jihe\Models\Team::class, 'team_id', 'id');
    }

    /**
     * a team member should be a registered user
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\Jihe\Models\User::class, 'user_id', 'id');
    }

    /**
     * a team member can fill requirements required by the team
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requirements()
    {
        return $this->hasMany(\Jihe\Models\TeamMemberRequirement::class, 'member_id', 'id');
    }

    public function group()
    {
        return $this->belongsTo(\Jihe\Models\TeamGroup::class, 'group_id', 'id');
    }

    public function toEntity()
    {
        $member = new \Jihe\Entities\TeamMember();
        $member->setRole($this->role)
               ->setName($this->name)
               ->setVisibility($this->visibility)
               ->setMemo($this->memo)
               ->setStatus($this->status)
               ->setEntryTime($this->created_at->format('Y-m-d H:i:s'));

        if ($this->relationLoaded('team')) {
            $member->setTeam($this->team->toEntity());
        } else {
            $member->setTeam((new \Jihe\Entities\Team)->setId($this->team_id));
        }

        if ($this->relationLoaded('user')) {
            $member->setUser($this->user->toEntity());
        } else {
            $member->setUser((new UserEntity)->setId($this->user_id));
        }

        if ($this->relationLoaded('requirements')) {
            // $this->requirements can never be null, in case there is no requirements
            // an empty Collection will be produced by Eloquent
            $member->setRequirements(array_map(function (\Jihe\Models\TeamMemberRequirement $requirement) {
                return $requirement->toEntity();
            }, $this->requirements->all()));
        }

        if ($this->relationLoaded('group') && $this->group != null) {
            $member->setGroup($this->group->toEntity());
        } else {
            if ($this->group_id == TeamGroupEntity::UNGROUPED) {
                $member->setGroup(null);
            } else {
                $member->setGroup((new TeamGroupEntity)->setId($this->group_id));
            }
        }

        return $member;
    }
}
