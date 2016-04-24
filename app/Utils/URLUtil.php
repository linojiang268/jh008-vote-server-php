<?php

namespace Jihe\Utils;

final class URLUtil
{

    public static function parseURLParamsToArray($url)
    {
        try {
            $queryParts = explode('&', parse_url($url, PHP_URL_QUERY));
            $params = [];
            foreach ($queryParts as $param) {
                $item = explode('=', $param);
                if (array_key_exists($item[0], $params)) {
                    if (is_array($params[$item[0]])) {
                        array_push($params[$item[0]], $item[1]);
                    } else {
                        $params[$item[0]] = [$params[$item[0]], $item[1]];
                    }
                } else {
                    $params[$item[0]] = $item[1];
                }
            }
            return $params;
        } catch (\Exception $e) {
            throw new \Exception('URL解析失败');
        }
    }

}
