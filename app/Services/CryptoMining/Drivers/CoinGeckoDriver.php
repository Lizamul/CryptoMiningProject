<?php

declare(strict_types=1);

namespace App\Services\CryptoMining\Drivers;

use App\Models\CryptoPair;
use App\Services\CryptoMining\Contracts\ExchangeInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class CoinGeckoDriver implements ExchangeInterface
{
    public const EXCHANGE_NAME = 'coingecko';

    public function fetchOhlcv(CryptoPair $pair, string $timeframe = '1h', int $limit = 100): Collection
    {
        if ($limit < 1) {
            return collect();
        }

        if (! in_array($timeframe, ['1m', '5m', '15m', '1h', '1d'], true)) {
            throw new InvalidArgumentException(sprintf('Timeframe [%s] tidak didukung CoinGecko.', $timeframe));
        }

        $days = match (true) {
            $limit <= 24 => 1,
            $limit <= 168 => 7,
            $limit <= 336 => 14,
            default => 30,
        };

        try {
            $response = Http::baseUrl('https://api.coingecko.com/api/v3')
                ->timeout(10)
                ->retry([200, 500, 1000], throw: false)
                ->get('/coins/'.rawurlencode($pair->exchange_symbol).'/ohlc', [
                    'vs_currency' => 'usd',
                    'days' => $days,
                ]);

            if ($response->status() === 429) {
                Log::warning('CoinGecko rate limit tercapai.', [
                    'coin' => $pair->exchange_symbol,
                    'days' => $days,
                ]);

                return collect();
            }

            if ($response->failed()) {
                throw new RuntimeException(sprintf('CoinGecko HTTP %d: %s', $response->status(), $response->body()));
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                throw new RuntimeException('CoinGecko mengembalikan payload JSON yang tidak valid.');
            }

            return collect($payload)
                ->filter(fn (mixed $candle): bool => is_array($candle)
                    && count($candle) >= 5
                    && is_numeric($candle[0])
                    && is_numeric($candle[1])
                    && is_numeric($candle[2])
                    && is_numeric($candle[3])
                    && is_numeric($candle[4]))
                ->map(fn (array $candle): array => [
                    'candle_time' => CarbonImmutable::createFromTimestampUTC((int) $candle[0] / 1000),
                    'open' => (string) $candle[1],
                    'high' => (string) $candle[2],
                    'low' => (string) $candle[3],
                    'close' => (string) $candle[4],
                    'volume' => '0',
                ])
                ->take(-$limit)
                ->values();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf('Gagal mengambil OHLC CoinGecko %s: %s', $pair->exchange_symbol, $exception->getMessage()),
                previous: $exception
            );
        }
    }

    public function getExchangeName(): string
    {
        return self::EXCHANGE_NAME;
    }
}
