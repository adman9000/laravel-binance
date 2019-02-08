<?php
namespace adman9000\binance;

class BinanceAPI
{
    protected $key;         // API key
    protected $secret;      // API secret
    protected $url;         // API base URL
    protected $recvWindow;  // API base URL
    protected $version;     // API version
    protected $curl;        // curl handle

    /**
     * Constructor for BinanceAPI
     */
    function __construct(array $auth = null, array $urls = null, array $settings = null)
    {
        if(!$auth)      $auth       = config("binance.auth");
        if(!$urls)      $urls       = config("binance.urls");
        if(!$settings)  $settings   = config("binance.settings");

        $this->key    = array_get($auth, 'key');
        $this->secret = array_get($auth, 'secret');

        $this->url        = array_get($urls, 'api');
        $this->wapi_url   = array_get($urls, 'wapi');

        $this->recvWindow = array_get($settings, 'timing');
        $this->curl       = curl_init();

        $curl_options     = [
            CURLOPT_SSL_VERIFYPEER => array_get($settings, 'ssl'),
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'Binance PHP API Agent',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 300
        ];

        curl_setopt_array($this->curl, $curl_options);

    }

    /**
     * Close CURL
     */
    function __destruct()
    {
        curl_close($this->curl);
    }

    /**
     * Key and Secret setter function. It's required for TRADE, USER_DATA, USER_STREAM, MARKET_DATA endpoints.
     * https://github.com/binance-exchange/binance-official-api-docs/blob/master/rest-api.md#endpoint-security-type
     *
     * @param string $key    API Key
     * @param string $secret API Secret
     */
    function setAPI($key, $secret)
    {
       $this->key    = $key;
       $this->secret = $secret;
    }


    //------ PUBLIC API CALLS --------
    /*
     * getServerTime
     * getExchangeInfo
     * getMarkets
     * getTickers
     * getOrderBook
     * getPublicTrades
     * getAggTrades
     * getCandlesticks
     * getAvgPrice
     * getTickerChange
     * getTickerPrice
     */


    /**
     * Test connectivity to the Rest API and get the current server time.
     *
     * @return timestamp
     * @throws \Exception
     */
    public function getServerTime()
    {
        $return = $this->request('v1/time');
        return $return['serverTime'];
    }


    /**
     * Current exchange trading rules and symbol information
     *
     * @return mixed
     * @throws \Exception
     */
    public function getExchangeInfo()
    {
        return $this->request('v1/exchangeInfo');
    }


    /**
     * Symbol information via exchangeInfo
     *
     * @return mixed
     * @throws \Exception
     */
    public function getMarkets()
    {
        $return = $this->request('v1/exchangeInfo');
        return $return['symbols'];
    }


    /**
     * Get ticker
     *
     * @return mixed
     * @throws \Exception
     */
    public function getTickers()
    {
        return $this->request('v1/ticker/allPrices');
    }


    /**
     * Order book
     *
     * @param string $symbol
     * @param int $limit default 100; max 1000; Valid limits:[5, 10, 20, 50, 100, 500, 1000]
     * @return mixed
     * @throws \Exception
     */
    public function getOrderBook($symbol = 'BNBBTC', $limit = 100)
    {
        $data = [
            'symbol' => $symbol,
            'limit'  => $limit,
        ];

        return $this->request('v1/depth', $data);
    }


    /**
     * Recent trades
     * Get recent trades (up to last 500).
     *
     * @param string $symbol
     * @param int $limit Default 500; max 1000.
     * @return mixed
     * @throws \Exception
     */
    public function getPublicTrades($symbol = 'BNBBTC', $limit = 500)
    {
        $data = [
            'symbol' => $symbol,
            'limit'  => $limit,
        ];

        return $this->request('v1/trades', $data);
    }


    /**
     * Compressed/Aggregate trades list
     * Get compressed, aggregate trades. Trades that fill at the time, from the same order, with the same price will have the quantity aggregated.
     *
     * "a": 26129,         // Aggregate tradeId
     * "p": "0.01633102",  // Price
     * "q": "4.70443515",  // Quantity
     * "f": 27781,         // First tradeId
     * "l": 27781,         // Last tradeId
     * "T": 1498793709153, // Timestamp
     * "m": true,          // Was the buyer the maker?
     * "M": true           // Was the trade the best price match?
     *
     * @param string $symbol
     * @param int $fromId
     * @param timestamp $startTime
     * @param timestamp $endTime
     * @param int $limit Default 500; max 1000.
     * @return mixed
     * @throws \Exception
     */
    public function getAggTrades($symbol = 'BNBBTC', $fromId = null, $startTime = null, $endTime = null, $limit = 500)
    {
        $data = [
            'symbol' => $symbol,
            'fromId' => $fromId,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'limit'  => $limit,
        ];

        return $this->request('v1/aggTrades', $data);
    }


    /**
     * Kline/Candlestick data
     * Kline/candlestick bars for a symbol. Klines are uniquely identified by their open time.
     *
     * candlesticks get the candles for the given intervals
     * 1m,3m,5m,15m,30m,1h,2h,4h,6h,8h,12h,1d,3d,1w,1M
     *
     * 1499040000000,      // Open time
     * "0.01634790",       // Open
     * "0.80000000",       // High
     * "0.01575800",       // Low
     * "0.01577100",       // Close
     * "148976.11427815",  // Volume
     * 1499644799999,      // Close time
     * "2434.19055334",    // Quote asset volume
     * 308,                // Number of trades
     * "1756.87402397",    // Taker buy base asset volume
     * "28.46694368",      // Taker buy quote asset volume
     * "17928899.62484339" // Ignore.
     *
     * @param string $symbol
     * @param string $interval
     * @param timestamp $startTime
     * @param timestamp $endTime
     * @param int $limit Default 500; max 1000.
     * @return mixed
     * @throws \Exception
     */
    public function getCandlesticks($symbol = 'BNBBTC', $interval = '1h', $startTime = null, $endTime = null, $limit = 500)
    {
        $data = [
            'symbol' => $symbol,
            'interval' => $interval,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'limit'  => $limit,
        ];

        return $this->request('v1/klines', $data);
    }


    /**
     * Current average price
     * Current average price for a symbol.
     *
     * @param string $symbol
     * @return mixed
     * @throws \Exception
     */
    public function getAvgPrice($symbol = 'BNBBTC')
    {
        $data = [
            'symbol' => $symbol,
        ];

        return $this->request('v3/avgPrice', $data);
    }


    /**
     * 24hr ticker price change statistics
     * 24 hour rolling window price change statistics. Careful when accessing this with no symbol.
     * Weight: 1 for a single symbol; 40 when the symbol parameter is omitted
     *
     * @param string $symbol
     * @return mixed
     * @throws \Exception
     */
    public function getTickerChange($symbol = null)
    {
        $data = [
            'symbol' => $symbol,
        ];

        return $this->request('v1/ticker/24hr', $data);
    }


    /**
     * Symbol price ticker
     * Latest price for a symbol or symbols.
     *
     * @param string $symbol
     * @return mixed
     * @throws \Exception
     */
    public function getTickerPrice($symbol = null)
    {
        $data = [
            'symbol' => $symbol,
        ];

        return $this->request('v3/ticker/price', $data);
    }


    //------ PRIVATE API CALLS ----------
    /*
    * getBalances
    * getRecentTrades
    * getOpenOrders
    * getAllOrders
    * trade
    * marketSell
    * marketBuy
    * limitSell
    * limitBuy
    * depositAddress
    */

    /**
     * Get current account information
     *
     * @return mixed
     * @throws \Exception
     */
    public function getBalances() {

        $b = $this->privateRequest('v3/account');
        return $b['balances'];

    }

    /**
     * Get trades for a specific account and symbol
     *
     * @param string $symbol Currency pair
     * @param int $limit     Limit of trades. Max. 500
     * @return mixed
     * @throws \Exception
     */
    public function getRecentTrades($symbol = 'BNBBTC', $limit = 500)
    {
        $data = [
            'symbol' => $symbol,
            'limit'  => $limit,
        ];

        $b = $this->privateRequest('v3/myTrades', $data);
        return $b;

    }

    public function getOpenOrders()
    {


        $b = $this->privateRequest('v3/openOrders');
        return $b;

    }

    public function getAllOrders($symbol)
    {

        $data = [
            'symbol' => $symbol
        ];
        $b = $this->privateRequest('v3/allOrders', $data);
        return $b;

    }

    /**
     * Base trade function
     *
     * @param string $symbol   Asset pair to trade
     * @param string $quantity Amount of trade asset
     * @param string $side     BUY, SELL
     * @param string $type     MARKET, LIMIT, STOP_LOSS, STOP_LOSS_LIMIT, TAKE_PROFIT, TAKE_PROFIT_LIMIT, LIMIT_MAKER
     * @param bool $price      Limit price
     * @return mixed
     * @throws \Exception
     */
    public function trade($symbol, $quantity, $side, $type = 'MARKET', $price = false)
    {
        $data = [
            'symbol'   => $symbol,
            'side'     => $side,
            'type'     => $type,
            'quantity' => $quantity
        ];
        if($price !== false)
        {
            $data['price'] = $price;
        }

        $b = $this->privateRequest('v3/order', $data, 'POST');

        return $b;
    }

    /**
     * Sell at market price
     *
     * @param string $symbol   Asset pair to trade
     * @param string $quantity Amount of trade asset
     * @return mixed
     * @throws \Exception
     */
    public function marketSell($symbol, $quantity)
    {
        return $this->trade($symbol, $quantity, 'SELL', 'MARKET');
    }

    /**
     * Buy at market price
     *
     * @param string $symbol   Asset pair to trade
     * @param string $quantity Amount of trade asset
     * @return mixed
     * @throws \Exception
     */
    public function marketBuy($symbol, $quantity)
    {
        return $this->trade($symbol, $quantity, 'BUY', 'MARKET');
    }

    /**
     * Sell limit
     *
     * @param string $symbol   Asset pair to trade
     * @param string $quantity Amount of trade asset
     * @param float $price     Limit price to sell
     * @return mixed
     * @throws \Exception
     */
    public function limitSell($symbol, $quantity, $price)
    {
        return $this->trade($symbol, $quantity, 'SELL', 'LIMIT', $price);
    }

    /**
     * Buy limit
     *
     * @param string $symbol   Asset pair to trade
     * @param string $quantity Amount of trade asset
     * @param float $price     Limit price to buy
     * @return mixed
     * @throws \Exception
     */
    public function limitBuy($symbol, $quantity, $price)
    {
        return $this->trade($symbol, $quantity, 'BUY', 'LIMIT', $price);
    }



    /**
     * Deposit Address
     * @param string $symbol   Asset symbol
     * @return mixed
     **/
    public function depositAddress($symbol) {

        return $this->wapiRequest("v3/depositAddress.html", ['asset' => $symbol]);

    }

    //------ REQUESTS FUNCTIONS ------

    /**
     * Make public requests (Security Type: NONE)
     *
     * @param string $url    URL Endpoint
     * @param array $params  Required and optional parameters
     * @param string $method GET, POST, PUT, DELETE
     * @return mixed
     * @throws \Exception
     */
    private function request($url, $params = [], $method = 'GET')
    {
        if($params)
        {
            $query   = http_build_query($params, '', '&');
            // Set URL & Header
            curl_setopt($this->curl, CURLOPT_URL, $this->url . $url . "?{$query}");

        } else {
            // Set URL & Header
            curl_setopt($this->curl, CURLOPT_URL, $this->url . $url);
        }

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array());

        //Add post vars
        if($method == 'POST')
        {
            curl_setopt($this->curl, CURLOPT_POST, count($params));
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        }

        //Get result
        $result = curl_exec($this->curl);
        if($result === false)
            throw new \Exception('CURL error: ' . curl_error($this->curl));

        // decode results
        $result = json_decode($result, true);

        if(!is_array($result) || json_last_error())
            throw new \Exception('JSON decode error');

        return $result;

    }

    /**
     * Make private requests (Security Type: TRADE, USER_DATA, USER_STREAM, MARKET_DATA)
     *
     * @param string $url    URL Endpoint
     * @param array $params  Required and optional parameters
     * @param string $method GET, POST, PUT, DELETE
     * @return mixed
     * @throws \Exception
     */
    private function privateRequest($url, $params = [], $method = 'GET')
    {
        // build the POST data string
        $params['timestamp']  = number_format((microtime(true) * 1000), 0, '.', '');
        $params['recvWindow'] = $this->recvWindow;

        $query   = http_build_query($params, '', '&');

        // set API key and sign the message
        $sign    = hash_hmac('sha256', $query, $this->secret);

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
            curl_setopt($this->curl,CURLOPT_POST, 1);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, array());
        }

        //Get result
        $result = curl_exec($this->curl);
        if($result === false)
            throw new \Exception('CURL error: ' . curl_error($this->curl));

         // decode results
        $result = json_decode($result, true);
        if(!is_array($result) || json_last_error())
            throw new \Exception('JSON decode error');

        return $result;

    }

    /**
     * Make wapi requests
     *
     * @param string $url    URL Endpoint
     * @param array $params  Required and optional parameters
     * @param string $method GET, POST, PUT, DELETE
     * @return mixed
     * @throws \Exception
     */
    private function wapiRequest($url, $params = [], $method = 'GET')
    {
        // build the POST data string
        $params['timestamp']  = number_format((microtime(true) * 1000), 0, '.', '');
        $params['recvWindow'] = $this->recvWindow;

        $query   = http_build_query($params, '', '&');

        // set API key and sign the message
        $sign    = hash_hmac('sha256', $query, $this->secret);

        $headers = array(
            'X-MBX-APIKEY: ' . $this->key
        );

        // make request
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

         // build the POST data string
        $postdata = $params;

        // Set URL & Header
        curl_setopt($this->curl, CURLOPT_URL, $this->wapi_url . $url."?{$query}&signature={$sign}");

        //Add post vars
        if($method == "POST") {
            curl_setopt($this->curl,CURLOPT_POST, 1);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, array());
        }

        //Get result
        $result = curl_exec($this->curl);
        if($result === false)
            throw new \Exception('CURL error: ' . curl_error($this->curl));

         // decode results
        $result = json_decode($result, true);
        if(!is_array($result) || json_last_error())
            throw new \Exception('JSON decode error');

        return $result;

    }

}
