<?php
namespace Jihe\Contracts\Repositories;

interface TeamRepository
{
    /**
     * get the number of teams created by given creator
     *
     * @param int $creator   creator of teams
     * @return int      number of teams created by given creator
     */
    public function getNumberOfTeamsCreatedBy($creator);
    
    /**
     * find team by its id (city is pre-fetched)
     *
     * @param int $team                id of team
     * @param array $relations|[]      array of model relatisonship, keys:
     *                                   - city
     *                                   - creator
     *                                   - requirements
     * @return \Jihe\Entities\Team|null
     */
    public function findTeam($team, $relations = []);

    /**
     * check whether given team exists or not
     *
     * @param int $team  id of team
     * @return bool      true if team exists. false otherwise
     */
    public function exists($team);
    
    /**
     * add a new team
     *
     * @param \Jihe\Entities\Team $team
     */
    public function add($team);
    
    /**
     * update team
     * @param \Jihe\Entities\Team $team
     */
    public function update($team);
    
    /**
     * 
     * @param int $team           id of team
     * @param array $properties   array of team properties, keys taken:
     *                             - status  (int)status of team
     *                             - tags    (array)
     * @return boolean
     */
    public function updateProperties($team, $properties = []);

    /**
     *
     * @param int $team           id of team
     * @param array $notices      array of $notices, values taken:
     *                             - activities
     *                             - members
     *                             - news
     *                             - albums
     *                             - notices
     * @return boolean
     */
    public function updateNotifiedAt($team, $notices = []);
    
    /**
     * find all teams created by given creator
     *
     * @param int $creator        id of team creator
     * @param array $relations|[] array of model relatisonship, keys:
     *                               - city
     *                               - creator
     *                               - requirements
     * @return array           array of \Jihe\Entities\Team. An empty array will
     *                         be returned if there is no teams created by given creator.
     */
    public function findTeamsCreatedBy($creator, $relations = []);
    
    /**
     * find teams that name like given keywords of name
     *
     * @param string $name         city of teams
     * @param string $name         keywords of team's name
     * @param int $page            the offset page of teams
     * @param int $size            the limit of teams size
     * @param array $relations|[]  array of model relatisonship, keys:
     *                               - city
     *                               - creator
     *                               - requirements
     * @param array $criteria|[]   criteria, keys:
     *                               - city|null  (int)id of city
     *                               - name|null  string of nickname
     *                               - tagged     boolean, null(default)
     *                               - freeze     boolean, null(default)
     *                               - forbidden  boolean, null(default)
     * @return array|[]            array of \Jihe\Entities\Team
     */
    public function findTeams($page, $size, array $relations = [], array $criteria = []);
    
    /**
     * find pengding teams that has request for certification
     * 
     * @param int $page            the offset page of teams
     * @param int $size            the limit of teams size
     * @param array $relations|[]  array of model relatisonship, keys:
     *                               - city
     *                               - creator
     *                               - requirements
     * @return array|[]            array of \Jihe\Entities\Team
     */
    public function findPendingTeamsForCertification($page, $size, $relations = []);
    
    /**
     * add requirements of team
     *
     * @param int   $team          id of team
     * @param array $requirements  array of \Jihe\Entities\TeamRequirement
     * @return boolean             true if add successfully, false otherwise
     */
    public function addRequirements($team, array $requirements = []);
    
    /**
     * delete team's requirements by ids of requirement
     * 
     * @param array $requirements  array of requirement's id
     */
    public function deleteRequirements(array $requirements = []);
    
    /**
     * find requirements of team by given team id
     * 
     * @param int $team  id of team
     * @return array     array of \Jihe\Entities\TeamRequirement
     */
    public function findRequirements($team);
    
    /**
     * update team to pending certification
     *
     * @param int $team id of team
     * @return boolean  true if update successfully
     */
    public function updateTeamToPendingCertification($team);
    
    /**
     * update team to certification
     * 
     * @param int $team id of team
     * @return boolean  true if update successfully
     */
    public function updateTeamToCertification($team);
    
    /**
     * update team to uncertification
     *
     * @param int $team id of team
     * @return boolean  true if update successfully
     */
    public function updateTeamToUnCertification($team);

    /**
     * add certifications of team
     * 
     * @param int $team               id of team
     * @param  array $certifications  array of \Jihe\Entities\TeamCertification
     * @return boolean                true if add successfully, false otherwise
     */
    public function addCertifications($team, array $certifications = []);
    
    /**
     * delete team's certifications by ids of certification
     * 
     * @param array $certifications  array of certification's id
     */
    public function deleteCertifications(array $certifications = []);
    
    /**
     * find certifications of team by given team id
     *
     * @param int $team  id of team
     * @return array     array of \Jihe\Entities\TeamCertification
     */
    public function findCertifications($team);

    /**
     * find teams of given tesm ids
     *
     * @param string $teams        ids of team
     * @param array $relations|[]  array of model relatisonship, keys:
     *                               - city
     *                               - creator
     *                               - requirements

     * @return array|[]            array of \Jihe\Entities\Team
     */
    public function findTeamsOf($teams = [], array $relations = []);
}
