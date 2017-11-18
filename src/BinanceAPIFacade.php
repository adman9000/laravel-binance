<?php namespace adman9000\binance;

use Illuminate\Support\Facades\Facade;

class BinanceAPIFacade extends Facade {

	protected static function getFacadeAccessor() {
		return 'binance';
	}
}