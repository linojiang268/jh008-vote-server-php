<?php
namespace Jihe\Exceptions;

class SignatureException extends AppException
{
    public function __construct($message = '签名非法')
    {
        parent::__construct($message, ExceptionCode::INCORRECT_REQUEST_SIGN);
    }
}