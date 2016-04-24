<?php
namespace Jihe\Exceptions\User;

use Jihe\Exceptions\AppException;
use Jihe\Exceptions\ExceptionCode;

/**
 * this exception will be thrown if necessary information of the user
 * is missing.
 */
class UserInfoIncompleteException extends AppException
{
    public function __construct($message = '用户资料不完整')
    {
        parent::__construct($message, ExceptionCode::USER_INFO_INCOMPLETE);
    }
}