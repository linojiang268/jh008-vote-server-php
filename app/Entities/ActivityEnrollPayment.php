<?php
namespace Jihe\Entities;

use Jihe\Entities\Activity;
use Jihe\Entities\User;

class ActivityEnrollPayment
{
    const CHANNEL_ALIPAY = 1;
    const CHANNEL_WXPAY = 2;

    const STATUS_WAIT = 0;
    const STATUS_SUCCESS = 2;
    const STATUS_FAIL = 3;
    const STATUS_CLOSED = 4;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Jihe\Entities\Activity
     */
    private $activity;

    /**
     * @var \Jihe\Entities\User
     */
    private $user;

    /**
     * @var integer
     */
    private $fee;

    /**
     * @var integer
     */
    private $channel;

    /**
     * @var string
     */
    private $orderNo;

    /**
     * @var string
     */
    private $tradeNo;

    /**
     * @var \DateTime
     */
    private $payedAt;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var integer
     */
    private $status;

    /**
     * Get Id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Id
     * 
     * @param integer $id
     * @return ActivityEnrollPayment
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get activity
     *
     * @return \Jihe\Entities\Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set activity
     *
     * @param \Jihe\Entities\Activity
     * @return ActivityEnrollPayment
     */
    public function setActivity(Activity $activity)
    {
        $this->activity = $activity;
        return $this;
    }

    /**
     * Get user
     *
     * @return \Jihe\Entities\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user
     *
     * @param \Jihe\Entities\User
     * @return ActivityEnrollPayment
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get fee
     *
     * @return integer
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * Set fee
     *
     * @param integer
     * @return ActivityEnrollPayment
     */
    public function setFee($fee)
    {
        $this->fee = $fee;
        return $this;
    }

    /**
     * Get channel
     *
     * @return integer
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set channel
     *
     * @param integer $channel
     * @return ActivityEnrollPayment
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * Get orderNo
     *
     * @return string
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * Set orderNo
     *
     * @param string $orderNo
     * @return ActivityEnrollPayment
     */
    public function setOrderNo($orderNo)
    {
        $this->orderNo = $orderNo;
        return $this;
    }

    /**
     * Get tradeNo
     *
     * @return string
     */
    public function getTradeNo()
    {
        return $this->tradeNo;
    }

    /**
     * Set tradeNo
     *
     * @param string $tradeNo
     * @return ActivityEnrollPayment
     */
    public function setTradeNo($tradeNo)
    {
        $this->tradeNo = $tradeNo;
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdAt 
     *
     * @param \DateTime $createdAt
     * @return ActivityEnrollPayment
     */
    public function setCreatedAt(\Datetime $createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get payedAt
     *
     * @return \DateTime|null $payedAt
     */
    public function getPayedAt()
    {
        return $this->payedAt;
    }

    /**
     * Set payedAt
     *
     * @param \DateTime|null $payedAt
     * @return ActivityEnrollPayment
     */
    public function setPayedAt($payedAt)
    {
        $this->payedAt = $payedAt;
        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return ActivityEnrollPayment
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
}
