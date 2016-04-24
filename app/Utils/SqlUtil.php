<?php
namespace Jihe\Utils;

abstract class SqlUtil
{
    /**
     * escape magic characters (%, _) in mysql
     *
     * @param string $text  text to be escaped
     * @return string       escaped text
     */
    public static function escape($text)
    {
        $search  = ['_' , '%' ];
        $replace = ['\_', '\%'];

        return str_replace($search, $replace, $text);
    }
}