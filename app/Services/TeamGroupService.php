<?php
namespace Jihe\Services;

use Jihe\Contracts\Repositories\TeamGroupRepository;
use Jihe\Entities\TeamGroup;

/**
 * Service for team group
 */
class TeamGroupService
{
    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * @var \Jihe\Contracts\Repositories\TeamGroupRepository
     */
    private $groups;

    /**
     * @var TeamMemberService
     */
    private $teamMemberService;

    public function __construct(TeamGroupRepository $groups,
                                TeamService $teamService,
                                TeamMemberService $teamMemberService)
    {
        $this->groups = $groups;
        $this->teamService = $teamService;
        $this->teamMemberService = $teamMemberService;
    }

    /**
     * get all groups of given team
     *
     * @param int|\Jihe\Entities\Team $team  the team's id or team instance
     * @throws \Exception      if team does not exist
     * @return array
     */
    public function getGroupsOf($team)
    {
        if (is_int($team)) { // it's team's id
            if (null == ($team = $this->teamService->getTeam($team))) {
                throw new \Exception('非法社团');
            }
        }

        return $this->groups->all($team->getId());
    }

    /**
     * get group
     *
     * @param int $group   id of the group
     *
     * @return \Jihe\Entities\TeamGroup|null
     */
    public function getGroup($group)
    {
        return $this->groups->findGroup($group);
    }

    /**
     * add a group for given team
     * @param string $name    name of the group
     * @param int|\Jihe\Entities\Team $team    the team for which to add this group
     *
     * @throws \Exception
     *
     * @return int     id of the newly added group
     */
    public function add($name, $team)
    {
        if (is_int($team)) { // it's team's id
            if (null == ($team = $this->teamService->getTeam($team))) {
                throw new \Exception('非法社团');
            }
        }

        // check the group name
        if ($this->exists($name, $team->getId())) {
            throw new \Exception('社团分组已存在');
        }

        return $this->groups->add($name, $team->getId());
    }

    /**
     * update group detail, the name currently
     *
     * @param int $team      id of group whose detail will be updated
     * @param int $group     id of group whose detail will be updated
     * @param string $name   new name
     *
     * @throws \Exception    if bad team/group pair given
     * @return bool          true if update succeeds, false otherwise.
     */
    public function update($team, $group, $name)
    {
        return $this->groups->update($team, $group, $name);
    }

    // check group name
    private function exists($name, $team)
    {
        // group name can not be '未分组'
        if ($name == '未分组')
        {
            return true;
        }

        //  group name should be unique in that team
        return $this->groups->exists($name, $team);
    }

    /**
     * update group detail, the name currently
     *
     * @param int $team      id of group whose detail will be updated
     * @param int $group     id of group whose detail will be updated
     * @param int $to        members in the group to be deleted will be moved to $to group
     *
     * @throws \Exception    if bad team/group pair given
     * @return bool          true on success, false otherwise
     */
    public function delete($team, $group, $to = TeamGroup::UNGROUPED)
    {
        // delete the group
        if ($this->groups->delete($team, $group)) {
            // move all members in that group to specified group
            $this->teamMemberService->changeGroupOfGroupedMembers($team, $group, $to);
        }
    }

}