<?php

return [
    'alioss' => [
        'server'         => env('OSS_SERVER'),
        'key'            => env('OSS_KEY'),
        'secret'         => env('OSS_SECRET'),
        'bucket'         => env('OSS_DEFAULT_BUCKET'),
        'base_url'       => env('OSS_BASE_URL'),
        'base_image_url' => env('OSS_BASE_IMAGE_URL'),
        'public_path'    => env('OSS_PUBLIC_PATH', ''),
    ],

];
