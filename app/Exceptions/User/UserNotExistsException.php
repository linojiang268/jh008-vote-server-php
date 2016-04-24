<?php
namespace Jihe\Exceptions\User;

use Jihe\Exceptions\AppException;

class UserNotExistsException extends AppException
{
    private $mobile; 

    public function __construct($mobile, $message = '用户不存在')
    {
        $this->mobile = $mobile;    
        
        parent::__construct($message, 2);
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