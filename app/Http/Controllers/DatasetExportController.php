<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CryptoPair;
use App\Models\OhlcvCandle;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatasetExportController extends Controller
{
    public function exportCsv(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $pair = $filters['pair'];
        $timeframe = $filters['timeframe'];
        $filename = sprintf(
            'dataset_%s_%s.csv',
            $this->safeFilenamePart($pair->exchange_symbol),
            $timeframe
        );

        return response()->stream(function () use ($filters): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                throw new \RuntimeException('Tidak dapat membuka output stream.');
            }

            fputcsv($handle, ['timestamp', 'datetime_utc', 'open', 'high', 'low', 'close', 'volume']);
            $this->streamCandles($filters, function (array $row) use ($handle): void {
                fputcsv($handle, [
                    $row['timestamp'],
                    $row['datetime_utc'],
                    $row['open'],
                    $row['high'],
                    $row['low'],
                    $row['close'],
                    $row['volume'],
                ]);
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function exportJson(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $pair = $filters['pair'];
        $timeframe = $filters['timeframe'];
        $filename = sprintf(
            'dataset_%s_%s.json',
            $this->safeFilenamePart($pair->exchange_symbol),
            $timeframe
        );

        return response()->stream(function () use ($filters): void {
            $first = true;
            echo '[';

            $this->streamCandles($filters, function (array $row) use (&$first): void {
                if (! $first) {
                    echo ',';
                }

                echo json_encode($row, JSON_THROW_ON_ERROR);
                $first = false;
            });

            echo ']';
        }, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * @return array{pair: CryptoPair, timeframe: string, start_date: ?string, end_date: ?string}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'crypto_pair_id' => ['required', 'integer', 'exists:crypto_pairs,id'],
            'timeframe' => ['nullable', 'string', 'in:1m,5m,15m,1h,1d'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        return [
            'pair' => CryptoPair::query()->findOrFail((int) $validated['crypto_pair_id']),
            'timeframe' => (string) ($validated['timeframe'] ?? '1h'),
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ];
    }

    /**
     * @param array{pair: CryptoPair, timeframe: string, start_date: ?string, end_date: ?string} $filters
     * @param callable(array<string, int|float|string>): void $consume
     */
    private function streamCandles(array $filters, callable $consume): void
    {
        $query = OhlcvCandle::query()
            ->where('crypto_pair_id', $filters['pair']->id)
            ->where('timeframe', $filters['timeframe'])
            ->when($filters['start_date'], fn ($query, string $date) => $query->whereDate('candle_time', '>=', $date))
            ->when($filters['end_date'], fn ($query, string $date) => $query->whereDate('candle_time', '<=', $date))
            ->orderBy('candle_time');

        $query->chunk(500, function ($candles) use ($consume): void {
            foreach ($candles as $candle) {
                $datetime = CarbonImmutable::instance($candle->candle_time)->utc();
                $consume([
                    'timestamp' => (int) $datetime->timestamp,
                    'datetime_utc' => $datetime->format('Y-m-d\TH:i:s\Z'),
                    'open' => (string) $candle->open,
                    'high' => (string) $candle->high,
                    'low' => (string) $candle->low,
                    'close' => (string) $candle->close,
                    'volume' => (string) $candle->volume,
                ]);
            }
        });
    }

    private function safeFilenamePart(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '_', $value) ?: 'pair';
    }
}
