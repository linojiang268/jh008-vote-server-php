<?php
namespace Jihe\Entities;

class PromotionActivityManager
{
    const STATUS_NORMAL = 0;
    const STATUS_FORBIDDEN = 1;

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $activityName;
    
    /**
     * @var string
     */
    protected $activityDesc;

    /**
     * @var string
     */
    protected $templateSegment;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var status
     */
    protected $status;

 
    /**
     * Get id.
     *
     * @return integer id.
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
     * Get activityName.
     *
     * @return string activityName.
     */
    public function getActivityName()
    {
        return $this->activityName;
    }
 
    /**
     * Set activityName.
     *
     * @param string $activityName.
     *
     * @return static.
     */
    public function setActivityName($activityName)
    {
        $this->activityName = $activityName;
        return $this;
    }

    /**
     * Get activityDesc.
     *
     * @return string activityDesc.
     */
    public function getActivityDesc()
    {
        return $this->activityDesc;
    }
 
    /**
     * Set activityDesc.
     *
     * @param string $activityDesc.
     *
     * @return static.
     */
    public function setActivityDesc($activityDesc)
    {
        $this->activityDesc = $activityDesc;
        return $this;
    }

    /**
     * Get templateSegment.
     *
     * @return string templateSegment.
     */
    public function getTemplateSegment()
    {
        return $this->templateSegment;
    }
 
    /**
     * Set templateSegment.
     *
     * @param string $templateSegment.
     *
     * @return static.
     */
    public function setTemplateSegment($templateSegment)
    {
        $this->templateSegment = $templateSegment;
        return $this;
    }
 
    /**
     * Get manager name.
     *
     * @return string name.
     */
    public function getName()
    {
        return $this->name;
    }
 
    /**
     * Set manager name.
     *
     * @param string $name  manager name.
     *
     * @return static.
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
 
    /**
     * Get password.
     *
     * @return string password.
     */
    public function getPassword()
    {
        return $this->password;
    }
 
    /**
     * Set password.
     *
     * @param string $password  user password.
     *
     * @return static.
     */
    public function setPassword($password)
    {
        $this->password = $password;
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
}
