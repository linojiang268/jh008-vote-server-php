<?php
namespace Jihe\Entities;

class LoginDevice
{
    const SOURCE_CLIENT = 1;
    const SOURCE_BACKSTAGE = 2;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $mobile;

    /**
     * @var integer
     */
    private $source;

    /**
     * 48 bit string
     * @var string
     */
    private $identifier;

    /**
     * identifier beloong the user who login last time
     * 48 bit string
     * @var string
     */
    private $oldIdentifier;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return LoginDevice
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get user mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set user mobile
     *
     * @param string $mobile
     *
     * @return LoginDevice
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }

    /**
     * Get user login source
     *
     * @return integer
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set user login source
     *
     * @param integer $source
     *
     * @return LoginDevice
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Get device unique identifier
     *
     * @return string   48 bit string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set device unique identifier
     *
     * @param string $identifier    48 bit string, start with mobile
     *
     * @return LoginDevice
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * check whether identifier passed in equals to identifier propery
     *
     * @param string $identifier    identifier should be check
     *
     * @return boolean
     */
    public function checkIdentifier($identifier)
    {
        return $this->identifier == $identifier;
    }

    /**
     * Get oldIdentifier.
     *
     * @return oldIdentifier.
     */
    function getOldIdentifier()
    {
        return $this->oldIdentifier;
    }

    /**
     * Set oldIdentifier.
     *
     * @param oldIdentifier the value to set.
     *
     * @return static.
     */
    function setOldIdentifier($oldIentifier)
    {
        $this->oldIdentifier = $oldIentifier;
        return $this;
    }
}
