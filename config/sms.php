<?php

return [
    'drivers' => [
        'default' => 'guodu',

        'webchinese' => [ // configuration for WebChinese
            'account' => env('SMS_ACCOUNT'),
            'key'     => env('SMS_PASSWORD'),
        ],

        'guodu' => [
            'account'   => env('SMS_ACCOUNT'),
            'pass'      => env('SMS_PASSWORD'),
            'affix'     => env('SMS_GUODU_AFFIX', ''),
            'signature' => env('SMS_SIGNATURE', '【集合】'),
        ],

        'chuanglan' => [
            'account'   => env('SMS_ACCOUNT'),
            'pass'      => env('SMS_PASSWORD'),
            'affix'     => env('SMS_CHUANGLAN_AFFIX', null),
        ],
    ],

    'config' => [
        'no_sending' => env('SMS_NO_SENDING', false),
    ],

];
