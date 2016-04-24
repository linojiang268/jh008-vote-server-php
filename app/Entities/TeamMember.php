<?php
namespace Jihe\Entities;

class TeamMember
{
    /**
     * a normal member, which is approved by the team
     */
    const STATUS_NORMAL     = 0;

    // ordinary member
    const ROLE_ORDINARY     = 0;
    // creator
    const ROLE_CREATOR      = 1;

    const ROLE_PHRASES = [
        self::ROLE_ORDINARY  => '成员',
        self::ROLE_CREATOR   => '团长',
    ];

    const VISIBILITY_ALL    = 0;
    const VISIBILITY_TEAM   = 1;

    public static function isValidVisibility($visibility)
    {
        return in_array($visibility, [
            self::VISIBILITY_ALL,
            self::VISIBILITY_TEAM,
        ]);
    }

    private $id;
    private $user;
    private $team;
    private $status;
    private $role;
    private $group;
    private $memo;
    private $name;
    private $visibility;
    private $entryTime;
    private $requirements;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return \Jihe\Entities\User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setTeam($team)
    {
        $this->team = $team;
        return $this;
    }

    public function getTeam()
    {
        return $this->team;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setMemo($memo)
    {
        $this->memo = $memo;
        return $this;
    }

    public function getMemo()
    {
        return $this->memo;
    }

    public function setRole($role)
    {
        $this->role = $role;
        return $this;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getVisibility()
    {
        return $this->visibility;
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

    public function setEntryTime($entryTime)
    {
        $this->entryTime = $entryTime;
        return $this;
    }

    public function getEntryTime()
    {
        return $this->entryTime;
    }

    public function setRequirements($requirements)
    {
        $this->requirements = $requirements;
        return $this;
    }

    public function getRequirements()
    {
        return $this->requirements;
    }

    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @return \Jihe\Entities\TeamGroup|null
     */
    public function getGroup()
    {
        return $this->group;
    }

}
