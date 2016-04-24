<?php

return [
    // the elastic search servers
    'servers' => [
        [
            'host' => env('SEARCH_HOST', 'localhost'),
            'port' => env('SEARCH_PORT', '9200'),
        ],
    ]

];
