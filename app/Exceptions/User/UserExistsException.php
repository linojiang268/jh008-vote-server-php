<?php
namespace Jihe\Exceptions\User;

use Jihe\Exceptions\AppException;

/**
 * this exception will be thrown if user exists when registering
 * a new user.
 */
class UserExistsException extends AppException
{
    private $mobile; 

    public function __construct($mobile, $message = '用户已存在')
    {
        $this->mobile = $mobile;    
        
        parent::__construct($message, 1);
    }

    /**
     * get user's mobile, which can identify the user
     * 
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }
}