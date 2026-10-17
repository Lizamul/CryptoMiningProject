<?php

declare(strict_types=1);

namespace App\Services\CryptoMining;

use App\Services\CryptoMining\Contracts\ExchangeInterface;
use App\Services\CryptoMining\Drivers\BinanceDriver;
use App\Services\CryptoMining\Drivers\CoinGeckoDriver;
use App\Services\CryptoMining\Drivers\IndodaxDriver;
use InvalidArgumentException;

class ExchangeManager
{
    /**
     * @var array<string, class-string<ExchangeInterface>>
     */
    private const DRIVERS = [
        'indodax' => IndodaxDriver::class,
        'binance' => BinanceDriver::class,
        'coingecko' => CoinGeckoDriver::class,
    ];

    public function driver(string $exchange): ExchangeInterface
    {
        $driverClass = self::DRIVERS[strtolower($exchange)] ?? null;

        if ($driverClass === null) {
            throw new InvalidArgumentException(sprintf('Exchange [%s] tidak dikenali.', $exchange));
        }

        return app($driverClass);
    }
}
