<?php
namespace Jihe\Entities;

use Jihe\Entities\Team;

class TeamRequirement
{
    private $id;
    private $requirement;
    
    private $team;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getRequirement()
    {
        return $this->requirement;
    }

    public function setRequirement($requirement)
    {
        $this->requirement = $requirement;
        return $this;
    }

    /**
     * @return \Jihe\Entities\Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @param \Jihe\Entites\Team $team
     * @return \Jihe\Entities\TeamRequirement
     */
    public function setTeam(Team $team)
    {
        $this->team = $team;
        return $this;
    }
}
