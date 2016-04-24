<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\id;
use Jihe\Contracts\Repositories\name;
use Jihe\Contracts\Repositories\TeamGroupRepository as TeamGroupRepositoryContract;
use Jihe\Entities\TeamGroup as TeamGroupEntity;
use Jihe\Models\TeamGroup;

class TeamGroupRepository implements TeamGroupRepositoryContract
{
    /**
     * add a new group
     *
     * @param string $name      name of the group
     * @param int    $team      team's id
     * @return int              id of the newly added group
     */
    public function add($name, $team)
    {
        return TeamGroup::create([
            'team_id' => $team,
            'name'    => $name,
        ])->id;
    }

    /**
     * check whether group exists
     *
     * @param string $name     name of the group
     * @param int $team     id of the team
     * @return bool     true if given group exists. false otherwise.
     */
    public function exists($name, $team)
    {
        return null !== TeamGroup::where('team_id', $team)
                                 ->where('name', $name)
                                 ->value('id');
    }

    /**
     * list all groups belonging to given team
     *
     * @param int $team    id of team
     * @return array   array of TeamGroupEntity
     */
    public function all($team)
    {
        return array_map(function (TeamGroup $group) {
            return $group->toEntity();
        }, TeamGroup::where('team_id', $team)
                    ->get()->all());
    }

    /**
     * find group
     *
     * @param int $group              id of group
     * @return TeamGroupEntity|null
     */
    public function findGroup($group)
    {
        $group = TeamGroup::where('id', $group)
                          ->first();

        return $group ? $group->toEntity() : null;
    }

    /**
     * update group's detail
     *
     * @param int $team      id of team
     * @param int $group     id of group
     * @param string $name   new name
     * @return bool          true if update succeeds, false otherwise.
     */
    public function update($team, $group, $name)
    {
        return 1 == TeamGroup::where('id', $group)
                             ->where('team_id', $team)
                             ->update([
                                 'name' => $name,
                             ]);
    }

    /**
     * delete given group
     *
     * @param int $team      id of team
     * @param int $group     id of group
     * @return bool          true if deleted succeeds, false otherwise.
     */
    public function delete($team, $group)
    {
        return 1 == TeamGroup::where('id', $group)
                             ->where('team_id', $team)
                             ->delete();
    }
}
