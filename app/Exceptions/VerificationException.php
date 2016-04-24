<?php
namespace Jihe\Exceptions;

class VerificationException extends AppException
{
    public function __construct($message = '验证码错误')
    {
        parent::__construct($message, ExceptionCode::INCORRECT_VERIFICATION);
    }
}