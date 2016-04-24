<?php
namespace Jihe\Entities;

class ActivityMember
{
    const UNGROUPED = 0;

    const ROLE_NORMAL = 0;
    const ROLE_MANAGER = 1;

    const CHECKIN_WAIT = 0;
    const CHECKIN_DONE = 1;

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var \Jihe\Entities\Activity;
     */
    protected $activity;

    /**
     * @var \Jihe\Entities\User;
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
     * @var array
     */
    protected $attrs;

    /**
     * @var integer
     */
    protected $groupId;

    /**
     * @var integer
     */
    protected $role;

    /**
     * @var integer
     */
    protected $score;

    /**
     * @var array
     */
    protected $scoreAttributes;

    /**
     * @var string
     */
    protected $scoreMemo;

    /**
     * @var integer
     */
    protected $checkin;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $checkins;

    /**
     * Get id.
     *
     * @return integer          activity memeber id.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param integer $id       activity member id.
     *
     * @return static.
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * Get mobile.
     *
     * @return string.
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
     * @return string.
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
     * @return array            format as below
     *                          [
     *                              ['key' => ‘身高', 'value' => '170cm'],
     *                              ...
     *                          ]
     */
    public function getAttrs()
    {
        return $this->attrs;
    }

    /**
     * Set attrs.
     *
     * @param array attrs       format as below
     *                          [
     *                              ['key' => ‘身高', 'value' => '170cm'],
     *                              ...
     *                          ]
     *
     * @return static.
     */
    public function setAttrs($attrs)
    {
        $this->attrs = $attrs;
        return $this;
    }

    /**
     * Get groupId.
     *
     * @return integer.
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set groupId.
     *
     * @param integer $groupId.
     *
     * @return static.
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
        return $this;
    }

    /**
     * Get role.
     *
     * @return integer.
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set role.
     *
     * @param integer $role.
     *
     * @return static.
     */
    public function setRole($role)
    {
        $this->role = $role;
        return $this;
    }
 
    /**
     * Get score.
     *
     * @return integer.
     */
    public function getScore()
    {
        return $this->score;
    }
 
    /**
     * Set score.
     *
     * @param integer $score.
     *
     * @return static.
     */
    public function setScore($score)
    {
        $this->score = $score;
        return $this;
    }
 
    /**
     * Get scoreAttributes.
     *
     * @return array.
     */
    public function getScoreAttributes()
    {
        return $this->scoreAttributes;
    }
 
    /**
     * Set scoreAttributes.
     *
     * @param array $scoreAttributes.
     *
     * @return static.
     */
    public function setScoreAttributes($scoreAttributes)
    {
        $this->scoreAttributes = $scoreAttributes;
        return $this;
    }
 
    /**
     * Get scoreMemo.
     *
     * @return string.
     */
    public function getScoreMemo()
    {
        return $this->scoreMemo;
    }
 
    /**
     * Set scoreMemo.
     *
     * @param string $scoreMemo.
     *
     * @return static.
     */
    public function setScoreMemo($scoreMemo)
    {
        $this->scoreMemo = $scoreMemo;
        return $this;
    }
 
    /**
     * Get checkin.
     *
     * @return integer.
     */
    public function getCheckin()
    {
        return $this->checkin;
    }
 
    /**
     * Set checkin.
     *
     * @param integer $checkin.
     *
     * @return static.
     */
    public function setCheckin($checkin)
    {
        $this->checkin = $checkin;
        return $this;
    }
 
    /**
     * Get checkins.
     *
     * @return \Illuminate\Support\Collection.
     */
    public function getCheckins()
    {
        return $this->checkins;
    }
 
    /**
     * Set checkins.
     *
     * @param \Illuminate\Support\Collection $checkins.
     *
     * @return static.
     */
    public function setCheckins($checkins)
    {
        $this->checkins = $checkins;
        return $this;
    }
}
