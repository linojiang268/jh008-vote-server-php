<?php
namespace Jihe\Contracts\Repositories;

use Jihe\Entities\TeamGroup as TeamGroupEntity;

interface TeamGroupRepository
{
    /**
     * add a new group
     *
     * @param string $name      name of the group
     * @param int    $team      team's id
     * @return int              id of the newly added group
     */
    public function add($name, $team);

    /**
     * check whether group exists
     *
     * @param string $name    name of the group
     * @param int $team       id of the team
     * @return bool           true if given group exists. false otherwise.
     */
    public function exists($name, $team);

    /**
     * list all groups belonging to given team
     *
     * @param int $team   id of team
     * @return array      array of TeamGroupEntity
     */
    public function all($team);

    /**
     * find group
     *
     * @param int $group              id of group
     * @return TeamGroupEntity|null
     */
    public function findGroup($group);

    /**
     * update group's detail
     *
     * @param int $team      id of team
     * @param int $group     id of group
     * @param string $name   new name
     * @return bool          true if update succeeds, false otherwise.
     */
    public function update($team, $group, $name);

    /**
     * delete given group
     *
     * @param int $team      id of team
     * @param int $group     id of group
     * @return bool          true if deleted succeeds, false otherwise.
     */
    public function delete($team, $group);
}
