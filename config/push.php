<?php

return [
    'drivers' => [
        'default' => 'yunba',
        'yunba'   => [
            'config' => [
                'app' => [
                    'appKey'    => env('PUSH_APP_KEY'),
                    'secretKey' => env('PUSH_SECRET_KEY'),
                ],
                'appStore' => [
                    'appKey'    => env('APP_STORE_PUSH_APP_KEY'),
                    'secretKey' => env('APP_STORE_PUSH_SECRET_KEY'),
                ],
            ],
            'signature' => env('PUSH_SIGNATURE', ''),
            'shutdownConfig' => explode(',', env('PUSH_SHUT_DOWN_CONFIG', '')),
        ],
    ],

    'config'  => [
    'no_push' => env('PUSH_NO_SENDING', false),
],

];
