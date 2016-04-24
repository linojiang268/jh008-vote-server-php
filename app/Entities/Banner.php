<?php
namespace Jihe\Entities;

class Banner
{
    const TYPE_URL = 'url';
    const TYPE_TEAM = 'team';
    const TYPE_ACTIVITY = 'activity';

    private $id;
    private $city;
    private $imageUrl;
    private $type;
    private $attributes;
    private $memo;
    private $beginTime;
    private $endTime;

    const THUMBNAIL_STYLE_FOR_IMAGE = '@960w_480h_1e_90Q_1pr.jpg';

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return Banner
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return \Jihe\Entities\City
     */
    public function getCity()
    {
        return $this->city;
    }

    public function setCity(\Jihe\Entities\City $city = null)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * @param mixed $imageUrl
     * @return Banner
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getImageUrlOfThumbnail()
    {
        return $this->imageUrl . self::THUMBNAIL_STYLE_FOR_IMAGE;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return Banner
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param mixed $attributes
     * @return Banner
     */
    public function setAttributes($attributes = null)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * @param mixed $memo
     * @return Banner
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBeginTime()
    {
        return $this->beginTime;
    }

    /**
     * @param mixed $beginTime
     * @return Banner
     */
    public function setBeginTime($beginTime)
    {
        $this->beginTime = $beginTime;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param mixed $endTime
     * @return Banner
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }
}
