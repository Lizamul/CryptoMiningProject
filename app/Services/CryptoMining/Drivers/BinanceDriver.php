<?php

declare(strict_types=1);

namespace App\Services\CryptoMining\Drivers;

use App\Models\CryptoPair;
use App\Services\CryptoMining\Contracts\ExchangeInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class BinanceDriver implements ExchangeInterface
{
    public const EXCHANGE_NAME = 'binance';

    public function fetchOhlcv(CryptoPair $pair, string $timeframe = '1h', int $limit = 100): Collection
    {
        if ($limit < 1) {
            return collect();
        }

        $interval = [
            '1m' => '1m',
            '5m' => '5m',
            '15m' => '15m',
            '1h' => '1h',
            '1d' => '1d',
        ][$timeframe] ?? throw new InvalidArgumentException(sprintf('Timeframe [%s] tidak didukung Binance.', $timeframe));

        $symbol = strtoupper(str_replace(['/', '_', '-'], '', $pair->exchange_symbol));

        try {
            $response = Http::baseUrl('https://api.binance.com')
                ->timeout(10)
                ->retry([200, 500, 1000], throw: false)
                ->get('/api/v3/klines', [
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'limit' => min($limit, 1000),
                ]);

            if ($response->failed()) {
                throw new RuntimeException(sprintf('Binance HTTP %d: %s', $response->status(), $response->body()));
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                throw new RuntimeException('Binance mengembalikan payload JSON yang tidak valid.');
            }

            return collect($payload)
                ->filter(fn (mixed $candle): bool => is_array($candle)
                    && count($candle) >= 6
                    && is_numeric($candle[0])
                    && is_numeric($candle[1])
                    && is_numeric($candle[2])
                    && is_numeric($candle[3])
                    && is_numeric($candle[4])
                    && is_numeric($candle[5]))
                ->map(fn (array $candle): array => [
                    'candle_time' => CarbonImmutable::createFromTimestampUTC((int) $candle[0] / 1000),
                    'open' => (string) $candle[1],
                    'high' => (string) $candle[2],
                    'low' => (string) $candle[3],
                    'close' => (string) $candle[4],
                    'volume' => (string) $candle[5],
                ])
                ->take(-$limit)
                ->values();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf('Gagal mengambil kline Binance %s: %s', $pair->exchange_symbol, $exception->getMessage()),
                previous: $exception
            );
        }
    }

    public function getExchangeName(): string
    {
        return self::EXCHANGE_NAME;
    }
}
