<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\City as CityEntity;

class Team extends Model
{
    use SoftDeletes;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'teams';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
                            'creator_id',
                            'city_id',
                            'name',
                            'email',
                            'logo_url',
                            'address',
                            'contact_phone',
                            'contact',
                            'contact_hidden',
                            'introduction',
                            'certification',
                            'qr_code_url',
                            'join_type',
                            'status',
                            'activities_updated_at',
                            'members_updated_at',
                            'news_updated_at',
                            'albums_updated_at',
                            'notices_updated_at',
                            'tags',
                          ];
    
    public function creator()
    {
        return $this->belongsTo(\Jihe\Models\User::class, 'creator_id', 'id');
    }
    
    public function city()
    {
        return $this->belongsTo(\Jihe\Models\City::class, 'city_id', 'id');
    }

    public function members()
    {
        return $this->hasManyThrough(\Jihe\Models\User::class,
                                     \Jihe\Models\TeamMember::class,
                                     'team_id', 'user_id');
    }
    
    public function requirements()
    {
        return $this->hasMany(\Jihe\Models\TeamRequirement::class, 'team_id', 'id');
    }
    
    public function certifications()
    {
        return $this->hasMany(\Jihe\Models\TeamCertification::class, 'team_id', 'id');
    }

    public function toEntity()
    {
        $team = (new TeamEntity())
                ->setId($this->id)
                ->setName($this->name)
                ->setEmail($this->email)
                ->setLogoUrl($this->logo_url)
                ->setAddress($this->address)
                ->setContactPhone($this->contact_phone)
                ->setContact($this->contact)
                ->setContactHidden($this->contact_hidden)
                ->setIntroduction($this->introduction)
                ->setCertification($this->certification)
                ->setJoinType($this->join_type)
                ->setQrCodeUrl($this->qr_code_url)
                ->setStatus($this->status)
                ->setActivitiesUpdatedAt($this->activities_updated_at)
                ->setMembersUpdatedAt($this->members_updated_at)
                ->setNewsUpdatedAt($this->news_updated_at)
                ->setAlbumsUpdatedAt($this->albums_updated_at)
                ->setNoticesUpdatedAt($this->notices_updated_at)
                ->setTags($this->tags ? json_decode($this->tags) : null)
                ->setCreatedAt($this->created_at);

        if ($this->relationLoaded('city')) {
            $team->setCity($this->city->toEntity());
        } else {
            $team->setCity((new CityEntity())->setId($this->city_id));
        }

        if ($this->relationLoaded('creator')) {
            $team->setCreator($this->creator->toEntity());
        } else {
            $team->setCreator((new UserEntity)->setId($this->creator_id));
        }
        
        if (TeamEntity::JOIN_TYPE_VERIFY == $team->getJoinType() && $this->relationLoaded('requirements')) {
            $team->setRequirements(array_map(function ($requirement) {
                                                return $requirement->toEntity();
                                            }, $this->requirements->all()));
        } else {
            $team->setRequirements([]);
        }

        return $team;
    }
}
