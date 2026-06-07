<?php

namespace adman9000\binance\Tests;

use adman9000\binance\BinanceAPI;
use adman9000\binance\BinanceServiceProvider;
use adman9000\binance\Exceptions\BinanceApiException;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class BinanceAPITest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [BinanceServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('binance.auth.key', 'test-key');
        $app['config']->set('binance.auth.secret', 'test-secret');
    }

    private function binance(): BinanceAPI
    {
        return new BinanceAPI([
            'auth'     => ['key' => 'test-key', 'secret' => 'test-secret'],
            'urls'     => ['api' => 'https://api.binance.com/api/', 'sapi' => 'https://api.binance.com/sapi/'],
            'settings' => ['timing' => 5000, 'timeout' => 30, 'connect_timeout' => 10],
        ]);
    }

    // -------- PUBLIC ENDPOINTS --------

    public function test_get_server_time(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/time' => Http::response(['serverTime' => 1672531200000]),
        ]);

        $result = $this->binance()->getServerTime();

        $this->assertEquals(1672531200000, $result);
    }

    public function test_get_tickers_all(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/ticker/price*' => Http::response([
                ['symbol' => 'BTCUSDT', 'price' => '50000.00'],
                ['symbol' => 'ETHUSDT', 'price' => '3000.00'],
            ]),
        ]);

        $result = $this->binance()->getTickers();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('BTCUSDT', $result[0]['symbol']);
    }

    public function test_get_tickers_single(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/ticker/price*' => Http::response([
                'symbol' => 'BTCUSDT',
                'price'  => '50000.00',
            ]),
        ]);

        $result = $this->binance()->getTickers('BTCUSDT');

        $this->assertEquals('BTCUSDT', $result['symbol']);
        $this->assertEquals('50000.00', $result['price']);
    }

    public function test_get_ticker_delegates_to_get_tickers(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/ticker/price*' => Http::response([
                'symbol' => 'ETHUSDT',
                'price'  => '3000.00',
            ]),
        ]);

        $result = $this->binance()->getTicker('ETHUSDT');

        $this->assertEquals('ETHUSDT', $result['symbol']);
    }

    public function test_get_markets(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/exchangeInfo*' => Http::response([
                'symbols' => [
                    ['symbol' => 'BTCUSDT', 'status' => 'TRADING'],
                ],
            ]),
        ]);

        $result = $this->binance()->getMarkets();

        $this->assertIsArray($result);
        $this->assertEquals('BTCUSDT', $result[0]['symbol']);
    }

    public function test_get_order_book(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/depth*' => Http::response([
                'lastUpdateId' => 1234,
                'bids'         => [['50000.00', '1.000']],
                'asks'         => [['50001.00', '0.500']],
            ]),
        ]);

        $result = $this->binance()->getOrderBook('BTCUSDT');

        $this->assertArrayHasKey('bids', $result);
        $this->assertArrayHasKey('asks', $result);
    }

    public function test_get_public_trades(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/trades*' => Http::response([
                ['id' => 1, 'price' => '50000.00', 'qty' => '0.001'],
            ]),
        ]);

        $result = $this->binance()->getPublicTrades('BTCUSDT');

        $this->assertIsArray($result);
        $this->assertEquals('50000.00', $result[0]['price']);
    }

    public function test_get_agg_trades(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/aggTrades*' => Http::response([
                ['a' => 1, 'p' => '50000.00', 'q' => '0.001'],
            ]),
        ]);

        $result = $this->binance()->getAggTrades('BTCUSDT');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('p', $result[0]);
    }

    public function test_get_candlesticks(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/klines*' => Http::response([
                [1672531200000, '49000.00', '51000.00', '48000.00', '50000.00', '100.0'],
            ]),
        ]);

        $result = $this->binance()->getCandlesticks('BTCUSDT', '1h', 1);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function test_get_avg_price(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/avgPrice*' => Http::response([
                'mins'  => 5,
                'price' => '50100.00',
            ]),
        ]);

        $result = $this->binance()->getAvgPrice('BTCUSDT');

        $this->assertEquals('50100.00', $result['price']);
    }

    public function test_get_ticker_change(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/ticker/24hr*' => Http::response([
                'symbol'      => 'BTCUSDT',
                'priceChange' => '1000.00',
            ]),
        ]);

        $result = $this->binance()->getTickerChange('BTCUSDT');

        $this->assertEquals('BTCUSDT', $result['symbol']);
    }

    public function test_get_book_ticker(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/ticker/bookTicker*' => Http::response([
                'symbol'   => 'BTCUSDT',
                'bidPrice' => '49999.00',
                'askPrice' => '50001.00',
            ]),
        ]);

        $result = $this->binance()->getBookTicker('BTCUSDT');

        $this->assertArrayHasKey('bidPrice', $result);
        $this->assertArrayHasKey('askPrice', $result);
    }

    // -------- PRIVATE ENDPOINTS --------

    public function test_get_balances(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/account*' => Http::response([
                'balances' => [
                    ['asset' => 'BTC', 'free' => '0.5', 'locked' => '0.0'],
                    ['asset' => 'USDT', 'free' => '1000.0', 'locked' => '0.0'],
                ],
            ]),
        ]);

        $result = $this->binance()->getBalances();

        $this->assertCount(2, $result);
        $this->assertEquals('BTC', $result[0]['asset']);
    }

    public function test_get_balance_returns_matching_asset(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/account*' => Http::response([
                'balances' => [
                    ['asset' => 'BTC', 'free' => '0.5', 'locked' => '0.0'],
                    ['asset' => 'USDT', 'free' => '1000.0', 'locked' => '0.0'],
                ],
            ]),
        ]);

        $result = $this->binance()->getBalance('BTC');

        $this->assertEquals('BTC', $result['asset']);
        $this->assertEquals('0.5', $result['free']);
    }

    public function test_get_balance_is_case_insensitive(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/account*' => Http::response([
                'balances' => [
                    ['asset' => 'BTC', 'free' => '0.5', 'locked' => '0.0'],
                ],
            ]),
        ]);

        $this->assertEquals('BTC', $this->binance()->getBalance('btc')['asset']);
    }

    public function test_get_balance_returns_null_for_unknown_asset(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/account*' => Http::response([
                'balances' => [
                    ['asset' => 'BTC', 'free' => '0.5', 'locked' => '0.0'],
                ],
            ]),
        ]);

        $this->assertNull($this->binance()->getBalance('ETH'));
    }

    public function test_get_recent_trades(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/myTrades*' => Http::response([
                ['id' => 100, 'symbol' => 'BTCUSDT', 'price' => '50000.00'],
            ]),
        ]);

        $result = $this->binance()->getRecentTrades('BTCUSDT', 10);

        $this->assertIsArray($result);
        $this->assertEquals('BTCUSDT', $result[0]['symbol']);
    }

    public function test_get_open_orders(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/openOrders*' => Http::response([
                ['orderId' => 1, 'symbol' => 'BTCUSDT', 'status' => 'NEW'],
            ]),
        ]);

        $result = $this->binance()->getOpenOrders('BTCUSDT');

        $this->assertIsArray($result);
        $this->assertEquals('NEW', $result[0]['status']);
    }

    public function test_get_all_orders(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/allOrders*' => Http::response([
                ['orderId' => 1, 'symbol' => 'BTCUSDT', 'status' => 'FILLED'],
            ]),
        ]);

        $result = $this->binance()->getAllOrders('BTCUSDT');

        $this->assertIsArray($result);
        $this->assertEquals('FILLED', $result[0]['status']);
    }

    public function test_market_buy(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/order*' => Http::response([
                'orderId' => 123,
                'symbol'  => 'BTCUSDT',
                'side'    => 'BUY',
                'type'    => 'MARKET',
                'status'  => 'FILLED',
            ]),
        ]);

        $result = $this->binance()->marketBuy('BTCUSDT', '0.001');

        $this->assertEquals('BUY', $result['side']);
        $this->assertEquals('MARKET', $result['type']);
    }

    public function test_market_sell(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/order*' => Http::response([
                'orderId' => 124,
                'symbol'  => 'BTCUSDT',
                'side'    => 'SELL',
                'type'    => 'MARKET',
                'status'  => 'FILLED',
            ]),
        ]);

        $result = $this->binance()->marketSell('BTCUSDT', '0.001');

        $this->assertEquals('SELL', $result['side']);
    }

    public function test_limit_buy(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/order*' => Http::response([
                'orderId' => 125,
                'symbol'  => 'BTCUSDT',
                'side'    => 'BUY',
                'type'    => 'LIMIT',
                'price'   => '45000.00',
                'status'  => 'NEW',
            ]),
        ]);

        $result = $this->binance()->limitBuy('BTCUSDT', '0.001', 45000.0);

        $this->assertEquals('BUY', $result['side']);
        $this->assertEquals('LIMIT', $result['type']);
    }

    public function test_limit_sell(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/order*' => Http::response([
                'orderId' => 126,
                'symbol'  => 'BTCUSDT',
                'side'    => 'SELL',
                'type'    => 'LIMIT',
                'price'   => '55000.00',
                'status'  => 'NEW',
            ]),
        ]);

        $result = $this->binance()->limitSell('BTCUSDT', '0.001', 55000.0);

        $this->assertEquals('SELL', $result['side']);
        $this->assertEquals('LIMIT', $result['type']);
    }

    public function test_deposit_address(): void
    {
        Http::fake([
            'https://api.binance.com/sapi/v1/capital/deposit/address*' => Http::response([
                'address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7Divfna',
                'coin'    => 'BTC',
            ]),
        ]);

        $result = $this->binance()->depositAddress('BTC');

        $this->assertEquals('BTC', $result['coin']);
        $this->assertArrayHasKey('address', $result);
    }

    // -------- CLOCK DRIFT --------

    public function test_timestamp_drift_auto_retries_and_succeeds(): void
    {
        $serverTime = (int) (microtime(true) * 1000) - 2000;

        Http::fake([
            'https://api.binance.com/api/v3/time'     => Http::response(['serverTime' => $serverTime]),
            'https://api.binance.com/api/v3/account*' => Http::sequence()
                ->push(['code' => -1021, 'msg' => 'Timestamp for this request was 1000ms ahead of the server\'s time.'])
                ->push(['balances' => [['asset' => 'BTC', 'free' => '1.0', 'locked' => '0.0']]]),
        ]);

        $result = $this->binance()->getBalances();

        $this->assertEquals('BTC', $result[0]['asset']);
    }

    // -------- ERROR HANDLING --------

    public function test_api_error_response_throws_exception(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/ticker/price*' => Http::response([
                'code' => -1121,
                'msg'  => 'Invalid symbol.',
            ]),
        ]);

        $this->expectException(BinanceApiException::class);
        $this->expectExceptionMessage('Invalid symbol.');

        $this->binance()->getTickers('INVALID');
    }

    public function test_exception_carries_response_payload(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/ticker/price*' => Http::response([
                'code' => -1121,
                'msg'  => 'Invalid symbol.',
            ]),
        ]);

        try {
            $this->binance()->getTickers('INVALID');
            $this->fail('Expected BinanceApiException');
        } catch (BinanceApiException $e) {
            $this->assertEquals(-1121, $e->getResponse()['code']);
        }
    }

    public function test_non_array_response_throws_exception(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/time*' => Http::response('not json', 200),
        ]);

        $this->expectException(BinanceApiException::class);

        $this->binance()->getServerTime();
    }

    public function test_set_api_credentials(): void
    {
        $binance = $this->binance();
        $binance->setAPI('new-key', 'new-secret');

        Http::fake([
            'https://api.binance.com/api/v3/account*' => Http::response([
                'balances' => [],
            ]),
        ]);

        $result = $binance->getBalances();
        $this->assertIsArray($result);
    }

    public function test_private_request_includes_signature(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/account*' => Http::response([
                'balances' => [],
            ]),
        ]);

        $this->binance()->getBalances();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'signature=')
                && str_contains($request->url(), 'timestamp=');
        });
    }

    public function test_private_request_sends_api_key_header(): void
    {
        Http::fake([
            'https://api.binance.com/api/v3/account*' => Http::response([
                'balances' => [],
            ]),
        ]);

        $this->binance()->getBalances();

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-MBX-APIKEY', 'test-key');
        });
    }
}
