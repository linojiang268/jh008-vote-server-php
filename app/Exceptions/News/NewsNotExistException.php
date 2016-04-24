<?php
namespace Jihe\Exceptions\News;

use Jihe\Exceptions\AppException;
use Jihe\Exceptions\ExceptionCode;

/**
 * this exception will be thrown if the operated news not exists.
 */
class NewsNotExistException extends AppException
{
    public function __construct($message = '该资讯不存在')
    {
        parent::__construct($message, ExceptionCode::NEWS_NOT_EXIST);
    }
}