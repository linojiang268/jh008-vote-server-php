<?php

return [
    // the minimum time interval (in seconds) after which message is
    // allowed to be sent since last. 0 indicates no limit
    'send_interval' => env('SMS_SEND_INTERVAL', 120),

    // after expired_at seconds, message should be expired
    'expired_at' => env('SMS_EXPIRED_AT', 600),
    
    // the maximum number of messages that can be sent within a period
    // of time (in seconds). for instance, ['limit_period' => 86400, 'limit_count' => 10]
    // tells that user can be sent 10 messages per day. 
    // 0 of either field indicates no limit.
    'limit_period' => env('SMS_LIMIT_PERIOD', 86400),
    'limit_count'  => env('SMS_LIMIT_COUNT',  10),
];
