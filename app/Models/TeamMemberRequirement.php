<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\TeamMember as TeamMemberEntity;
use Jihe\Entities\TeamRequirement as TeamRequirementEntity;

class TeamMemberRequirement extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'team_member_requirements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'member_id', 'requirement_id', 'value' ];

    public function requirement()
    {
        return $this->belongsTo(\Jihe\Models\TeamRequirement::class, 'requirement_id', 'id');
    }

    public function member()
    {
        return $this->belongsTo(\Jihe\Models\TeamMember::class, 'member_id', 'id');
    }

    public function toEntity()
    {
        $requirement = (new \Jihe\Entities\TeamMemberRequirement)
            ->setId($this->id)
            ->setValue($this->value);

        if ($this->relationLoaded('requirement')) {
            $requirement->setRequirement($this->requirement->toEntity());
        } else {
            $requirement->setRequirement((new TeamRequirementEntity)->setId($this->requirement_id));
        }

        if ($this->relationLoaded('member')) {
            $requirement->setMember($this->member->toEntity());
        } else {
            $requirement->setMember((new TeamMemberEntity())->setId($this->member_id));
        }

        return $requirement;
    }
}
