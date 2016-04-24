<?php
namespace Jihe\Utils;

final class PaginationUtil
{
    public static function count2Pages($count, $size)
    {
        return ceil($count / $size);
    }

    public static function genValidPage($page, $count, $size)
    {
        $pages = self::count2Pages($count, $size);
        return $page <= $pages ? $page : $pages;
    }
}
