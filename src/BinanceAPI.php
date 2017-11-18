<?php 
namespace adman9000\binance;

class BinanceAPI
{
    protected $key;     // API key
    protected $secret;  // API secret
    protected $url;     // API base URL
    protected $version; // API version
    protected $curl;    // curl handle

    /**
     * Constructor for BinanceAPI
     *
     */
    function __construct()
    {
        $this->key = config('binance.binance_key');
        $this->secret = config('binance.binance_secret');
        $this->url = config('binance.urls.api');
        $this->curl = curl_init();

        curl_setopt_array($this->curl, array(
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Binance PHP API Agent',
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true)
        );
    }

    function __destruct()
    {
        curl_close($this->curl);
    }
	
	
}