<?php
namespace Jihe\Entities;

class News
{
    const CLICK_NUM_CACHE_KEY = 'news_detail_click_num';

    private $id;
    private $title;
    private $content;
    private $coverUrl;
    private $publisher;
    private $activity;
    private $team;
    private $publishTime;
    private $clickNum;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return \Jihe\Entities\News
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return \Jihe\Entities\News
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return \Jihe\Entities\News
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getCoverUrl()
    {
        return $this->coverUrl;
    }

    /**
     * @param string $coverUrl
     *
     * @return \Jihe\Entities\News
     */
    public function setCoverUrl($coverUrl)
    {
        $this->coverUrl = $coverUrl;
        return $this;
    }

    /**
     * @return \Jihe\Entities\User
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * @param \Jihe\Entities\User $publisher
     * @return \Jihe\Entities\News
     */
    public function setPublisher($publisher)
    {
        $this->publisher = $publisher;
        return $this;
    }

    /**
     * reutrn \Jihe\Entities\Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @param \Jihe\Entities\Activity $activity
     * @return \Jihe\Entities\News
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;
        return $this;
    }

    /**
     * @return \Jihe\Entities\Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @param \Jihe\Entities\Team $team
     * @return \Jihe\Entities\News
     */
    public function setTeam($team)
    {
        $this->team = $team;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPublishTime()
    {
        return $this->publishTime;
    }

    /**
     * @param \DateTime $publishTime
     * @return News
     */
    public function setPublishTime($publishTime)
    {
        $this->publishTime = $publishTime;
        return $this;
    }

    /**
     * @return int $clickNum
     */
    public function getClickNum()
    {
        return $this->clickNum;
    }

    /**
     * @param int $clickNum
     * @return News
     */
    public function setClickNum($clickNum)
    {
        $this->clickNum = $clickNum;
        return $this;
    }

}