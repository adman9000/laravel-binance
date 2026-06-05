<?php

namespace adman9000\binance\Exceptions;

class BinanceApiException extends \RuntimeException
{
    public function __construct(string $message, private readonly ?array $response = null)
    {
        parent::__construct($message);
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }
}
