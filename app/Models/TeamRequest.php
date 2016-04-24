<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use \Jihe\Entities\TeamRequest as TeamRequestEntity;
use \Jihe\Entities\Team as TeamEntity;
use \Jihe\Entities\City as CityEntity;
use \Jihe\Entities\User as UserEntity;
use Jihe\Entities\Jihe\Entities;

class TeamRequest extends Model
{
    /**
     * the result that user has not inspected
     *
     * @var int
     */
    const UN_READ = 0;
    
    /**
     * the result that user has inspected
     *
     * @var int
     */
    const READ = 1;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'team_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
                            'team_id',
                            'initiator_id',
                            'city_id',
                            'name',
                            'email',
                            'logo_url',
                            'address',
                            'contact_phone',
                            'contact',
                            'contact_hidden',
                            'introduction',
                            'status',
                          ];

    public function city()
    {
        return $this->belongsTo(\Jihe\Models\City::class, 'city_id', 'id');
    }
    
    public function team()
    {
        return $this->belongsTo(\Jihe\Models\Team::class, 'team_id', 'id');
    }
    
    public function initiator()
    {
        return $this->belongsTo(\Jihe\Models\User::class, 'initiator_id', 'id');
    }
    
    public function toEntity()
    {
        $teamRequest = (new TeamRequestEntity())
                        ->setId($this->id)
                        ->setName($this->name)
                        ->setEmail($this->email)
                        ->setLogoUrl($this->logo_url)
                        ->setAddress($this->address)
                        ->setContactPhone($this->contact_phone)
                        ->setContact($this->contact)
                        ->setContactHidden($this->contact_hidden)
                        ->setIntroduction($this->introduction)
                        ->setStatus($this->status)
                        ->setRead((self::READ == $this->read))
                        ->setMemo($this->memo)
                        ->setCreatedAt($this->created_at)
                        ->setUpdatedAt($this->updated_at);
        
        $teamRequest->setCity(
                $this->relationLoaded('city') ? $this->city->toEntity()
                                              : (new CityEntity())->setId($this->city_id));
        
        if (isset($this['team_id'])) {
            $teamRequest->setTeam(
                    $this->relationLoaded('team') ? $this->morphToTeamEntity($this->team)
                                                  : (new TeamEntity())->setId($this->team_id));
        }
        $teamRequest->setInitiator(
                $this->relationLoaded('initiator') ? $this->initiator->toEntity()
                                                   : (new UserEntity())->setId($this->initiator_id));
        
        return $teamRequest;
    }
    
    /**
     *
     * @param \Jihe\Models\Team $team
     * @return \Jihe\Entities\Team | null
     */
    private function morphToTeamEntity(Team $team = null)
    {
        return is_null($team) ? null : $team->toEntity();
    }
}
