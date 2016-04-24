<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | name/key for signature in the request
    |--------------------------------------------------------------------------
    |
    */
    'param' => 'sign',

    /*
    |--------------------------------------------------------------------------
    | key used to verify the request or sign the response
    |--------------------------------------------------------------------------
    | This key is used for caculating request parameters signature
    | 32 character string
    |
    */
    'key'  => env('SIGN_KEY', 'RandomSignKey'),
    
    // name for the key to be added in order to verify the signature
    'name' => 'key',

    /*
    |--------------------------------------------------------------------------
    | Signature method 
    |--------------------------------------------------------------------------
    | Here you may configure the signature method 
    |
    | Available methods: "md5", "sha1", "sha256", "sha512"
    */
    'method' => 'sha512',
];
