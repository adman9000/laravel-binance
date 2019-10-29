# laravel-binance
Laravel implementation of the Binance crypto exchange trading API

## Install

#### Install via Composer

```
composer require adman9000/laravel-binance
```

Utilises autoloading in Laravel 5.5+. For older versions add the following lines to your `config/app.php`

```php
'providers' => [
        ...
        adman9000\binance\BinanceServiceProvider::class,
        ...
    ],


 'aliases' => [
        ...
        'Kraken' => adman9000\binance\BinanceAPIFacade::class,
    ],
```

#### Publish config

```
php artisan vendor:publish --provider="adman9000\binance\BinanceServiceProvider"
```

## Features

Price tickers, balances, trades
