<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\TeamCertification as TeamCertificationEntity;
use Jihe\Entities\Team as TeamEntity;

class TeamCertification extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'team_certifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'team_id', 'certification_url', 'type' ];
    
    public function team()
    {
        return $this->belongsTo(\Jihe\Models\Team::class, 'team_id', 'id');
    }
    
    public function toEntity()
    {
        $teamCertification = (new TeamCertificationEntity())
                            ->setId($this->id)
                            ->setCertificationUrl($this->certification_url)
                            ->setType($this->type);

        if ($this->relationLoaded('team')) {
            $teamCertification->setTeam($this->team->toEntity());
        } else {
            $teamCertification->setTeam((new TeamEntity())->setId($this->team_id));
        }
        
        return $teamCertification;
    }
}
