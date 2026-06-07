# laravel-binance

Laravel implementation of the Binance Spot trading API.

[![Tests](https://github.com/adman9000/laravel-binance/actions/workflows/tests.yml/badge.svg)](https://github.com/adman9000/laravel-binance/actions)

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12

## Install

```bash
composer require adman9000/laravel-binance
```

Publish the config file:

```bash
php artisan vendor:publish --provider="adman9000\binance\BinanceServiceProvider"
```

Add your credentials to `.env`:

```
BINANCE_KEY=your-api-key
BINANCE_SECRET=your-api-secret
```

## Usage

### Via facade

```php
use Binance;

$balances = Binance::getBalances();
$price    = Binance::getTickers('BTCUSDT');
```

### Via dependency injection

```php
use adman9000\binance\BinanceAPI;

class TradingService
{
    public function __construct(private BinanceAPI $binance) {}
}
```

### Direct instantiation

```php
$binance = new BinanceAPI([
    'auth'     => ['key' => 'your-key', 'secret' => 'your-secret'],
    'urls'     => ['api' => 'https://api.binance.com/api/', 'sapi' => 'https://api.binance.com/sapi/'],
    'settings' => ['timing' => 5000, 'timeout' => 30, 'connect_timeout' => 10],
]);
```

## Available Methods

### Public (no authentication required)

| Method | Description |
|---|---|
| `getServerTime()` | Server timestamp in milliseconds |
| `getTickers(string $symbol = '')` | All symbol prices, or a single price |
| `getMarkets()` | Exchange trading rules and symbol info |
| `getOrderBook(string $symbol, int $limit = 100)` | Bids and asks for a symbol |
| `getPublicTrades(string $symbol, int $limit = 500)` | Recent trades for a symbol |
| `getAggTrades(string $symbol, int $limit = 500)` | Compressed/aggregate trade list |
| `getCandlesticks(string $symbol, string $interval = '1h', int $limit = 500)` | Kline/candlestick data |
| `getAvgPrice(string $symbol)` | Current average price |
| `getTickerChange(string $symbol = '')` | 24hr rolling window price change |
| `getBookTicker(string $symbol = '')` | Best price/qty on the order book |

Valid `$interval` values: `1s`, `1m`, `3m`, `5m`, `15m`, `30m`, `1h`, `2h`, `4h`, `6h`, `8h`, `12h`, `1d`, `3d`, `1w`, `1M`

### Private (API key + secret required)

| Method | Description |
|---|---|
| `getBalances()` | All account balances |
| `getBalance(string $asset)` | Balance for a single asset (e.g. `'BTC'`), or `null` if not found |
| `getRecentTrades(string $symbol, int $limit = 500)` | Your trade history for a symbol |
| `getOpenOrders(string $symbol = '')` | Current open orders |
| `getAllOrders(string $symbol)` | All orders for a symbol |
| `marketBuy(string $symbol, string $quantity)` | Market buy order |
| `marketSell(string $symbol, string $quantity)` | Market sell order |
| `limitBuy(string $symbol, string $quantity, float $price)` | Limit buy order |
| `limitSell(string $symbol, string $quantity, float $price)` | Limit sell order |
| `trade(string $symbol, string $quantity, string $side, string $type, ?float $price)` | Raw order placement |
| `depositAddress(string $coin)` | Deposit address for an asset |

## Configuration

```php
// config/binance.php
return [
    'auth' => [
        'key'    => env('BINANCE_KEY', ''),
        'secret' => env('BINANCE_SECRET', ''),
    ],
    'urls' => [
        'api'  => env('BINANCE_API_URL', 'https://api.binance.com/api/'),
        'sapi' => env('BINANCE_SAPI_URL', 'https://api.binance.com/sapi/'),
    ],
    'settings' => [
        'timing'          => env('BINANCE_TIMING', 5000),         // recvWindow in ms
        'timeout'         => env('BINANCE_TIMEOUT', 30),          // request timeout in seconds
        'connect_timeout' => env('BINANCE_CONNECT_TIMEOUT', 10),  // connection timeout in seconds
    ],
];
```

### Testnet

To use the Binance testnet, add to your `.env`:

```
BINANCE_API_URL=https://testnet.binance.vision/api/
BINANCE_SAPI_URL=https://testnet.binance.vision/sapi/
```

### Binance US

```
BINANCE_API_URL=https://api.binance.us/api/
BINANCE_SAPI_URL=https://api.binance.us/sapi/
```

## Error handling

All API errors throw `adman9000\binance\Exceptions\BinanceApiException`:

```php
use adman9000\binance\Exceptions\BinanceApiException;

try {
    $order = Binance::limitBuy('BTCUSDT', '0.001', 1.0);
} catch (BinanceApiException $e) {
    // $e->getMessage() — Binance error message
    // $e->getResponse() — full response array including error code
}
```

## Upgrading from v1

- `getTicker($symbol)` is deprecated — use `getTickers($symbol)` instead
- `getCurrencies()` has been removed (it returned `false`)
- `depositAddress($symbol)` now takes a coin ticker as `$coin` (same value, renamed parameter)
- `wapi` config URL removed — replaced by `sapi` (`https://api.binance.com/sapi/`)
- `ext-curl` is no longer required
- Errors now throw `BinanceApiException` instead of `\Exception`

## Running tests

```bash
composer install
./vendor/bin/phpunit
```

## Licence

MIT
