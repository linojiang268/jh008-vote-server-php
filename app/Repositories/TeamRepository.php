<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\TeamRepository as TeamRepositoryContract;
use Jihe\Models\Team;
use Jihe\Models\TeamRequirement;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\City as CityEntity;
use Jihe\Entities\TeamRequirement as TeamRequirementEntity;
use Jihe\Models\TeamCertification;
use Jihe\Utils\SqlUtil;

class TeamRepository implements TeamRepositoryContract
{
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::getNumberOfTeamsCreatedBy()
     */
    public function getNumberOfTeamsCreatedBy($creator)
    {
        return Team::where('creator_id', $creator)->count();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::findTeam()
     */
    public function findTeam($team, $relations = [])
    {
        return $this->convertToEntity($this->findTeamModel($team, $relations));
    }
    
    /**
     * @param int $team
     * @return \Jihe\Models\Team
     */
    private function findTeamModel($team, $relations = [])
    {
        $query = Team::where('id', $team);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->first();
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::exists()
     */
    public function exists($team)
    {
        return null !== Team::where('id', $team)->value('id');
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::findTeamsCreatedBy()
     */
    public function findTeamsCreatedBy($creator, $relations = [])
    {
        $query = Team::where('creator_id', $creator);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return array_map([ $this, 'convertToEntity' ], $query->get()->all());
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::findTeams()
     */
    public function findTeams($page, $size, array $relations = [], array $criteria = [])
    {
        $query = Team::orderBy('updated_at', 'desc')
                     ->orderBy('id', 'desc');
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        if (!empty($name = array_get($criteria, 'name'))) {
            $query->where('name', 'like', '%' . SqlUtil::escape($name) . '%');
        }
        
        if ($city = array_get($criteria, 'city')) {
            $query->where('city_id', $city);
        }
        
        if (null !== ($tagged = array_get($criteria, 'tagged'))) {
            if ($tagged) {
                $query->whereNotNull('tags');
            } else {
                $query->whereNull('tags');
            }
        }
        
        if (null !== ($freeze = array_get($criteria, 'freeze'))) {
            if ($freeze) {
                $query->where('status', TeamEntity::STATUS_FREEZE);
            } else {
                $query->where('status', '<>', TeamEntity::STATUS_FREEZE);;
            }
        }
        
        if (null !== ($forbidden = array_get($criteria, 'forbidden'))) {
            if ($forbidden) {
                $query->where('status', TeamEntity::STATUS_FORBIDDEN);
            } else {
                $query->where('status', '<>', TeamEntity::STATUS_FORBIDDEN);;
            }
        }
        
        $total = $query->getCountForPagination()->count();
        $pages = ceil($total / $size);
        if ($page > $pages) {
            $page = $pages;
        }
        
        return [$pages, array_map([ $this, 'convertToEntity' ], 
                                  $query->forPage($page, $size)->get()->all())];
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::findPendingTeamsForCertification()
     */
    public function findPendingTeamsForCertification($page, $size, $relations = [])
    {
        $query = Team::where('certification', TeamEntity::CERTIFICATION_PENDING)
                     ->orderBy('created_at')
                     ->orderBy('id');
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        $total = $query->getCountForPagination()->count();
        $pages = ceil($total / $size);
        if ($page > $pages) {
            $page = $pages;
        }
        
        return [$pages, array_map([ $this, 'convertToEntity' ],
                                  $query->forPage($page, $size)->get()->all())];
    }
    
    /**
     *
     * @param \Jihe\Models\Team $model
     * @return \Jihe\Entities\Team|null
     */
    private function convertToEntity(Team $model = null)
    {
        if ($model == null) {
            return null;
        }

        return $model->toEntity();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::add()
     */
    public function add($team)
    {
        $team->setId(null);
        return Team::create($this->convertToModelArr($team))->id;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::update()
     */
    public function update($team)
    {
        $teamId = $team->getId();
        return 1 == Team::where('id', $teamId)
                        ->where('status', TeamEntity::STATUS_NORMAL)
                        ->update($this->convertToModelArr($team));
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::updateProperty()
     */
    public function updateProperties($team, $properties = [])
    {
        $attributes = [];
        
        if (!is_null($status = array_get($properties, 'status'))) {
            array_set($attributes, 'status', $status);
        }
        
        if (array_key_exists('tags', $properties)) {
            $tags = array_get($properties, 'tags');
            array_set($attributes, 'tags', $tags ? json_encode($tags) : null);
        }
        
        return 1 == Team::where('id', $team)
                        ->update($attributes);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::updateNotifiedAt()
     */
    public function updateNotifiedAt($team, $notices = [])
    {
        $attributes = [];

        if (in_array('activities', $notices)) {
            array_set($attributes, 'activities_updated_at', date('Y-m-d H:i:s'));
        }

        if (in_array('members', $notices)) {
            array_set($attributes, 'members_updated_at', date('Y-m-d H:i:s'));
        }

        if (in_array('news', $notices)) {
            array_set($attributes, 'news_updated_at', date('Y-m-d H:i:s'));
        }

        if (in_array('albums', $notices)) {
            array_set($attributes, 'albums_updated_at', date('Y-m-d H:i:s'));
        }

        if (in_array('notices', $notices)) {
            array_set($attributes, 'notices_updated_at', date('Y-m-d H:i:s'));
        }

        return 1 == Team::where('id', $team)->update($attributes);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::addRequirements()
     */
    public function addRequirements($team, array $requirements = [])
    {
        if (empty($requirements)) {
            return true;
        }
        
        $this->findTeamModel($team)
             ->requirements()
             ->createMany(array_map([$this, 'convertToRequirementModelArr'], $requirements));
        
        return true;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::deleteRequirements()
     */
    public function deleteRequirements(array $requirements = [])
    {
        if (empty($requirements)) {
            return true;
        }
        
        return count($requirements) == TeamRequirement::whereIn('id', $requirements)
                                                        ->delete();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::findRequirements()
     */
    public function findRequirements($team)
    {
        return array_map(function (TeamRequirement $teamRequirement) {
                            return $teamRequirement->toEntity();
                        }, 
                        $this->findTeamModel($team)
                             ->requirements
                             ->all());
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::updateTeamToPendingCertification()
     */
    public function updateTeamToPendingCertification($team)
    {
        return $this->updateCertification($team, TeamEntity::CERTIFICATION_PENDING);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::updateTeamToCertification()
     */
    public function updateTeamToCertification($team)
    {
        return $this->updateCertification($team, TeamEntity::CERTIFICATION);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::updateTeamToUnCertification()
     */
    public function updateTeamToUnCertification($team)
    {
        return $this->updateCertification($team, TeamEntity::UN_CERTIFICATION);
    }
    
    private function updateCertification($team, $certification)
    {
        return 1 == Team::where('id', $team)
                        ->update(['certification' => $certification]);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::addCertifications()
     */
    public function addCertifications($team, array $certifications = [])
    {
        if (empty($certifications)) {
            return true;
        }
        
        $this->findTeamModel($team)
             ->certifications()
             ->createMany(array_map([$this, 'convertToCertificationModelArr'], $certifications));
        
        return true;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::deleteCertifications()
     */
    public function deleteCertifications(array $certifications = [])
    {
        if (empty($certifications)) {
            return true;
        }
        
        return count($certifications) == TeamCertification::whereIn('id', $certifications)
                                                        ->delete();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::findCertifications()
     */
    public function findCertifications($team)
    {
        return array_map(function (TeamCertification $teamCertification) {
                            return $teamCertification->toEntity();
                        },
                        $this->findTeamModel($team)
                             ->certifications
                             ->all());
    }
    
    /**
     *
     * @param \Jihe\Entities\Team $team
     * @return array
     */
    private function convertToModelArr($team)
    {
        return array_filter([
                'creator_id'    => $team->getCreator()->getId(),
                'city_id'       => $team->getCity()->getId(),
                'name'          => $team->getName(),
                'email'         => $team->getEmail(),
                'logo_url'      => $team->getLogoUrl(),
                'address'       => $team->getAddress(),
                'contact_phone' => $team->getContactPhone(),
                'contact'       => $team->getContact(),
                'contact_hidden' => $team->getContactHidden(),
                'introduction'  => $team->getIntroduction(),
                'certification' => $team->getCertification(),
                'qr_code_url'   => $team->getQrCodeUrl(),
                'join_type'     => $team->getJoinType(),
                'status'        => $team->getStatus(),
        ], function ($value) {
            return !is_null($value);
        });
    }
    
    /**
     *
     * @param \Jihe\Entities\TeamRequirement $teamRequirement
     * @return array
     */
    private function convertToRequirementModelArr($teamRequirement)
    {
        return [
            'requirement' => $teamRequirement->getRequirement(),
        ];
    }
    
    /**
     *
     * @param \Jihe\Entities\TeamCertification $teamCertification
     * @return array
     */
    private function convertToCertificationModelArr($teamCertification)
    {
        return [
            'certification_url' => $teamCertification->getCertificationUrl(),
            'type'              => $teamCertification->getType(),
        ];
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRepository::findTeamsOf()
     */
    public function findTeamsOf($teams = [], array $relations = [])
    {
        if (empty($teams)) {
            return [0, []];
        }

        $query = Team::whereIn('id', $teams)
                     ->orderBy('activities_updated_at', 'desc')
                     ->orderBy('id', 'desc');

        if (!empty($relations)) {
            $query->with($relations);
        }

        $total = $query->count();

        return [
            $total,
            array_map(
                [ $this, 'convertToEntity' ],
                $query->get()->all())
        ];
    }
}
