<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CryptoPair;
use App\Models\MiningLog;
use App\Models\OhlcvCandle;
use App\Services\CryptoMining\ExchangeManager;
use Illuminate\Console\Command;
use Throwable;

class MineCryptoData extends Command
{
    protected $signature = 'crypto:mine
                            {--exchange=indodax : Pilihan exchange (indodax, binance, coingecko, all)}
                            {--timeframe=1h : Candle timeframe (1m, 5m, 1h, 1d)}
                            {--limit=100 : Jumlah candle per pair}';

    protected $description = 'Menambang data pasar historis OHLCV dan menyimpannya ke database.';

    public function __construct(private readonly ExchangeManager $exchangeManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $targetExchange = (string) $this->option('exchange');
        $timeframe = (string) $this->option('timeframe');
        $limit = max(1, (int) $this->option('limit'));

        $query = CryptoPair::query()->where('is_active', true);

        if ($targetExchange !== 'all') {
            $query->where('exchange', $targetExchange);
        }

        $pairs = $query->orderBy('exchange')->orderBy('id')->get();

        if ($pairs->isEmpty()) {
            $this->warn('Tidak ada pasangan koin aktif yang ditemukan untuk parameter ini.');
            return self::SUCCESS;
        }

        foreach ($pairs as $pair) {
            $startedAt = microtime(true);

            try {
                $driver = $this->exchangeManager->driver($pair->exchange);
                $candles = $driver->fetchOhlcv($pair, $timeframe, $limit);

                if ($candles->isEmpty()) {
                    $this->warn("Data kosong diterima dari {$pair->exchange} untuk {$pair->symbol}.");
                    continue;
                }

                $now = now();
                $rows = $candles->map(fn (array $candle): array => [
                    'crypto_pair_id' => $pair->id,
                    'timeframe'      => $timeframe,
                    'open'           => $candle['open'],
                    'high'           => $candle['high'],
                    'low'            => $candle['low'],
                    'close'          => $candle['close'],
                    'volume'         => $candle['volume'],
                    'candle_time'    => $candle['candle_time']->format('Y-m-d H:i:s'),
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ])->all();

                OhlcvCandle::query()->upsert(
                    $rows,
                    ['crypto_pair_id', 'timeframe', 'candle_time'],
                    ['open', 'high', 'low', 'close', 'volume', 'updated_at']
                );

                $this->recordLog($pair->exchange, 'OHLCV_SYNC', 'SUCCESS', count($rows), $startedAt, 'Sinkronisasi berhasil.');
                $this->info("✔ [{$pair->exchange}] {$pair->symbol}: " . count($rows) . " candle tersimpan.");
            } catch (Throwable $e) {
                $this->recordLog($pair->exchange, 'OHLCV_SYNC', 'FAILED', 0, $startedAt, $e->getMessage());
                $this->error("✖ [{$pair->exchange}] {$pair->symbol}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function recordLog(string $exchange, string $endpoint, string $status, int $count, float $startedAt, string $msg): void
    {
        MiningLog::query()->create([
            'exchange'         => $exchange,
            'endpoint'         => $endpoint,
            'status'           => $status,
            'records_fetched'  => $count,
            'response_time_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            'message'          => $msg,
        ]);
    }
}
