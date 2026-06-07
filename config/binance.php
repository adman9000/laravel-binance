<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Binance API Authentication
    |--------------------------------------------------------------------------
    */

    'auth' => [
        'key'    => env('BINANCE_KEY', ''),
        'secret' => env('BINANCE_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | API URLs
    |--------------------------------------------------------------------------
    */

    'urls' => [
        'api'  => env('BINANCE_API_URL', 'https://api.binance.com/api/'),
        'sapi' => env('BINANCE_SAPI_URL', 'https://api.binance.com/sapi/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    */

    'settings' => [
        'timing'          => env('BINANCE_TIMING', 5000),
        'timeout'         => env('BINANCE_TIMEOUT', 30),
        'connect_timeout' => env('BINANCE_CONNECT_TIMEOUT', 10),
    ],

];
