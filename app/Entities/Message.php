<?php
namespace Jihe\Entities;

class Message
{
    const TYPE_TEXT = 'text';
    const TYPE_URL = 'url';
    const TYPE_TEAM = 'team';
    const TYPE_ACTIVITY = 'activity';

    //only push
    const TYPE_KICK = 'kick';
    //app upgrade
    const TYPE_VERSION_UPGRADE = 'version_upgrade';

    const NOTIFIED_TYPE_SMS = 'sms';
    const NOTIFIED_TYPE_PUSH = 'push';

    private $id;
    private $team;
    private $activity;
    private $user;
    private $content;
    private $type = 'text';
    private $attributes;
    private $notifiedType = 'push';
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

    /**
     * @return \Jihe\Entities\Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    public function setTeam(\Jihe\Entities\Team $team)
    {
        $this->team = $team;
        return $this;
    }

    /**
     * @return \Jihe\Entities\Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    public function setActivity(\Jihe\Entities\Activity $activity)
    {
        $this->activity = $activity;
        return $this;
    }
    
    /**
     * @return \Jihe\Entities\User
     */
    public function getUser()
    {
        return $this->user;
    }
    
    public function setUser(\Jihe\Entities\User $user)
    {
        $this->user = $user;
        return $this;
    }
    
    public function getContent()
    {
        return $this->content;
    }
    
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }
    
    public function getType()
    {
        return $this->type;
    }
    
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    
    public function getAttributes()
    {
        return $this->attributes;
    }
    
    public function setAttributes($attributes = null)
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @return string
     */
    public function getNotifiedType()
    {
        return $this->notifiedType;
    }

    /**
     * @param string $notifiedType
     * @return Message
     */
    public function setNotifiedType($notifiedType)
    {
        $this->notifiedType = $notifiedType;
        return $this;
    }
    
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }
    
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
