<?php

namespace adman9000\binance;

use adman9000\binance\Exceptions\BinanceApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BinanceAPI
{
    protected string $key;
    protected string $secret;
    protected string $apiUrl;
    protected string $sapiUrl;
    protected int $recvWindow;
    protected int $timeout;
    protected int $connectTimeout;

    public function __construct(array $config = [])
    {
        if (empty($config)) {
            $config = config('binance', []);
        }

        $this->key            = $config['auth']['key'] ?? '';
        $this->secret         = $config['auth']['secret'] ?? '';
        $this->apiUrl         = $config['urls']['api'] ?? 'https://api.binance.com/api/';
        $this->sapiUrl        = $config['urls']['sapi'] ?? 'https://api.binance.com/sapi/';
        $this->recvWindow     = $config['settings']['timing'] ?? 5000;
        $this->timeout        = $config['settings']['timeout'] ?? 30;
        $this->connectTimeout = $config['settings']['connect_timeout'] ?? 10;
    }

    public function setAPI(string $key, string $secret): void
    {
        $this->key    = $key;
        $this->secret = $secret;
    }

    // -------- PUBLIC ENDPOINTS --------

    public function getServerTime(): int
    {
        return $this->request('v3/time')['serverTime'];
    }

    /** Returns all symbol prices, or a single price when $symbol is given. */
    public function getTickers(string $symbol = ''): array
    {
        $params = $symbol ? ['symbol' => $symbol] : [];
        return $this->request('v3/ticker/price', $params);
    }

    /** @deprecated Use getTickers($symbol) */
    public function getTicker(string $symbol): array
    {
        return $this->getTickers($symbol);
    }

    /** Exchange trading rules and symbol information. */
    public function getMarkets(): array
    {
        return $this->request('v3/exchangeInfo')['symbols'];
    }

    public function getOrderBook(string $symbol, int $limit = 100): array
    {
        return $this->request('v3/depth', ['symbol' => $symbol, 'limit' => $limit]);
    }

    public function getPublicTrades(string $symbol, int $limit = 500): array
    {
        return $this->request('v3/trades', ['symbol' => $symbol, 'limit' => $limit]);
    }

    public function getAggTrades(string $symbol, int $limit = 500): array
    {
        return $this->request('v3/aggTrades', ['symbol' => $symbol, 'limit' => $limit]);
    }

    /**
     * @param string $interval  1s, 1m, 3m, 5m, 15m, 30m, 1h, 2h, 4h, 6h, 8h, 12h, 1d, 3d, 1w, 1M
     */
    public function getCandlesticks(string $symbol, string $interval = '1h', int $limit = 500): array
    {
        return $this->request('v3/klines', [
            'symbol'   => $symbol,
            'interval' => $interval,
            'limit'    => $limit,
        ]);
    }

    public function getAvgPrice(string $symbol): array
    {
        return $this->request('v3/avgPrice', ['symbol' => $symbol]);
    }

    /** 24hr rolling window ticker. Omit $symbol for all symbols. */
    public function getTickerChange(string $symbol = ''): array
    {
        $params = $symbol ? ['symbol' => $symbol] : [];
        return $this->request('v3/ticker/24hr', $params);
    }

    /** Best price/qty on the order book. Omit $symbol for all symbols. */
    public function getBookTicker(string $symbol = ''): array
    {
        $params = $symbol ? ['symbol' => $symbol] : [];
        return $this->request('v3/ticker/bookTicker', $params);
    }

    // -------- PRIVATE ENDPOINTS --------

    public function getBalances(): array
    {
        return $this->privateRequest('v3/account')['balances'];
    }

    public function getRecentTrades(string $symbol = 'BNBBTC', int $limit = 500): array
    {
        return $this->privateRequest('v3/myTrades', ['symbol' => $symbol, 'limit' => $limit]);
    }

    public function getOpenOrders(string $symbol = ''): array
    {
        $params = $symbol ? ['symbol' => $symbol] : [];
        return $this->privateRequest('v3/openOrders', $params);
    }

    public function getAllOrders(string $symbol): array
    {
        return $this->privateRequest('v3/allOrders', ['symbol' => $symbol]);
    }

    /**
     * @param string      $type   MARKET, LIMIT, STOP_LOSS, STOP_LOSS_LIMIT, TAKE_PROFIT, TAKE_PROFIT_LIMIT, LIMIT_MAKER
     * @param float|null  $price  Required for LIMIT order types
     */
    public function trade(string $symbol, string $quantity, string $side, string $type = 'MARKET', ?float $price = null): array
    {
        $data = [
            'symbol'   => $symbol,
            'side'     => $side,
            'type'     => $type,
            'quantity' => $quantity,
        ];

        if ($price !== null) {
            $data['price'] = $price;
        }

        if (in_array($type, ['LIMIT', 'STOP_LOSS_LIMIT', 'TAKE_PROFIT_LIMIT', 'LIMIT_MAKER'])) {
            $data['timeInForce'] = 'GTC';
        }

        return $this->privateRequest('v3/order', $data, 'POST');
    }

    public function marketBuy(string $symbol, string $quantity): array
    {
        return $this->trade($symbol, $quantity, 'BUY', 'MARKET');
    }

    public function marketSell(string $symbol, string $quantity): array
    {
        return $this->trade($symbol, $quantity, 'SELL', 'MARKET');
    }

    public function limitBuy(string $symbol, string $quantity, float $price): array
    {
        return $this->trade($symbol, $quantity, 'BUY', 'LIMIT', $price);
    }

    public function limitSell(string $symbol, string $quantity, float $price): array
    {
        return $this->trade($symbol, $quantity, 'SELL', 'LIMIT', $price);
    }

    public function depositAddress(string $coin): array
    {
        return $this->privateRequest('v1/capital/deposit/address', ['coin' => $coin], 'GET', true);
    }

    // -------- REQUEST LAYER --------

    private function request(string $endpoint, array $params = [], string $method = 'GET'): array
    {
        $url = $this->apiUrl . $endpoint;

        $http = Http::timeout($this->timeout)->connectTimeout($this->connectTimeout);

        $response = match ($method) {
            'POST'   => $http->post($url, $params),
            default  => $http->get($url, $params),
        };

        return $this->parseResponse($response);
    }

    private function privateRequest(string $endpoint, array $params = [], string $method = 'GET', bool $sapi = false): array
    {
        $params['timestamp']  = (int) (microtime(true) * 1000);
        $params['recvWindow'] = $this->recvWindow;

        $query               = http_build_query($params, '', '&');
        $params['signature'] = hash_hmac('sha256', $query, $this->secret);

        $url  = ($sapi ? $this->sapiUrl : $this->apiUrl) . $endpoint;
        $http = Http::timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->withHeaders(['X-MBX-APIKEY' => $this->key]);

        $response = match ($method) {
            'POST'   => $http->post($url, $params),
            'DELETE' => $http->delete($url, $params),
            default  => $http->get($url, $params),
        };

        return $this->parseResponse($response);
    }

    private function parseResponse(Response $response): array
    {
        $data = $response->json();

        if (!is_array($data)) {
            throw new BinanceApiException('Invalid response from Binance API');
        }

        if (isset($data['code']) && $data['code'] < 0) {
            throw new BinanceApiException($data['msg'] ?? 'Binance API error', $data);
        }

        return $data;
    }
}
