<?php
namespace Jihe\Entities;

/**
 * A user requests for being enrolled into a team
 */
class TeamMemberEnrollmentRequest
{
    /**
     * user issued team enrollment request, and it's not yet processed
     * @var int
     */
    const STATUS_PENDING = 0;
    
    /**
     * user's team enrollment request is approved
     * @var int
     */
    const STATUS_APPROVED = 1;
    
    /**
     * user's team enrollment request is rejected
     * @var int
     */
    const STATUS_REJECTED = 2;
    
    private $id;
    private $status = self::STATUS_PENDING;

    private $initiator;
    private $team;
    private $group;
    private $requirements;
    private $memo;
    private $name;
    private $reason;
    
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getMemo()
    {
        return $this->memo;
    }

    public function setMemo($memo)
    {
        $this->memo = $memo;
        return $this;
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

    public function getReason()
    {
        return $this->reason;
    }

    public function setReason($reason)
    {
        $this->reason = $reason;
        return $this;
    }

    public function getRequirements()
    {
        return $this->requirements;
    }

    public function setRequirements(array $requirements)
    {
        $this->requirements = $requirements;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    
    /**
     * @return \Jihe\Entities\Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    public function setTeam($team)
    {
        $this->team = $team;
        return $this;
    }

    /**
     * @return \Jihe\Entities\TeamGroup
     */
    public function getGroup()
    {
        return $this->group;
    }

    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @return \Jihe\Entities\User
     */
    public function getInitiator()
    {
        return $this->initiator;
    }

    public function setInitiator($initiator)
    {
        $this->initiator = $initiator;
        return $this;
    }
}
