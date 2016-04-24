<?php
namespace Jihe\Exceptions\Activity;

use Jihe\Exceptions\AppException;
use Jihe\Exceptions\ExceptionCode;

class UserNotActivityMemberException extends AppException
{
    public function __construct($message = '用户非活动成员')
    {
        parent::__construct($message, ExceptionCode::USER_NOT_ACTIVITY_MEMBER);
    }
}