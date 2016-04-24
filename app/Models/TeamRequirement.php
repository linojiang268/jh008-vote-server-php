<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\TeamRequirement as TeamRequirementEntity;
use Jihe\Entities\Team as TeamEntity;

class TeamRequirement extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'team_requirements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'team_id', 'requirement' ];
    
    public function team()
    {
        return $this->belongsTo(\Jihe\Models\Team::class, 'team_id', 'id');
    }
    
    public function toEntity()
    {
        $teamRequirement = (new TeamRequirementEntity())
                            ->setId($this->id)
                            ->setRequirement($this->requirement);

        if ($this->relationLoaded('team')) {
            $teamRequirement->setTeam($this->team->toEntity());
        } else {
            $teamRequirement->setTeam((new TeamEntity())->setId($this->team_id));
        }
        
        return $teamRequirement;
    }
}
