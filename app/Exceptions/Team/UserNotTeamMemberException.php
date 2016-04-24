<?php
namespace Jihe\Exceptions\Team;

use Jihe\Exceptions\AppException;
use Jihe\Exceptions\ExceptionCode;

class UserNotTeamMemberException extends AppException
{
    public function __construct($message = '用户非社团成员')
    {
        parent::__construct($message, ExceptionCode::USER_NOT_TEAM_MEMBER);
    }
}