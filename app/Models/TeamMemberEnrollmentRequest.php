<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\TeamGroup as TeamGroupEntity;
use Jihe\Entities\TeamMemberEnrollmentRequest as TeamMemberEnrollmentRequestEntity;
use Jihe\Entities\User as UserEntity;

class TeamMemberEnrollmentRequest extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'team_member_enrollment_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'team_id', 'initiator_id', 'name', 'group_id', 'memo', 'status', 'reason' ];

    public function team()
    {
        return $this->belongsTo(\Jihe\Models\Team::class, 'team_id', 'id');
    }

    /**
     * who initiates this request
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function initiator()
    {
        return $this->belongsTo(\Jihe\Models\User::class, 'initiator_id', 'id');
    }

    public function group()
    {
        return $this->belongsTo(\Jihe\Models\TeamGroup::class, 'group_id', 'id');
    }

    /**
     * a team member can fill requirements required by the team
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requirements()
    {
        return $this->hasMany(\Jihe\Models\TeamMemberEnrollmentRequirement::class, 'request_id', 'id');
    }

    public function toEntity()
    {
        $request = new TeamMemberEnrollmentRequestEntity;
        $request->setId($this->id)
            ->setMemo($this->memo)
            ->setName($this->name)
            ->setStatus($this->status)
            ->setReason($this->reason);

        if ($this->relationLoaded('team')) {
            $request->setTeam($this->team->toEntity());
        } else {
            $request->setTeam((new TeamEntity)->setId($this->team_id));
        }

        if ($this->relationLoaded('initiator')) {
            $request->setInitiator($this->initiator->toEntity());
        } else {
            $request->setInitiator((new UserEntity)->setId($this->initiator_id));
        }

        if ($this->relationLoaded('group')) {
            if ($this->group != null) {
                $request->setGroup(null);
            } else {
                $request->setGroup($this->group->toEntity());
            }
        } else {
            if ($this->group_id == TeamGroupEntity::UNGROUPED) {
                $request->setGroup(null);
            } else {
                $request->setGroup((new TeamGroupEntity)->setId($this->group_id));
            }
        }

        if ($this->relationLoaded('requirements')) {
            // $this->requirements can never be null, in case there is no requirements
            // an empty Collection will be produced by Eloquent
            $request->setRequirements(array_map(function (\Jihe\Models\TeamMemberEnrollmentRequirement $requirement) {
                return $requirement->toEntity();
            }, $this->requirements->all()));
        }

        return $request;
    }
}
