<?php
namespace Jihe\Contracts\Repositories;

interface TeamMemberRepository
{
    /**
     * add a new team member
     *
     * @param int $user            the user to add to the team
     * @param int $team            team the user will be added to
     * @param array $requests      enrollment requirements. including:
     *                             - name         (Optional) nick name in the team
     *                             - memo         (Optional) memo
     *                             - requirements (Optional) of array type. contains answer to team requirements,
     *                                            which is keyed by team requirement id and valued by corresponding
     *                                            answer
     *                             - group        (Optional) group of given team, if not given, TeamGroup::UNGROUPED
     *                                            will be used
     * @return int                id of the newly added member
     */
    public function add($user, $team, array $requests = null);

    /**
     * check whether the membership between user and teams exists or not
     *
     * @param int $user        user's id
     * @param array $teams     ids of teams
     *
     * @return array           array of whether user if exists in each given team.
     *                         each key is team id, value is result of checked.
     *                         result: true if user is in key of team, otherwise false.
     */
    public function exists($user, array $teams);

    /**
     * find team member
     * @param $user
     * @param $team
     * @return \Jihe\Entities\TeamMember|null
     */
    public function findTeamMember($user, $team);

    /**
     * update individual team members' group
     *
     * @param int $team           team id
     * @param array|int $member   member ids
     * @param int $toGroup        to-group id
     */
    public function updateGroup($team, $member, $toGroup);


    /**
     * update team members's group(in a same group) to another group
     *
     * @param int $team        team id
     * @param int $fromGroup   from-group id
     * @param int $toGroup     to-group id
     */
    public function updateGroupOfGroupedMembers($team, $fromGroup, $toGroup);


    /**
     * delete member from team
     *
     * @param int $team        team id
     * @param int $member      member id
     * @return bool            true on success, false otherwise.
     */
    public function delete($team, $member);


    /**
     * update member's visibility in team
     *
     * @param int $member
     * @param int $team
     * @param array $updates
     * @return bool
     */
    public function update($member, $team, array $updates);


    /**
     * list members in a given team
     *
     * @param int $team        team id
     * @param int $page        page number
     * @param int $size        page size
     * @param array $criteria  - group       group id
     *                         - mobile      mobile#
     *                         - name        name
     *                         - visibility  TeamMember::VISIBILITY_ALL or TeamMember::VISIBILITY_TEAM or null
     * @return mixed
     */
    public function listMembers($team, $page, $size, array $criteria = []);

    /**
     * @param $team|array
     * @param null $visibility
     * @return mixed
     */
    public function countMembers($team, $visibility = null);
    
    /**
     * get enrolled team ids of given user
     * 
     * @param int $user      id of user
     * @param int $page      page number
     * @param int $size      page size
     * @param array $option  option, keys taken:
     *                        - only_id  true(default)
     *                        - paging   false(default)
     * @return arrays        array of team ids
     */
    public function listEnrolledTeams($user, $page = null, $size = null, array $option = []);
}
