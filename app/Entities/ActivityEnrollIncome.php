<?php
namespace Jihe\Entities;

use Jihe\Entities\Activity;
use Jihe\Entities\Team;

class ActivityEnrollIncome
{
    const STATUS_WAIT = 1;
    const STATUS_TRANSFERING = 2;
    const STATUS_FINISHED = 3;

    private static $statusDesc = [
        self::STATUS_WAIT           => '待转账',
        self::STATUS_TRANSFERING    => '转账中',
        self::STATUS_FINISHED       => '已转账',
    ];

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $totalFee;

    /**
     * @var integer
     */
    private $transferedFee;

    /**
     * @var array element is array, describe as below:
     *              [
     *                  $confirmTimestamp,  // timestamp
     *                  $fee,               // integer
     *                  $evidenceUrl        // string, begin with http:// or https://
     *              ]
     */
    private $financialActionResult;

    /**
     * @var \Jihe\Entities\Activity
     */
    private $activity;

    /**
     * @var \Jihe\Entities\Team
     */
    private $team;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var \DateTime
     */
    private $enrollEndTime;

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
     * @return ActivityEnrollIncome
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get totalFee
     *
     * @return integer
     */
    public function getTotalFee()
    {
        return $this->totalFee;
    }

    /**
     * Set totalFee
     *
     * @param integer $totalFee
     * @return ActivityEnrollIncome
     */
    public function setTotalFee($totalFee)
    {
        $this->totalFee = $totalFee;
        return $this;
    }

    /**
     * Get transferedFee
     *
     * @return integer
     */
    public function getTransferedFee()
    {
        return $this->transferedFee;
    }

    /**
     * Set transferedFee
     *
     * @param integer $transferedFee
     * @return ActivityEnrollIncome
     */
    public function setTransferedFee($transferedFee)
    {
        $this->transferedFee = $transferedFee;
        return $this;
    }

    /**
     * Get financialActionResult
     *
     * @return array each item is a url
     */
    public function getFinancialActionResult()
    {
        return $this->financialActionResult;    
    }

    /**
     * Set financialActionResult
     *
     * @param array $financialActionResult
     * @return ActivityEnrollIncome
     */
    public function setFinancialActionResult($financialActionResult)
    {
        $this->financialActionResult = $financialActionResult;
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
     * @return ActivityEnrollIncome
     */
    public function setActivity(Activity $activity)
    {
        $this->activity = $activity;
        return $this;
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
     * @param \Jihe\Entities\Team
     * @return ActivityEnrollIncome
     */
    public function setTeam(Team $team)
    {
        $this->team = $team;
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
     *
     * @return ActivityEnrollIncome
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get status desc
     *
     * @return string
     */
    public function getStatusDesc()
    {
        return array_get(self::$statusDesc, $this->status);
    }

    /**
     * get activity enroll end time
     *
     * @return \DateTime
     */
    public function getEnrollEndTime()
    {
        return $this->enrollEndTime;
    }

    /**
     * set activity enroll end time
     *
     * @param \DateTime $enrollEndTime
     *
     * @return ActivityEnrollIncome
     */
    public function setEnrollEndTime(\DateTime $enrollEndTime)
    {
        $this->enrollEndTime = $enrollEndTime;
        return $this;
    }

    /**
     * Get all status and desc map
     *
     * @return array
     */
    public static function getStatusDescMap()
    {
        return self::$statusDesc;
    }
}
