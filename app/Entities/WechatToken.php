<?php
namespace Jihe\Entities;

/**
 * WechatToken
 */
class WechatToken
{
    /**
     * @var string
     */
    private $openid;

    /**
     * @var string
     */
    private $webTokenAccess;

    /**
     * @var \DateTime
     */
    private $webTokenExpireAt;

    /**
     * @var string
     */
    private $webTokenRefresh;

    /**
     * @var string
     */
    private $tokenAccess;

    /**
     * @var \DateTime
     */
    private $tokenExpireAt;
 
    /**
     * Get openid.
     *
     * @return openid.
     */
    function getOpenid()
    {
        return $this->openid;
    }
 
    /**
     * Set openid.
     *
     * @param openid the value to set.
     *
     * @return static.
     */
    function setOpenid($openid)
    {
        $this->openid = $openid;
        return $this;
    }
 
    /**
     * Get webTokenAccess.
     *
     * @return webTokenAccess.
     */
    function getWebTokenAccess()
    {
        return $this->webTokenAccess;
    }
 
    /**
     * Set webTokenAccess.
     *
     * @param webTokenAccess the value to set.
     *
     * @return static.
     */
    function setWebTokenAccess($webTokenAccess)
    {
        $this->webTokenAccess = $webTokenAccess;
        return $this;
    }
 
    /**
     * Get webTokenExpireAt.
     *
     * @return webTokenExpireAt.
     */
    function getWebTokenExpireAt()
    {
        return $this->webTokenExpireAt;
    }
 
    /**
     * Set webTokenExpireAt.
     *
     * @param webTokenExpireAt the value to set.
     *
     * @return static.
     */
    function setWebTokenExpireAt($webTokenExpireAt)
    {
        $this->webTokenExpireAt = $webTokenExpireAt;
        return $this;
    }
 
    /**
     * Get webTokenRefresh.
     *
     * @return webTokenRefresh.
     */
    function getWebTokenRefresh()
    {
        return $this->webTokenRefresh;
    }
 
    /**
     * Set webTokenRefresh.
     *
     * @param webTokenRefresh the value to set.
     *
     * @return static.
     */
    function setWebTokenRefresh($webTokenRefresh)
    {
        $this->webTokenRefresh = $webTokenRefresh;
        return $this;
    }
 
    /**
     * Get tokenAccess.
     *
     * @return tokenAccess.
     */
    function getTokenAccess()
    {
        return $this->tokenAccess;
    }
 
    /**
     * Set tokenAccess.
     *
     * @param tokenAccess the value to set.
     *
     * @return static.
     */
    function setTokenAccess($tokenAccess)
    {
        $this->tokenAccess = $tokenAccess;
        return $this;
    }
 
    /**
     * Get tokenExpireAt.
     *
     * @return tokenExpireAt.
     */
    function getTokenExpireAt()
    {
        return $this->tokenExpireAt;
    }
 
    /**
     * Set tokenExpireAt.
     *
     * @param tokenExpireAt the value to set.
     *
     * @return static.
     */
    function setTokenExpireAt($tokenExpireAt)
    {
        $this->tokenExpireAt = $tokenExpireAt;
        return $this;
    }
}
