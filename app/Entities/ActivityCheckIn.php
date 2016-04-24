<?php
namespace Jihe\Entities;


class ActivityCheckIn
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var \Jihe\Entities\User.
     */
    protected $user;

    /**
     * @var \Jihe\Entities\Activity.
     */
    protected $activity;

    /**
     * @var integer
     */
    protected $step;

    /**
     * @var integer
     */
    protected $processId;
 
    /**
     * Get id.
     *
     * @return integer.
     */
    public function getId()
    {
        return $this->id;
    }
 
    /**
     * Set id.
     *
     * @param integer $id.
     *
     * @return static.
     */
    public function setId($id)
    {
        $this->id = $id;
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
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }
 
    /**
     * Get activity.
     *
     * @return \Jihe\Entities\Activity.
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
    public function setActivity($activity)
    {
        $this->activity = $activity;
        return $this;
    }
 
    /**
     * Get step.
     *
     * @return integer.
     */
    public function getStep()
    {
        return $this->step;
    }
 
    /**
     * Set step.
     *
     * @param integer $step.
     *
     * @return static.
     */
    public function setStep($step)
    {
        $this->step = $step;
        return $this;
    }
 
    /**
     * Get processId.
     *
     * @return integer.
     */
    public function getProcessId()
    {
        return $this->processId;
    }
 
    /**
     * Set processId.
     *
     * @param integer $processId.
     *
     * @return static.
     */
    public function setProcessId($processId)
    {
        $this->processId = $processId;
        return $this;
    }
}
