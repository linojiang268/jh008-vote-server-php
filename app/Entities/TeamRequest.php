<?php
namespace Jihe\Entities;

/**
 * Request for either team enrollment or update
 */
class TeamRequest
{
    /**
     * user issued team enrollment/update request, and it's not processed
     * @var int
     */
    const STATUS_PENDING = 0;
    
    /**
     * user's enrollment/update request is approved
     * @var int
     */
    const STATUS_APPROVED = 1;
    
    /**
     * user's enrollment/update request is rejected
     * @var int
     */
    const STATUS_REJECTED = 2;

    const CONTACT_NOT_HIDDEN = 0;//不隐藏联系方式
    const CONTACT_HIDDEN = 1;//隐藏联系方式

    private $id;
    private $name;
    private $email;
    private $logoUrl;
    private $address;
    private $contactPhone;
    private $contact;
    private $contactHidden = self::CONTACT_HIDDEN;
    private $introduction;
    private $status = self::STATUS_PENDING;
    private $read = false;
    private $memo;

    private $city;
    private $team;
    private $initiator;

    private $createdAt;
    private $updatedAt;
    
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
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

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    public function getLogoUrl()
    {
        return $this->logoUrl;
    }

    public function setLogoUrl($logoUrl)
    {
        $this->logoUrl = $logoUrl;
        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    public function getContactPhone()
    {
        return $this->contactPhone;
    }

    public function setContactPhone($contactPhone)
    {
        $this->contactPhone = $contactPhone;
        return $this;
    }

    public function getContact()
    {
        return $this->contact;
    }

    public function setContact($contact)
    {
        $this->contact = $contact;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getContactHidden()
    {
        return $this->contactHidden;
    }

    /**
     * @param mixed $contactHidden
     */
    public function setContactHidden($contactHidden)
    {
        $this->contactHidden = $contactHidden;
        return $this;
    }

    public function getIntroduction()
    {
        return $this->introduction;
    }

    public function setIntroduction($introduction)
    {
        $this->introduction = $introduction;
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
     * @return \Jihe\Entities\City
     */
    public function getCity()
    {
        return $this->city;
    }
    
    public function setCity($city)
    {
        $this->city = $city;
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

    /**
     * @return boolean
     */
    public function isRead()
    {
        return $this->read;
    }
    
    /**
     * @param boolean $read
     * @return \Jihe\Entities\TeamRequest
     */
    public function setRead($read)
    {
        $this->read = $read;
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

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     * @return TeamRequest
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param mixed $updatedAt
     * @return TeamRequest
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
