<?php

namespace adman9000\binance;

use Illuminate\Support\ServiceProvider;

class BinanceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/binance.php' => config_path('binance.php'),
        ]);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/binance.php', 'binance');

        $this->app->singleton('binance', function () {
            return new BinanceAPI(config('binance'));
        });
    }
}
