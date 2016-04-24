<?php
namespace Jihe\Entities;

use Jihe\Entities\User;
use Jihe\Entities\Activity;

class ActivityApplicant
{
    const STATUS_INVALID = -1;
    const STATUS_NORMAL = 0;
    const STATUS_AUDITING = 1;
    const STATUS_PAY = 2;
    const STATUS_SUCCESS = 3;
    const STATUS_PAY_EXPIRED = 4;

    const CHANNEL_APP = 0;              // 用户从客户端主动报名
    const CHANNEL_WEB = 1;              // 用户从H5页面主动报名
    const CHANNEL_SINGLE_VIP = 2;       // 团长为单个用户主动报名
    const CHANNEL_BATCH_VIP = 3;        // 团长批量导入vip用户

    const ATTR_MOBILE = '手机号';
    const ATTR_NAME = '姓名';

    protected static $statusMaps = [
        self::STATUS_INVALID        => '失效',
        self::STATUS_NORMAL         => '初始',
        self::STATUS_AUDITING       => '审核中',
        self::STATUS_PAY            => '待付款',
        self::STATUS_SUCCESS        => '成功报名',
        self::STATUS_PAY_EXPIRED    => '支付过期',
    ];

    /**
     * @var integer
     */
    protected $id;

    /**
     * unique, used for identifying payment order no
     *
     * @var string  32 bit
     */
    protected $orderNo;

    /**
     * @var \Jihe\Entities\Activity 
     */
    protected $activity;

    /**
     * @var \Jihe\Entities\User
     */
    protected $user;

    /**
     * @var string
     */
    protected $mobile;

    /**
     * @var string
     */
    protected $name;

    /**
     * Associate array, key is custom applicantion condition
     *
     * @var array
     */
    protected $attrs;

    /**
     * @var \DateTime
     */
    protected $expireAt;

    /**
     * @var integer
     */
    protected $channel;

    /**
     * @var string
     */
    protected $remark;

    /**
     * @var integer
     */
    protected $status;

    /**
     * @var \DateTime
     */
    protected $applicantTime;

    /**
     * Get status description
     *
     * @param integer|null $status
     *
     * @return array|string     return desc related status if which be specified
     *                          or return desc array
     */
    public static function statusDescMap($status = null)
    {
        if ($status) {
            return isset(self::$statusMaps[$status]) ?
                self::$statusMaps[$status] : '未知状态';
        } else {
            return self::$statusMaps;
        }
    }
 
    /**
     * Get id.
     *
     * @return integer      applicant id.
     */
    public function getId()
    {
        return $this->id;
    }
 
    /**
     * Set id.
     *
     * @param integer $id   applicant id.
     *
     * @return static.
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
 
    /**
     * Get orderNo.
     *
     * @return string orderNo.
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }
 
    /**
     * Set orderNo.
     *
     * @param string $orderNo.
     *
     * @return static.
     */
    public function setOrderNo($orderNo)
    {
        $this->orderNo = $orderNo;
        return $this;
    }
 
    /**
     * Get activity.
     *
     * @return \Jihe\Entities\ActivityEntity.
     */
    public function getActivity()
    {
        return $this->activity;
    }
 
    /**
     * Set activity.
     *
     * @param \Jihe\Entities\Activity $activity.
     *
     * @return static.
     */
    public function setActivity(Activity $activity)
    {
        $this->activity = $activity;
        return $this;
    }
 
    /**
     * Get user.
     *
     * @return \Jihe\Entities\User.
     */
    public function getUser()
    {
        return $this->user;
    }
 
    /**
     * Set user.
     *
     * @param \Jihe\Entities\User $user.
     *
     * @return static.
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }
 
    /**
     * Get mobile.
     *
     * @return string mobile.
     */
    public function getMobile()
    {
        return $this->mobile;
    }
 
    /**
     * Set mobile.
     *
     * @param string $mobile.
     *
     * @return static.
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }
 
    /**
     * Get name.
     *
     * @return string name.
     */
    public function getName()
    {
        return $this->name;
    }
 
    /**
     * Set name.
     *
     * @param string $name.
     *
     * @return static.
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
 
    /**
     * Get attrs.
     *
     * @return array attrs.
     */
    public function getAttrs()
    {
        return $this->attrs;
    }
 
    /**
     * Set attrs.
     *
     * @param array $attrs.
     *
     * @return static.
     */
    public function setAttrs(array $attrs)
    {
        $this->attrs = $attrs;
        return $this;
    }
 
    /**
     * Get expireAt.
     *
     * @return \DateTime|null expireAt   after expireAt, applicant be set expired.
     */
    public function getExpireAt()
    {
        return $this->expireAt;
    }
 
    /**
     * Set expireAt.
     *
     * @param \DateTime|null $expireAt.
     *
     * @return static.
     */
    public function setExpireAt($expireAt)
    {
        $this->expireAt = $expireAt;
        return $this;
    }
 
    /**
     * Get channel.
     *
     * @return integer channel.
     */
    public function getChannel()
    {
        return $this->channel;
    }
 
    /**
     * Set channel.
     *
     * @param integer $channel.
     *
     * @return static.
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
        return $this;
    }
 
    /**
     * Get status.
     *
     * @return integer status.
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get status desc
     *
     * @return string status description.
     */
    public function getStatusDesc()
    {
        return self::statusDescMap($this->status);
    }
 
    /**
     * Set status.
     *
     * @param integer $status.
     *
     * @return static.
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get status.
     *
     * @return \DateTime status.
     */
    public function getApplicantTime()
    {
        return $this->applicantTime;
    }

    /**
     * Set applicant time
     *
     * @param \DateTime $applicantTime
     *
     * @return static.
     */
    public function setApplicantTime(\DateTime $applicantTime)
    {
        $this->applicantTime = $applicantTime;
        return $this;
    }

    /**
     * Get remark
     *
     * @return string.
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set remark
     *
     * @param string $remark
     *
     * @return static.
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;
        return $this;
    }
}
