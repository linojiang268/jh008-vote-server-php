<?php
namespace Jihe\Entities;

use Illuminate\Support\Collection;
use Crypt;

class User
{
    /**
     * registration is done, but information is incomplete
     *
     * @var int
     */
    const STATUS_INCOMPLETE = 0;
    const STATUS_NORMAL     = 1; // 正常用户
    const STATUS_FORBIDDEN  = 2; // 封号
    
    const TYPE_SELF   = 0;      // 自己注册
    const TYPE_INVITE = 1;      // 别人添加

    const GENDER_UNKNOWN = 0;   // 未知
    const GENDER_MALE   = 1;    // 男
    const GENDER_FEMALE = 2;    // 女

    private static $statusDescMap = [
        self::STATUS_INCOMPLETE => '待完善',
        self::STATUS_NORMAL     => '正常',
        self::STATUS_FORBIDDEN => '已封号',
    ];

    private static $genderDescMap = [
        self::GENDER_UNKNOWN    => '未知',
        self::GENDER_MALE       => '男',
        self::GENDER_FEMALE     => '女',
    ];

    const THUMBNAIL_STYLE_FOR_AVATAR = '@200w_200h_1e_1pr.src';

    const IDENTITY_KEY = 'identity';

    private $id;
    private $mobile;
    private $password;
    private $salt;
    private $identitySalt;
    private $type;
    private $nickName;
    private $gender;
    private $birthday;
    private $signature;
    private $avatarUrl;
    private $status;
    private $registerAt;

    private $tags;

    /**
     * Get the user code
     *
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user id
     *
     * @return \Jihe\Entities\User
     */
    public function setId($id)
    {
        $this->id = (int) $id;
        return $this;
    }

    /**
     * Get user mobile
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set user mobile
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }

    /**
     * get register type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * set register type
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get user nick name
     */
    public function getNickName()
    {
        return $this->nickName;
    }

    /**
     * Set user nick name
     */
    public function setNickName($nickName)
    {
        $this->nickName = $nickName;
        return $this;
    }

    /**
     * get gender
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
        return $this;
    }

    /**
     * Get gender desc
     *
     * @return string
     */
    public function getGenderDesc()
    {
        array_get(self::$genderDescMap, $this->gender);
    }

    /**
     * Get birthday
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set birthday
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;
        return $this;
    }

    /**
     * Get user custom signature
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Set user custom signature
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;
        return $this;
    }

    /**
     * Get avatar uri
     */
    public function getAvatarUrl()
    {
        return $this->avatarUrl;
    }

    public function getAvatarUrlOfThumbnail()
    {
        if (empty($this->avatarUrl)) {
            return $this->avatarUrl;
        }
        
        return $this->avatarUrl . self::THUMBNAIL_STYLE_FOR_AVATAR;
    }

    /**
     * Set avatar uri
     */
    public function setAvatarUrl($avatarUrl)
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    /**
     * Get user status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set user status
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Judge whether user complete profile
     *
     * @return boolen
     */
    public function isNeedComplete()
    {
        return $this->status == self::STATUS_INCOMPLETE;
    }

    /**
     * Get user status desc
     */
    public function getStatusDesc()
    {
        return array_get(self::$statusDescMap, $this->status);
    }

    /**
     * Get tags
     *
     * @return \Illuminate\Support\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set tags
     *
     * @return \Jihe\Entities\User
     */
    public function setTags(Collection $tags)
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Get user register time
     *
     * @return \DateTime
     */
    public function getRegisterAt()
    {
        return $this->registerAt;
    }

    /**
     * Set user register time
     *
     * @param \DateTime $registerAt
     *
     * @return \Jihe\Entities\User
     */
    public function setRegisterAt($registerAt)
    {
        $this->registerAt = $registerAt;
        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getHashedPassword()
    {
        return $this->password;
    }

    /**
     * Set Password
     *
     * @param string $password
     *
     * @return \Jihe\Entities\User
     */
    public function setHashedPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Get salt
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set salt
     *
     * @param string $salt
     *
     * @return \Jihe\Entities\User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
        return $this;
    }

    /**
     * Get identity salt
     *
     * @return string
     */
    public function getIdentitySalt()
    {
        return $this->identitySalt;
    }

    /**
     * Get identity salt
     *
     * @return string|null
     */
    public function getIdentity()
    {
        if (empty($this->id) || empty($this->identitySalt)) {
            return null;
        }

        return Crypt::encrypt(json_encode([
            'key'  => self::IDENTITY_KEY,
            'uid'  => $this->id,
            'salt' => $this->identitySalt,
        ]));
    }

    /**
     * Set identity salt
     *
     * @param string $identitySalt
     *
     * @return \Jihe\Entities\User
     */
    public function setIdentitySalt($identitySalt)
    {
        $this->identitySalt = $identitySalt;
        return $this;
    }
}
