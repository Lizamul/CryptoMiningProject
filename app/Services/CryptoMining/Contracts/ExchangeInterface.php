<?php

declare(strict_types=1);

namespace App\Services\CryptoMining\Contracts;

use App\Models\CryptoPair;
use Illuminate\Support\Collection;

interface ExchangeInterface
{
    public function fetchOhlcv(CryptoPair $pair, string $timeframe = '1h', int $limit = 100): Collection;

    public function getExchangeName(): string;
}
