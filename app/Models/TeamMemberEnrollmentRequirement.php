<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\TeamMemberEnrollmentRequirement as TeamMemberEnrollmentRequirementEntity;
use Jihe\Entities\TeamRequirement as TeamRequirementEntity;
use Jihe\Entities\TeamMemberEnrollmentRequest as TeamMemberEnrollmentRequestEntity;

class TeamMemberEnrollmentRequirement extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'team_member_enrollment_requirements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'request_id', 'requirement_id', 'value' ];

    public function requirement()
    {
        return $this->belongsTo(\Jihe\Models\TeamRequirement::class, 'requirement_id', 'id');
    }

    public function request()
    {
        return $this->belongsTo(\Jihe\Models\TeamMemberEnrollmentRequest::class, 'request_id', 'id');
    }

    public function toEntity()
    {
        $requirement = (new TeamMemberEnrollmentRequirementEntity)
            ->setId($this->id)
            ->setValue($this->value);

        if ($this->relationLoaded('requirement')) {
            $requirement->setRequirement($this->requirement->toEntity());
        } else {
            $requirement->setRequirement((new TeamRequirementEntity)->setId($this->requirement_id));
        }

        if ($this->relationLoaded('request_id')) {
            $requirement->setRequest($this->request->toEntity());
        } else {
            $requirement->setRequest((new TeamMemberEnrollmentRequestEntity)->setId($this->request_id));
        }

        return $requirement;
    }
}
