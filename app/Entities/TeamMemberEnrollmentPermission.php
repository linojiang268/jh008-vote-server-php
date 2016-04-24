<?php
namespace Jihe\Entities;

/**
 *
 */
class TeamMemberEnrollmentPermission
{
    /**
     * permit
     * @var int
     */
    const STATUS_PERMITTED = 0;
    
    /**
     * prohibit
     * @var int
     */
    const STATUS_PROHIBITED = 1;
    
    private $id;
    private $status = self::STATUS_PERMITTED;

    private $mobile;
    private $team;
    private $memo;
    private $name;

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

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        if (self::isValidStatus($status)) {
            $this->status = $status;
        }
        
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

    public function getMobile()
    {
        return $this->mobile;
    }

    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }

    public function permitted()
    {
        return $this->status == self::STATUS_PERMITTED;
    }

    public function prohibited()
    {
        return $this->status == self::STATUS_PROHIBITED;
    }

    public static function isValidStatus($status)
    {
        return $status == self::STATUS_PERMITTED ||
               $status == self::STATUS_PROHIBITED;
    }
}
