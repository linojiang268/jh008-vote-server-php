<?php
namespace Jihe\Entities;

class ActivityPlan
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $activityId;

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
    private $planText;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return ActivityPlan
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getActivityId()
    {
        return $this->activityId;
    }

    /**
     * @param int $activityId
     *
     * @return ActivityPlan
     */
    public function setActivityId($activityId)
    {
        $this->activityId = $activityId;

        return $this;
    }

    /**
     * @return string
     *
     * @return string
     */
    public function getPlanText()
    {
        return $this->planText;
    }

    /**
     * @param string $planText
     *
     * @return ActivityPlan
     */
    public function setPlanText($planText)
    {
        $this->planText = $planText;

        return $this;
    }

    /**
     * @return string
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param string $endTime
     *
     * @return ActivityPlan
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getBeginTime()
    {
        return $this->beginTime;
    }

    /**
     * @param string $beginTime
     *
     * @return ActivityPlan
     */
    public function setBeginTime($beginTime)
    {
        $this->beginTime = $beginTime;

        return $this;
    }

}