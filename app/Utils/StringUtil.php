<?php
namespace Jihe\Utils;

use Illuminate\Support\Str;

final class StringUtil
{
    /**
     * generate random text 
     * 
     * @param int $length     length of the generated text
     * @param string $pool    character pool
     * 
     * @return string  generated random text
     */
    public static function quickRandom($length = 6, $pool = null)
    {
        if (empty($pool)) {
            return Str::quickRandom($length);
        }
        
        $repeats = ceil($length / strlen($pool));
        return substr(str_shuffle(str_repeat($pool, $repeats)), 0, $length);
    }

    public static function safeJsonDecode($json, $asArray = true)
    {
        $decoded = json_decode($json, $asArray);
        if ($decoded === null  && json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }else{
            return $decoded;
        }
    }
}