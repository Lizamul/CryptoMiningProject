<?php

declare(strict_types=1);

namespace App\Services\CryptoMining;

use App\Models\CryptoPair;
use App\Models\OhlcvCandle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class HistoricalBackfillService
{
    private const BATCH_SIZE = 500;

    /**
     * @param callable(int): void $onBatchPersisted
     */
    public function backfill(CryptoPair $pair, int $days, string $timeframe, callable $onBatchPersisted): int
    {
        if ($days < 1) {
            throw new InvalidArgumentException('Jumlah hari backfill harus lebih besar dari nol.');
        }

        return match ($pair->exchange) {
            'binance' => $this->backfillBinance($pair, $days, $timeframe, $onBatchPersisted),
            'indodax' => $this->backfillIndodax($pair, $days, $timeframe, $onBatchPersisted),
            'coingecko' => $this->backfillCoinGecko($pair, $days, $timeframe, $onBatchPersisted),
            default => throw new InvalidArgumentException(sprintf('Exchange [%s] belum mendukung backfill.', $pair->exchange)),
        };
    }

    /**
     * @param callable(int): void $onBatchPersisted
     */
    private function backfillBinance(CryptoPair $pair, int $days, string $timeframe, callable $onBatchPersisted): int
    {
        $interval = $this->binanceInterval($timeframe);
        $symbol = strtoupper(str_replace(['/', '_', '-'], '', $pair->exchange_symbol));
        $endTime = now()->timestamp * 1000;
        $currentStart = now()->subDays($days)->timestamp * 1000;
        $total = 0;

        try {
            while ($currentStart < $endTime) {
                $response = Http::baseUrl('https://api.binance.com')
                    ->timeout(10)
                    ->retry([200, 500, 1000], throw: false)
                    ->get('/api/v3/klines', [
                        'symbol' => $symbol,
                        'interval' => $interval,
                        'startTime' => $currentStart,
                        'endTime' => $endTime,
                        'limit' => 1000,
                    ]);

                if ($response->failed()) {
                    throw new RuntimeException(sprintf('Binance HTTP %d: %s', $response->status(), $response->body()));
                }

                $payload = $response->json();
                if (! is_array($payload) || $payload === []) {
                    break;
                }

                $rows = [];
                foreach ($payload as $candle) {
                    if (! is_array($candle) || count($candle) < 7 || ! is_numeric($candle[0])) {
                        continue;
                    }

                    $openTime = (int) $candle[0];
                    if ($openTime < $currentStart || $openTime >= $endTime) {
                        continue;
                    }

                    $rows[] = $this->row(
                        $pair,
                        $timeframe,
                        CarbonImmutable::createFromTimestampUTC($openTime / 1000),
                        (string) $candle[1],
                        (string) $candle[2],
                        (string) $candle[3],
                        (string) $candle[4],
                        (string) $candle[5]
                    );
                }

                $persisted = $this->persistRows($rows);
                if ($persisted > 0) {
                    $total += $persisted;
                    $onBatchPersisted($persisted);
                }

                $lastCandle = end($payload);
                $lastCloseTime = is_array($lastCandle) && isset($lastCandle[6]) && is_numeric($lastCandle[6])
                    ? (int) $lastCandle[6]
                    : $currentStart;
                $nextStart = $lastCloseTime + 1;

                if ($nextStart <= $currentStart) {
                    break;
                }

                $currentStart = $nextStart;
                usleep(250000);
            }

            return $total;
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf('Gagal backfill Binance %s: %s', $pair->exchange_symbol, $exception->getMessage()),
                previous: $exception
            );
        }
    }

    /**
     * @param callable(int): void $onBatchPersisted
     */
    private function backfillIndodax(CryptoPair $pair, int $days, string $timeframe, callable $onBatchPersisted): int
    {
        $resolution = [
            '1m' => '1',
            '5m' => '5',
            '15m' => '15',
            '1h' => '60',
            '1d' => '1D',
        ][$timeframe] ?? throw new InvalidArgumentException(sprintf('Timeframe [%s] tidak didukung Indodax.', $timeframe));
        $symbol = strtoupper(str_replace(['/', '_'], '', $pair->exchange_symbol));
        $to = now()->timestamp;
        $from = now()->subDays($days)->timestamp;

        try {
            $response = Http::baseUrl('https://indodax.com')
                ->timeout(15)
                ->retry([200, 500, 1000], throw: false)
                ->get('/tradingview/history_v2', [
                    'symbol' => $symbol,
                    'tf' => $resolution,
                    'from' => $from,
                    'to' => $to,
                ]);

            if ($response->failed()) {
                throw new RuntimeException(sprintf('Indodax HTTP %d: %s', $response->status(), $response->body()));
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                throw new RuntimeException('Indodax mengembalikan payload JSON yang tidak valid.');
            }

            return $this->persistMappedPayload($pair, $timeframe, $payload, function (array $candle): ?array {
                if (! isset($candle['Time'], $candle['Open'], $candle['High'], $candle['Low'], $candle['Close'])
                    || ! is_numeric($candle['Time'])) {
                    return null;
                }

                return [
                    CarbonImmutable::createFromTimestampUTC((int) $candle['Time']),
                    (string) $candle['Open'],
                    (string) $candle['High'],
                    (string) $candle['Low'],
                    (string) $candle['Close'],
                    isset($candle['Volume']) && is_numeric($candle['Volume']) ? (string) $candle['Volume'] : '0',
                ];
            }, $onBatchPersisted);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf('Gagal backfill Indodax %s: %s', $pair->exchange_symbol, $exception->getMessage()),
                previous: $exception
            );
        }
    }

    /**
     * @param callable(int): void $onBatchPersisted
     */
    private function backfillCoinGecko(CryptoPair $pair, int $days, string $timeframe, callable $onBatchPersisted): int
    {
        if (! in_array($timeframe, ['1m', '5m', '15m', '1h', '1d'], true)) {
            throw new InvalidArgumentException(sprintf('Timeframe [%s] tidak didukung CoinGecko.', $timeframe));
        }

        $requestedDays = min($days, 30);

        try {
            $response = Http::baseUrl('https://api.coingecko.com/api/v3')
                ->timeout(10)
                ->retry([200, 500, 1000], throw: false)
                ->get('/coins/'.rawurlencode($pair->exchange_symbol).'/ohlc', [
                    'vs_currency' => 'usd',
                    'days' => $requestedDays,
                ]);

            if ($response->status() === 429) {
                Log::warning('CoinGecko rate limit tercapai saat backfill.', [
                    'coin' => $pair->exchange_symbol,
                    'days' => $requestedDays,
                ]);

                return 0;
            }

            if ($response->failed()) {
                throw new RuntimeException(sprintf('CoinGecko HTTP %d: %s', $response->status(), $response->body()));
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                throw new RuntimeException('CoinGecko mengembalikan payload JSON yang tidak valid.');
            }

            return $this->persistMappedPayload($pair, $timeframe, $payload, function (array $candle): ?array {
                if (count($candle) < 5 || ! is_numeric($candle[0])) {
                    return null;
                }

                return [
                    CarbonImmutable::createFromTimestampUTC((int) $candle[0] / 1000),
                    (string) $candle[1],
                    (string) $candle[2],
                    (string) $candle[3],
                    (string) $candle[4],
                    '0',
                ];
            }, $onBatchPersisted);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf('Gagal backfill CoinGecko %s: %s', $pair->exchange_symbol, $exception->getMessage()),
                previous: $exception
            );
        }
    }

    /**
     * @param array<int, mixed> $payload
     * @param callable(array<int, mixed>): ?array $mapper
     * @param callable(int): void $onBatchPersisted
     */
    private function persistMappedPayload(
        CryptoPair $pair,
        string $timeframe,
        array $payload,
        callable $mapper,
        callable $onBatchPersisted
    ): int {
        $rows = [];
        $total = 0;

        foreach ($payload as $candle) {
            if (! is_array($candle)) {
                continue;
            }

            $mapped = $mapper($candle);
            if ($mapped === null) {
                continue;
            }

            $rows[] = $this->row($pair, $timeframe, ...$mapped);
            if (count($rows) === self::BATCH_SIZE) {
                $persisted = $this->persistRows($rows);
                $total += $persisted;
                $onBatchPersisted($persisted);
                $rows = [];
            }
        }

        if ($rows !== []) {
            $persisted = $this->persistRows($rows);
            $total += $persisted;
            $onBatchPersisted($persisted);
        }

        return $total;
    }

    /**
     * @param array<int, array<string, int|string>> $rows
     */
    private function persistRows(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        foreach (array_chunk($rows, self::BATCH_SIZE) as $batch) {
            OhlcvCandle::query()->upsert(
                $batch,
                ['crypto_pair_id', 'timeframe', 'candle_time'],
                ['open', 'high', 'low', 'close', 'volume', 'updated_at']
            );
        }

        return count($rows);
    }

    /**
     * @return array<string, int|string>
     */
    private function row(
        CryptoPair $pair,
        string $timeframe,
        CarbonImmutable $candleTime,
        string $open,
        string $high,
        string $low,
        string $close,
        string $volume
    ): array {
        $now = now();

        return [
            'crypto_pair_id' => $pair->id,
            'timeframe' => $timeframe,
            'open' => $open,
            'high' => $high,
            'low' => $low,
            'close' => $close,
            'volume' => $volume,
            'candle_time' => $candleTime->format('Y-m-d H:i:s'),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function binanceInterval(string $timeframe): string
    {
        return [
            '1m' => '1m',
            '5m' => '5m',
            '15m' => '15m',
            '1h' => '1h',
            '1d' => '1d',
        ][$timeframe] ?? throw new InvalidArgumentException(sprintf('Timeframe [%s] tidak didukung Binance.', $timeframe));
    }
}
