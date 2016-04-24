<?php

return [
    
    'alipay' => [
        // partner's id (under contract to Alipay). a 16-digit numbers, starting with '2088'
        'partner' => env('ALIPAY_PARTENER'),
        
        // seller's account on alipay, e.g., email/mobile
        'seller'  => env('ALIPAY_SELLER'),
        
        // secure key (安全码) that goes with alipay account
        'secure_key' => env('ALIPAY_SECURE_KEY'),
        
        // url to be notified (asynchronously) when trade status changes, 
        // should start with http:// or https://
        'notify_url' => env('ALIPAY_NOTIFY_URL'),
        
        // url to be notified (asynchronously) when trade refund status changes
        'refund_notify_url' => env('ALIPAY_REFUND_NOTIFY_URL'),
        
        // path to partner's private key. It's used to sign trade
        // data before sending to Alipay.
        'cert_file'  => env('ALIPAY_PARTENER_CERT_FILE'),
        
        // path to Alipay's public key, which is used to verify that the incoming
        // data is sent by Alipay.
        'ali_cert_file' => env('ALIPAY_ALI_CERT_FILE'),
    ],

    // For wx app payment
    'wx_app_pay' => [
        // unique identification in weixin open platform or weixin public account.
        'appid'         => env('WXPAY_APPID'),

        // commercial tenant (商户) id
        'mchid'        => env('WXPAY_MCHID'),

        // used for generating signature for transaction
        'key'           => env('WXPAY_KEY'),

        // file path to merchant's certificate
        'cert_file'     => env('WXPAY_MERCHANT_CERT_FILE'),

        // file path to merchant's private key.
        'sslkey_file'   => env('WXPAY_MERCHANT_SSLKEY_FILE'),

        // url to be notified when trade status changes
        'notify_url'    => env('WXPAY_NOTIFY_URL'),
    ],

    // For wx jsapi|native|micropay payment
    'wx_mp_pay' => [
        // unique identification in weixin mp platform or weixin public account.
        'appid'         => env('WXPAY_MP_APPID'),

        // commercial tenant (商户) id
        'mchid'         => env('WXPAY_MP_MCHID'),

        // used for generating signature for transaction
        'key'           => env('WXPAY_MP_KEY'),

        // file path to merchant's certificate
        'cert_file'     => env('WXPAY_MP_MERCHANT_CERT_FILE'),

        // file path to merchant's private key.
        'sslkey_file'   => env('WXPAY_MP_MERCHANT_SSLKEY_FILE'),

        // url to be notified when trade status changes
        'notify_url'    => env('WXPAY_MP_NOTIFY_URL'),
    ],

    'config' => [
        'expire_after'  => 1800,        // 60 * 30 seconds
    ],
];
