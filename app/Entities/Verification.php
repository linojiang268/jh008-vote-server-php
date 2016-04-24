<?php
namespace Jihe\Entities;

class Verification
{
    /**
     * default expire interval (in seconds)
     * @var int
     */
    const DEFAULT_EXPIRE_INTERVAL  =  600;

    const DEFAULT_EXPIRED_AT = 600;
    
    private $id;
    private $mobile;
    private $code;
    private $expiry;
    
    public function __construct($id, $mobile, $code, $expiry)
    {
        $this->id = $id;
        $this->mobile = $mobile;
        $this->code = $code;
        $this->expiry = $expiry;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * get the verification code
     *
     * @return string  the verification code
     */
    public function getCode()
    {
        return $this->code;
    }
    
    /**
     * is the verification code expired
     *
     * @param $deadline   the deadline to check, in 'Y-m-d H:i:s' format if specified
     *
     * @return boolean  true if the code expires, false otherwise
     */
    public function isExpired($deadline = null)
    {
        return $this->expiry < ($deadline ?: date('Y-m-d H:i:s'));
    }
}
