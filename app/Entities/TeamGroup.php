<?php
namespace Jihe\Entities;

class TeamGroup
{
    /**
     * members in a team that belong to no group falls into
     * a logically named UNGROUPED group.
     */
    const UNGROUPED = 0;

    private $id;
    /**
     * @var \Jihe\Entities\Team
     */
    private $team;
    private $name;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setTeam(Team $team)
    {
        $this->team = $team;
        return $this;
    }

    public function getTeam()
    {
        return $this->team;
    }
}
