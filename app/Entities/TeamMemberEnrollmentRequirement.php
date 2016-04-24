<?php
namespace Jihe\Entities;

class TeamMemberEnrollmentRequirement
{
    private $id;
    private $value;
    
    private $request;
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
     * @return \Jihe\Entities\TeamMemberEnrollmentRequestEntity
     */
    public function getRequest()
    {
        return $this->user;
    }

    /**
     * @param \Jihe\Entities\TeamMemberEnrollmentRequestEntity $request
     *
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return \Jihe\Entities\TeamMemberRequirement
     */
    public function getRequirement()
    {
        return $this->requirement;
    }

    /**
     * @param \Jihe\Entities\TeamMemberRequirement $requirement
     * @return $this
     */
    public function setRequirement($requirement)
    {
        $this->requirement = $requirement;
        return $this;
    }
}
