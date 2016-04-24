<?php
namespace Jihe\Entities;

class TeamMemberRequirement
{
    private $id;
    private $value;
    
    private $member;
    private $requirement;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return \Jihe\Entities\TeamMember
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @param \Jihe\Entities\TeamMember $member
     *
     * @return $this
     */
    public function setMember($member)
    {
        $this->member = $member;
        return $this;
    }

    /**
     * @return \Jihe\Entities\TeamMemberEnrollmentRequirement
     */
    public function getRequirement()
    {
        return $this->requirement;
    }

    /**
     * @param \Jihe\Entities\TeamMemberEnrollmentRequirement $requirement
     * @return $this
     */
    public function setRequirement($requirement)
    {
        $this->requirement = $requirement;
        return $this;
    }
}
