<?php
namespace Jihe\Entities;

class ActivityAlbumImage
{
    /**
     * image uploaded by activity sponsor
     */
    const SPONSOR = 0;
    
    /**
     * iamge uploaded by activity members
     */
    const USER = 1;
    
    /**
     * image status is pending, unvisible for user
     */
    const STATUS_PENDING  = 0;
    
    /**
     * image status is approved, visible for user
     */
    const STATUS_APPROVED = 1;

    const THUMBNAIL_STYLE_FOR_ALBUM_IMAGE = '@720w_720h_0e_1pr.jpg';

    public static function isValidCreatorType($creatorType)
    {
        return in_array($creatorType, [
            self::SPONSOR,
            self::USER,
        ]);
    }
    
    public static function isValidStatus($status)
    {
        return in_array($status, [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
        ]);
    }

    private $id;
    private $activity;
    private $creatorType;
    private $creator;
    private $imageUrl;
    private $status;
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
     * @return \Jihe\Entities\Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    public function setActivity(Activity $activity)
    {
        $this->activity = $activity;
        return $this;
    }

    public function getCreatorType()
    {
        return $this->creatorType;
    }

    public function setCreatorType($creatorType)
    {
        $this->creatorType = $creatorType;
        return $this;
    }

    /**
     * @return \Jihe\Entities\User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    public function setCreator($creator)
    {
        $this->creator = $creator;
        return $this;
    }

    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getImageUrlOfThumbnail()
    {
        return $this->imageUrl . self::THUMBNAIL_STYLE_FOR_ALBUM_IMAGE;
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
    
    public function isPending()
    {
        return self::STATUS_PENDING == $this->getStatus();
    }
    
    public function isSponsor()
    {
        return self::SPONSOR == $this->getCreatorType();
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
