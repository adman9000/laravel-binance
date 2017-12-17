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
        $this->key = config('binance.auth.key');
        $this->secret = config('binance.auth.secret');
        $this->url = config('binance.urls.api');
        $this->curl = curl_init();
        curl_setopt_array($this->curl, array(
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Binance PHP API Agent',
           // CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true)
        );
        
    }

    function __destruct()
    {
        curl_close($this->curl);
    }
	
	function setAPI($key, $secret) {

       $this->key = $key;
       $this->secret = $secret;
    }

     /**
     * Get ticker
     *
     * @return asset pair ticker info
     */
    public function getTicker()
    {
        return $this->request("v1/ticker/allPrices");
    }

     public function getCurrencies()
    {
       //Seems to be no such functionality
       return false;
    }

     public function getMarkets()
    {
        $return = $this->request("v1/exchangeInfo");
        return $return["symbols"];
    }



    public function getBalances() {

        $b = $this->privateRequest("v3/account");
        return $b['balances'];

    }

    private function request($url, $params = [], $method = "GET") {
        $opt = [
            "http" => [
                "method" => $method,
                "header" => "User-Agent: Mozilla/4.0 (compatible; PHP Binance API)\r\n"
            ]
        ];

        

         // build the POST data string
        $postdata = $params;


        // Set URL & Header
        curl_setopt($this->curl, CURLOPT_URL, $this->url . $url);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array());

        //Add post vars
        if($method == "POST") {
            curl_setopt($ch,CURLOPT_POST, count($params));
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postdata);
        }

        //Get result
        $result = curl_exec($this->curl);
        if($result===false)
            throw new \Exception('CURL error: ' . curl_error($this->curl));

         // decode results
        $result = json_decode($result, true);
        
        if(!is_array($result))
            throw new \Exception('JSON decode error');

        return $result;

    }

    private function privateRequest($url, $params = [], $method = "GET") {
        $opt = [
            "http" => [
                "method" => $method,
                "header" => "User-Agent: Mozilla/4.0 (compatible; PHP Binance API)\r\n"
            ]
        ];

        // build the POST data string
        $params['timestamp'] = number_format(microtime(true)*1000,0,'.','');
        $query = http_build_query($params, '', '&');

        // set API key and sign the message
        $sign = hash_hmac('sha256', $query, $this->secret);


        $headers = array(
            'X-MBX-APIKEY: ' . $this->key
        );

        // make request
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
   

         // build the POST data string
        $postdata = $params;


        // Set URL & Header
        curl_setopt($this->curl, CURLOPT_URL, $this->url . $url."?{$query}&signature={$sign}");

        //Add post vars
        if($method == "POST") {
            curl_setopt($ch,CURLOPT_POST, count($params));
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postdata);
        }

        //Get result
        $result = curl_exec($this->curl);
        if($result===false)
            throw new \Exception('CURL error: ' . curl_error($this->curl));

         // decode results
        $result = json_decode($result, true);
        if(!is_array($result))
            throw new \Exception('JSON decode error');

        return $result;

    }

}