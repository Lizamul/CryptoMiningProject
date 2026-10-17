<?php

declare(strict_types=1);

namespace App\Services\CryptoMining\Drivers;

use App\Models\CryptoPair;
use App\Services\CryptoMining\Contracts\ExchangeInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class IndodaxDriver implements ExchangeInterface
{
    public const EXCHANGE_NAME = 'indodax';

    public function fetchOhlcv(CryptoPair $pair, string $timeframe = '1h', int $limit = 100): Collection
    {
        if ($limit < 1) {
            return collect();
        }

        // Resolusi Indodax: 1, 5, 15, 60, D
        $resolutionMap = [
            '1m'  => '1',
            '5m'  => '5',
            '15m' => '15',
            '1h'  => '60',
            '1d'  => '1D',
        ];

        $resolution = $resolutionMap[$timeframe] ?? '60';

        // Hitung estimasi rentang waktu mundur berdasarkan limit candle
        $secondsPerCandle = match ($timeframe) {
            '1m' => 60,
            '5m' => 300,
            '15m' => 900,
            '1h' => 3600,
            '1d' => 86400,
            default => 3600,
        };

        $to = now()->timestamp;
        $from = $to - ($secondsPerCandle * ($limit + 10)); // Buffer ekstra 10 candle

        // Format simbol di Indodax kline: 'BTCIDR', 'ETHIDR'
        $symbol = strtoupper(str_replace(['/', '_'], '', $pair->exchange_symbol));

        try {
            $response = Http::baseUrl('https://indodax.com')
                ->timeout(15)
                ->retry([200, 500, 1000], throw: false)
                ->get('/tradingview/history_v2', [
                    'symbol' => $symbol,
                    'tf'     => $resolution,
                    'from'  => $from,
                    'to'    => $to,
                ]);

            if ($response->failed()) {
                throw new RuntimeException(sprintf('Indodax HTTP %d: %s', $response->status(), $response->body()));
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                return collect();
            }

            return collect($payload)
                ->filter(fn (mixed $candle): bool => is_array($candle)
                    && isset($candle['Time'], $candle['Open'], $candle['High'], $candle['Low'], $candle['Close'])
                    && is_numeric($candle['Time'])
                    && is_numeric($candle['Open'])
                    && is_numeric($candle['High'])
                    && is_numeric($candle['Low'])
                    && is_numeric($candle['Close']))
                ->map(fn (array $candle): array => [
                    'candle_time' => CarbonImmutable::createFromTimestampUTC((int) $candle['Time']),
                    'open'        => (string) $candle['Open'],
                    'high'        => (string) $candle['High'],
                    'low'         => (string) $candle['Low'],
                    'close'       => (string) $candle['Close'],
                    'volume'      => isset($candle['Volume']) && is_numeric($candle['Volume'])
                        ? (string) $candle['Volume']
                        : '0',
                ])
                ->take(-$limit)
                ->values();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf('Gagal mengambil kline Indodax %s: %s', $pair->exchange_symbol, $exception->getMessage()),
                previous: $exception
            );
        }
    }

    public function getExchangeName(): string
    {
        return self::EXCHANGE_NAME;
    }
}
