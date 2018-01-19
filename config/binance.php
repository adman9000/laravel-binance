<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Binance authentication
    |--------------------------------------------------------------------------
    |
    | Authentication key and secret for Binance API.
    |
     */

    'auth' => [
        'key'        => env('BINANCE_KEY', ''),
        'secret'     => env('BINANCE_SECRET', '')
    ],

    /*
    |--------------------------------------------------------------------------
    | API URLs
    |--------------------------------------------------------------------------
    |
    | Binance API endpoints
    |
     */

    'urls' => [
        'api'  => 'https://www.binance.com/api/',
        'wapi'  => 'https://www.binance.com/wapi/'
    ],


    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | Binance API settings
    |
     */

    'settings' => [
        'timing' => env('BINANCE_TIMING', 5000),
        'ssl'    => env('BINANCE_SSL_VERIFYPEER', true)
    ],

];
