<?php
namespace Jihe\Entities;

class UserTag
{
    private $id;
    private $name;
    private $resourceUrl;


    /**
     * Get tag id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set tag id
     *
     * @return \Jihe\Entities\UserTag
     */
    public function setId($id)
    {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * Get tag name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set tag name
     *
     * @return \Jihe\Entities\UserTag
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get remote resource uri
     */
    public function getResourceUrl()
    {
        return $this->resourceUrl;
    }
    
    /**
     * Set remote resource uri
     *
     * @return \Jihe\Entities\UserTag
     */
    public function setResourceUrl($resourceUrl)
    {
        $this->resourceUrl = $resourceUrl;
        return $this;
    }
}
