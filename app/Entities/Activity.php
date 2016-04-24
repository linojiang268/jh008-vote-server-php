<?php
namespace Jihe\Entities;

/**
 * Activity
 */
class Activity
{
    //Activity status
    const STATUS_DELETE = -1; // 已暂停
    const STATUS_NOT_PUBLISHED = 0; // 待发布
    const STATUS_PUBLISHED = 1; // 已发布

    //Activity enroll type
    const ENROLL_ALL = 1; // 任何人可以加入
    const ENROLL_TEAM = 2; // 团队内部人员可加入
    const ENROLL_PRIVATE = 3; //团长定义的私密人员可加入

    const NOT_AUDITING = 0;
    const AUDITING = 1;

    //Activity enroll payment type
    const ENROLL_FEE_TYPE_FREE = 1; // 免费
    const ENROLL_FEE_TYPE_AA = 2; // AA制
    const ENROLL_FEE_TYPE_PAY = 3; // 收费模式

    const HAS_ALBUM = 1;
    const HAS_NO_ALBUM = 0;

    const SUB_STATUS_STANDBY = 1; //准备中
    const SUB_STATUS_START_ENROLL = 2; //报名中
    const SUB_STATUS_IN_PREPARATION = 3; //筹备中
    const SUB_STATUS_IN_PROGRESS = 4; //进行中
    const SUB_STATUS_END = 5; //已结束

    const PAYMENT_TIMEOUT = 1800; //支付超时倒计时默认值

    const SHOW_END_ACTIVITY_DELAY = 7;//结束活动显示延长天数

    const THUMBNAIL_STYLE_FOR_COVER = '@720w_50Q_1pr.jpg';
    const THUMBNAIL_STYLE_FOR_IMAGE = '@720w_1pr.jpg';

    const MANAGE_SEND_OK = 0; //可以发送
    const MANAGE_SEND_ENROLL_NOT_BEGIN = 1; //活动报名未开始，不可使用
    const MANAGE_SEND_ACTIVITY_END = 2; //活动已结束，不可使用
    const MANAGE_SEND_ACTIVITY_DELAY = 7; //活动结束延迟天数

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $beginTime;

    /**
     * @var string
     */
    private $endTime;

    /**
     * @var string
     */
    private $contact;

    /**
     * @var string
     */
    private $telephone;

    /**
     * @var string
     */
    private $coverUrl;

    /**
     * @var string
     */
    private $address;

    /**
     * @var string
     */
    private $briefAddress;

    /**
     * @var string
     */
    private $detail;

    /**
     * @var string
     */
    private $enrollBeginTime;

    /**
     * @var string
     */
    private $enrollEndTime;

    /**
     * @var integer
     */
    private $enrollType;

    /**
     * @var integer
     */
    private $enrollLimit;

    /**
     * @var integer
     */
    private $enrollFeeType;

    /**
     * @var float
     */
    private $enrollFee;

    /**
     * @var string
     */
    private $enrollAttrs;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var integer
     */
    private $id;

    /**
     *
     * @var array
     */
    private $location;

    /**
     * @var array
     */
    private $roadmap;

    /**
     * @var \Jihe\Entities\City
     */
    private $city;

    /**
     * @var \Jihe\Entities\Team
     */
    private $team;

    /**
     * @var string
     */
    private $publishTime;

    /**
     * @var int
     */
    private $auditing;

    /**
     * @var int
     */
    private $updateStep;

    /**
     * @var int
     */
    private $essence;

    /**
     * @var int
     */
    private $top;

    /**
     * @var string
     */
    private $imagesUrl;

    /**
     * @var int
     */
    private $hasAlbum;

    /**
     * @var string
     */
    private $tags;

    /*
     * @var string
     */
    private $qrCodeUrl;

    /*
    * @var int
    */
    private $applicantsStatus;

    /*
     * @var string
     */
    private $organizers;

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Activity
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set beginTime
     *
     * @param \DateTime $beginTime
     *
     * @return Activity
     */
    public function setBeginTime($beginTime)
    {
        $this->beginTime = $beginTime;

        return $this;
    }

    /**
     * Get beginTime
     *
     * @return \DateTime
     */
    public function getBeginTime()
    {
        return $this->beginTime;
    }

    /**
     * Set endTime
     *
     * @param \DateTime $endTime
     *
     * @return Activity
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set contact
     *
     * @param string $contact
     *
     * @return Activity
     */
    public function setContact($contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return string
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set telephone
     *
     * @param string $telephone
     *
     * @return Activity
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * Get telephone
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Set coverUrl
     *
     *
     * @param string $coverUrl
     *
     * @return Activity
     */
    public function setCoverUrl($coverUrl)
    {
        $this->coverUrl = $coverUrl;

        return $this;
    }

    /**
     * Get coverUrl
     *
     * @return string
     */
    public function getCoverUrl()
    {
        return $this->coverUrl;
    }

    /**
     * get thumbnail url of image
     *
     * @return string
     */
    public function getCoverUrlOfThumbnail()
    {
        return $this->coverUrl . self::THUMBNAIL_STYLE_FOR_COVER;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return Activity
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set briefAddress
     *
     * @param string $briefAddress
     *
     * @return Activity
     */
    public function setBriefAddress($briefAddress)
    {
        $this->briefAddress = $briefAddress;

        return $this;
    }

    /**
     * Get briefAddress
     *
     * @return string
     */
    public function getBriefAddress()
    {
        return $this->briefAddress;
    }

    /**
     * Set detail
     *
     * @param string $detail
     *
     * @return Activity
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * Get detail
     *
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * Set enrollBeginTime
     *
     * @param \DateTime $enrollBeginTime
     *
     * @return Activity
     */
    public function setEnrollBeginTime($enrollBeginTime)
    {
        $this->enrollBeginTime = $enrollBeginTime;

        return $this;
    }

    /**
     * Get enrollBeginTime
     *
     * @return \DateTime
     */
    public function getEnrollBeginTime()
    {
        return $this->enrollBeginTime;
    }

    /**
     * Set enrollEndTime
     *
     * @param \DateTime $enrollEndTime
     *
     * @return Activity
     */
    public function setEnrollEndTime($enrollEndTime)
    {
        $this->enrollEndTime = $enrollEndTime;

        return $this;
    }

    /**
     * Get enrollEndTime
     *
     * @return \DateTime
     */
    public function getEnrollEndTime()
    {
        return $this->enrollEndTime;
    }

    /**
     * Set enrollType
     *
     * @param integer $enrollType
     *
     * @return Activity
     */
    public function setEnrollType($enrollType)
    {
        $this->enrollType = $enrollType;

        return $this;
    }

    /**
     * Get enrollType
     *
     * @return integer
     */
    public function getEnrollType()
    {
        return $this->enrollType;
    }

    /**
     * Set enrollLimit
     *
     * @param integer $enrollLimit
     *
     * @return Activity
     */
    public function setEnrollLimit($enrollLimit)
    {
        $this->enrollLimit = $enrollLimit;

        return $this;
    }

    /**
     * Get enrollLimit
     *
     * @return integer
     */
    public function getEnrollLimit()
    {
        return $this->enrollLimit;
    }

    /**
     * Set enrollFeeType
     *
     * @param integer $enrollFeeType
     *
     * @return Activity
     */
    public function setEnrollFeeType($enrollFeeType)
    {
        $this->enrollFeeType = $enrollFeeType;

        return $this;
    }

    /**
     * Get enrollFeeType
     *
     * @return integer
     */
    public function getEnrollFeeType()
    {
        return $this->enrollFeeType;
    }

    /**
     * Set enrollFee
     *
     * @param float $enrollFee
     *
     * @return Activity
     */
    public function setEnrollFee($enrollFee)
    {
        $this->enrollFee = $enrollFee;

        return $this;
    }

    /**
     * Get enrollFee
     *
     * @return float
     */
    public function getEnrollFee()
    {
        return $this->enrollFee;
    }

    /**
     * Set enrollAttrs
     *
     * @param string $enrollAttrs
     *
     * @return Activity
     */
    public function setEnrollAttrs($enrollAttrs)
    {
        $this->enrollAttrs = $enrollAttrs;

        return $this;
    }

    /**
     * Get enrollAttrs
     *
     * @return string
     */
    public function getEnrollAttrs()
    {
        return $this->enrollAttrs;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return Activity
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set location
     *
     * @param array $location
     *
     * @return Activity
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return array
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set roadmap
     *
     * @param array $roadmap
     *
     * @return Activity
     */
    public function setRoadmap($roadmap)
    {
        $this->roadmap = $roadmap;

        return $this;
    }

    /**
     * Get roadmap
     *
     * @return array
     */
    public function getRoadmap()
    {
        return $this->roadmap;
    }

    /**
     * Set id
     *
     * @param  integer $id
     *
     * @return Activity
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get team
     *
     * @return \Jihe\Entities\Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * Set team
     *
     * @param  \Jihe\Entities\Team $team
     *
     * @return Activity
     */
    public function setTeam($team)
    {
        $this->team = $team;
        return $this;
    }

    /**
     * Get city
     *
     * @return \Jihe\Entities\City
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set city
     *
     * @param  \Jihe\Entities\City $city
     *
     * @return Activity
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Set publishTime
     *
     * @param \DateTime $publishTime
     *
     * @return Activity
     */
    public function setPublishTime($publishTime)
    {
        $this->publishTime = $publishTime;

        return $this;
    }

    /**
     * Get publishTime
     *
     * @return \DateTime
     */
    public function getPublishTime()
    {
        return $this->publishTime;
    }

    /**
     * Set auditing
     *
     * @param int $auditing
     *
     * @return Activity
     */
    public function setAuditing($auditing)
    {
        $this->auditing = $auditing;

        return $this;
    }

    /**
     * Get auditing
     *
     * @return int
     */
    public function getAuditing()
    {
        return $this->auditing;
    }

    /**
     * Set updateStep
     *
     * @param int $updateStep
     *
     * @return Activity
     */
    public function setUpdateStep($updateStep)
    {
        $this->updateStep = $updateStep;

        return $this;
    }

    /**
     * Get updateStep
     *
     * @return int
     */
    public function getUpdateStep()
    {
        return $this->updateStep;
    }

    /**
     * Set essence
     *
     * @param int $essence
     *
     * @return Activity
     */
    public function setEssence($essence)
    {
        $this->essence = $essence;

        return $this;
    }

    /**
     * Get essence
     *
     * @return int
     */
    public function getEssence()
    {
        return $this->essence;
    }

    /**
     * Set top
     *
     * @param int $top
     *
     * @return Activity
     */
    public function setTop($top)
    {
        $this->top = $top;

        return $this;
    }

    /**
     * Get top
     *
     * @return int
     */
    public function getTop()
    {
        return $this->top;
    }

    /**
     * Set imagesUrl
     *
     * @param int $imagesUrl
     *
     * @return Activity
     */
    public function setImagesUrl($imagesUrl)
    {
        $this->imagesUrl = $imagesUrl;

        return $this;
    }

    /**
     * Get imagesUrl
     *
     * @return array
     */
    public function getImagesUrl()
    {
        return $this->imagesUrl;
    }

    /**
     * get thumbnail urls of images
     *
     * @return array|string
     */
    public function getImagesUrlOfThumbnail()
    {
        if (empty($this->imagesUrl)) {
            return $this->imagesUrl;
        }

        return array_map(function ($imageUrl) {
            return $imageUrl . self::THUMBNAIL_STYLE_FOR_IMAGE;
        }, $this->imagesUrl);
    }

    /**
     * Set hasAlbum
     *
     * @param int $hasAlbum
     *
     * @return Activity
     */
    public function setHasAlbum($hasAlbum)
    {
        $this->hasAlbum = $hasAlbum;

        return $this;
    }

    /**
     * Get hasAlbum
     *
     * @return int
     */
    public function getHasAlbum()
    {
        return $this->hasAlbum;
    }

    /**
     * Get subStatus
     *
     * @return int
     */
    public function getSubStatus()
    {
        $time = time();
        if ($time > strtotime($this->endTime)) {
            return Activity::SUB_STATUS_END;//已结束
        }
        if ($time >= strtotime($this->beginTime)) {
            return Activity::SUB_STATUS_IN_PROGRESS;//进行中
        }
        if ($time > strtotime($this->enrollEndTime)) {
            return Activity::SUB_STATUS_IN_PREPARATION;//筹备中
        }
        if ($time >= strtotime($this->enrollBeginTime)) {
            return Activity::SUB_STATUS_START_ENROLL;//报名中
        }

        return Activity::SUB_STATUS_STANDBY;//准备中
    }

    /**
     * Set Tags
     *
     * @param string $tags
     *
     * @return Activity
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get Tags
     *
     * @return string
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set qrCodeUrl
     *
     * @param string $qrCodeUrl
     *
     * @return Activity
     */
    public function setQrCodeUrl($qrCodeUrl)
    {
        $this->qrCodeUrl = $qrCodeUrl;

        return $this;
    }

    /**
     * Get qrCodeUrl
     *
     * @return string
     */
    public function getQrCodeUrl()
    {
        return $this->qrCodeUrl;
    }

    /**
     * Set applicantsStatus
     *
     * @param string $applicantsStatus
     *
     * @return Activity
     */
    public function setApplicantsStatus($applicantsStatus)
    {
        $this->applicantsStatus = $applicantsStatus;

        return $this;
    }

    /**
     * Get applicantsStatus
     *
     * @return int
     */
    public function getApplicantsStatus()
    {
        return $this->applicantsStatus;
    }

    /**
     * @return string
     */
    public function getOrganizers()
    {
        return $this->organizers;
    }

    /**
     * @param string $organizers
     */
    public function setOrganizers($organizers)
    {
        $this->organizers = $organizers;

        return $this;
    }

    /*
     * @return
     */
    public function willPaymentTimeout()
    {
        if($this->auditing == self::NOT_AUDITING){
            if($this->enrollFeeType == self::ENROLL_FEE_TYPE_PAY){
                return true;
            }
        }
        return false;
    }

    public function getSendStatus()
    {
        $time = time();
        if ($time > strtotime($this->endTime) + Activity::MANAGE_SEND_ACTIVITY_DELAY * 86400) {
            return Activity::MANAGE_SEND_ACTIVITY_END;
        }
        if ($time < strtotime($this->enrollBeginTime)) {
            return Activity::MANAGE_SEND_ENROLL_NOT_BEGIN;
        }
        return Activity::MANAGE_SEND_OK;
    }

}
