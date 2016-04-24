<?php
namespace Jihe\Entities;

class Question
{
    const QUESTION_TYPE_TEXT = 1;
    const QUESTION_TYPE_RADIO = 2;
    const QUESTION_TYPE_CHECK = 3;

    const SOURCE_ACTIVITY = 1;
    const SOURCE_TEAM = 2;

    private $id;
    private $content;
    private $type;  // 1 填空 2 单选 3 复选
    private $source; // 1 activity 2 team
    private $relate_id;
    private $pid;

    /**
     * Get city
     *
     * @return \Jihe\Entities\City
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set city
     *
     * @param  \Jihe\Entities\City $city
     *
     * @return Activity
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }


    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @param int $pid
     *
     * @return $this
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
        return $this;
    }

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
     * @return Question
     */
    public function setId($id)
    {
        $this->id = $id;
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
     *
     * @return Question
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return \Jihe\Entities\QuestionType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param \Jihe\Entities\QuestionType  $type
     *
     * @return Question
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param int $source
     *
     * @return Question
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @return int
     */
    public function getRelateId()
    {
        return $this->relate_id;
    }

    /**
     * @param int $relate_id
     *
     * @return Question
     */
    public function setRelateId($relate_id)
    {
        $this->relate_id = $relate_id;
        return $this;
    }

}
