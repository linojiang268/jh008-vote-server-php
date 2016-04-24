<?php
namespace Jihe\Entities;

class Team
{
    const STATUS_NORMAL = 0;// 正常
    const STATUS_FORBIDDEN = 1;// 已封团
    const STATUS_FREEZE = 2;// 已冻结
    
    const JOIN_TYPE_ANY = 0;// 任何人可以加入
    const JOIN_TYPE_VERIFY = 1;// 需要审核方可加入
    
    const UN_CERTIFICATION = 0;// 未认证
    const CERTIFICATION_PENDING = 1;// 认证审核中
    const CERTIFICATION = 2;// 已认证

    const CONTACT_NOT_HIDDEN = 0;//不隐藏联系方式
    const CONTACT_HIDDEN = 1;//隐藏联系方式

    const THUMBNAIL_STYLE_FOR_LOGO = '@200w_200h_1e_1pr.src';
    
    private $id;
    
    private $name;
    private $email;
    private $logoUrl;
    private $address;
    private $contactPhone;
    private $contact;
    private $contactHidden = self::CONTACT_HIDDEN;
    private $introduction;
    private $certification = self::UN_CERTIFICATION;
    private $qrCodeUrl;
    private $joinType = self::JOIN_TYPE_ANY;
    private $status = self::STATUS_NORMAL;
    
    private $creator;
    private $city;
    private $requirements;
    
    private $activitiesUpdatedAt;
    private $membersUpdatedAt;
    private $newsUpdatedAt;
    private $albumsUpdatedAt;
    private $noticesUpdatedAt;
    private $tags;

    private $createdAt;

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

    public function getLogoUrlOfThumbnail()
    {
        return $this->logoUrl . self::THUMBNAIL_STYLE_FOR_LOGO;
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
    
    public function getCertification()
    {
        return $this->certification;
    }
    
    public function setCertification($certification)
    {
        $this->certification = $certification;
        return $this;
    }
    
    public function getQrCodeUrl()
    {
        return $this->qrCodeUrl;
    }
    
    public function setQrCodeUrl($qrCodeUrl)
    {
        $this->qrCodeUrl = $qrCodeUrl;
        return $this;
    }

    public function getJoinType()
    {
        return $this->joinType;
    }

    public function setJoinType($joinType)
    {
        $this->joinType = $joinType;
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

    public function setCity(\Jihe\Entities\City $city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return \Jihe\Entities\User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    public function setCreator(\Jihe\Entities\User $creator)
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * check whether the team can accept enrollment request without auditing
     *
     * @return bool   true if the team can accept enrollment request without aduiting
     */
    public function acceptsWorldwideEnrollmentRequest()
    {
        return $this->joinType == self::JOIN_TYPE_ANY;
    }

    /**
     * @return array array of \Jihe\Entities\TeamRequirement
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * @param array $requirements
     * @return \Jihe\Entities\Team
     */
    public function setRequirements(array $requirements)
    {
        $this->requirements = $requirements;
        return $this;
    }
    
    public function getActivitiesUpdatedAt() 
    {
        return $this->activitiesUpdatedAt;
    }
    
    public function setActivitiesUpdatedAt($activitiesUpdatedAt)
    {
        $this->activitiesUpdatedAt = $activitiesUpdatedAt;
        return $this;
    }
    
    public function getMembersUpdatedAt()
    {
        return $this->membersUpdatedAt;
    }
    
    public function setMembersUpdatedAt($membersUpdatedAt)
    {
        $this->membersUpdatedAt = $membersUpdatedAt;
        return $this;
    }
    
    public function getNewsUpdatedAt()
    {
        return $this->newsUpdatedAt;
    }
    
    public function setNewsUpdatedAt($newsUpdatedAt)
    {
        $this->newsUpdatedAt = $newsUpdatedAt;
        return $this;
    }
    
    public function getAlbumsUpdatedAt()
    {
        return $this->albumsUpdatedAt;
    }
    
    public function setAlbumsUpdatedAt($albumsUpdatedAt)
    {
        $this->albumsUpdatedAt = $albumsUpdatedAt;
        return $this;
    }
    
    public function getNoticesUpdatedAt()
    {
        return $this->noticesUpdatedAt;
    }
    
    public function setNoticesUpdatedAt($noticesUpdatedAt)
    {
        $this->noticesUpdatedAt = $noticesUpdatedAt;
        return $this;
    }
    
    /**
     * 
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    public function setTags(array $tags = null)
    {
        $this->tags = $tags;
        return $this;
    }

    public function isCertificated()
    {
        return $this->certification == self::CERTIFICATION;
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
     * @return Team
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
