<?php
namespace Jihe\Entities;

/**
 * WechatUser
 */
class WechatUser
{
    const GENDER_UNKNOWN = 0;
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;

    const SUBSCRIBE_UNKNOWN = 0;
    const SUBSCRIBE_NO = 1;     // not subscribe wechat number
    const SUBSCRIBE_YES = 2;    // user aready subscribe wechat number

    /**
     * @var string
     */
    private $openid;

    /**
     * @var string
     */
    private $nickName;

    /**
     * @var integer 
     */
    private $gender;

    /**
     * @var string
     */
    private $country;

    /**
     * @var string
     */
    private $province;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $headimgurl;

    /**
     * @var string
     */
    private $unionid;

    //===================================================
    //  Fields below only available in wechat app client,
    //  not available in web oauth enviroment
    //===================================================
    /**
     * If subscribe is not unknown, it means user info aready be fetched
     * from wechat by client access token at least once
     *
     * @var integer|null
     */
    private $subscribe;

    /**
     * @var \DateTime|null
     */
    private $subscribeAt;

    /**
     * @var integer|null
     */
    private $groupid;

    /**
     * @var string|null
     */
    private $remark;
 
    /**
     * Get openid.
     *
     * @return string   user unique identifier in wechat mp number.
     */
    function getOpenid()
    {
        return $this->openid;
    }
 
    /**
     * Set openid.
     *
     * @param string $openid    user unique identifier in wechat mp number.
     *
     * @return static.
     */
    function setOpenid($openid)
    {
        $this->openid = $openid;
        return $this;
    }
 
    /**
     * Get nickName.
     *
     * @return string       user nickname in wechat
     */
    function getNickName()
    {
        return $this->nickName;
    }
 
    /**
     * Set nickName.
     *
     * @param string $nickName
     *
     * @return static.
     */
    function setNickName($nickName)
    {
        $this->nickName = $nickName;
        return $this;
    }
 
    /**
     * Get gender.
     *
     * @return integer          user gender.
     */
    function getGender()
    {
        return $this->gender;
    }
 
    /**
     * Set gender.
     *
     * @param integer gender
     *
     * @return static.
     */
    function setGender($gender)
    {
        $this->gender = $gender;
        return $this;
    }
 
    /**
     * Get country.
     *
     * @return string           country in user profile
     */
    function getCountry()
    {
        return $this->country;
    }
 
    /**
     * Set country.
     *
     * @param string $country
     *
     * @return static.
     */
    function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }
 
    /**
     * Get province.
     *
     * @return string           province in user profile.
     */
    function getProvince()
    {
        return $this->province;
    }
 
    /**
     * Set province.
     *
     * @param string $province
     *
     * @return static.
     */
    function setProvince($province)
    {
        $this->province = $province;
        return $this;
    }
 
    /**
     * Get city.
     *
     * @return string           city in user profile.
     */
    function getCity()
    {
        return $this->city;
    }
 
    /**
     * Set city.
     *
     * @param string $city
     *
     * @return static.
     */
    function setCity($city)
    {
        $this->city = $city;
        return $this;
    }
 
    /**
     * Get headimgurl.
     *
     * @return string           user headimgurl used for show user head image.
     */
    function getHeadimgurl()
    {
        return $this->headimgurl;
    }
 
    /**
     * Set headimgurl.
     *
     * @param string $headimgurl
     *
     * @return static.
     */
    function setHeadimgurl($headimgurl)
    {
        $this->headimgurl = $headimgurl;
        return $this;
    }
 
    /**
     * Get unionid.
     *
     * @return string|null      the unique user identifier in all wechat mp account 
     */
    function getUnionid()
    {
        return $this->unionid;
    }
 
    /**
     * Set unionid.
     *
     * @param string|null $unionid
     *
     * @return static.
     */
    function setUnionid($unionid)
    {
        $this->unionid = $unionid;
        return $this;
    }
 
    /**
     * Get subscribe.
     *
     * @return integer|null     whether user subscribe mp account
     *                          0 -- not subscribe
     *                          1 -- aready subscribe
     */
    function getSubscribe()
    {
        return $this->subscribe;
    }
 
    /**
     * Set subscribe.
     *
     * @param integer|null $subscribe
     *
     * @return static.
     */
    function setSubscribe($subscribe)
    {
        $this->subscribe = $subscribe;
        return $this;
    }
 
    /**
     * Get subscribeAt.
     *
     * @return \DateTime|null   the time user subscribe mp account.
     */
    function getSubscribeAt()
    {
        return $this->subscribeAt;
    }
 
    /**
     * Set subscribeAt.
     *
     * @param \DateTime|null $subscribeAt
     *
     * @return static.
     */
    function setSubscribeAt($subscribeAt)
    {
        $this->subscribeAt = $subscribeAt;
        return $this;
    }
 
    /**
     * Get groupid.
     *
     * @return string|null      user group id in mp account.
     */
    function getGroupid()
    {
        return $this->groupid;
    }
 
    /**
     * Set groupid.
     *
     * @param string|null $groupid
     *
     * @return static.
     */
    function setGroupid($groupid)
    {
        $this->groupid = $groupid;
        return $this;
    }
 
    /**
     * Get remark.
     *
     * @return remark.
     */
    function getRemark()
    {
        return $this->remark;
    }
 
    /**
     * Set remark.
     *
     * @param remark the value to set.
     *
     * @return static.
     */
    function setRemark($remark)
    {
        $this->remark = $remark;
        return $this;
    }
}
