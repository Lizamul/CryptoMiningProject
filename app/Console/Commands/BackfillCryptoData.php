<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CryptoPair;
use App\Models\MiningLog;
use App\Services\CryptoMining\HistoricalBackfillService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

class BackfillCryptoData extends Command
{
    protected $signature = 'crypto:backfill
                            {--pair= : ID atau simbol koin tertentu}
                            {--days=180 : Jumlah hari mundur}
                            {--timeframe=1h : Timeframe lilin}';

    protected $description = 'Melakukan penarikan mundur data historis OHLCV hingga ratusan hari ke belakang.';

    public function __construct(private readonly HistoricalBackfillService $backfillService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $timeframe = (string) $this->option('timeframe');
        $pairs = $this->resolvePairs();

        if ($days < 1) {
            $this->error('Option --days harus lebih besar dari nol.');
            return self::FAILURE;
        }

        if ($pairs->isEmpty()) {
            $this->warn('Tidak ada pasangan koin aktif yang cocok untuk backfill.');
            return self::SUCCESS;
        }

        $progress = $this->output->createProgressBar($pairs->count());
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $progress->setMessage('Memulai backfill...');
        $progress->start();

        foreach ($pairs as $pair) {
            $startedAt = microtime(true);
            $progress->setMessage(sprintf('%s · %s', strtoupper($pair->exchange), $pair->symbol));

            try {
                $recordsFetched = $this->backfillService->backfill(
                    $pair,
                    $days,
                    $timeframe,
                    static function (int $batchSize): void {
                        // The outer progress bar advances once per pair.
                    }
                );

                $this->recordLog(
                    $pair->exchange,
                    $this->endpointFor($pair->exchange),
                    'SUCCESS',
                    $recordsFetched,
                    $startedAt,
                    sprintf('Historical backfill %d hari selesai.', $days)
                );
            } catch (Throwable $exception) {
                $this->recordLog(
                    $pair->exchange,
                    $this->endpointFor($pair->exchange),
                    'FAILED',
                    0,
                    $startedAt,
                    $exception->getMessage()
                );
                $this->newLine();
                $this->error(sprintf('%s: %s', $pair->symbol, $exception->getMessage()));
            } finally {
                $progress->advance();
            }
        }

        $progress->setMessage('Selesai');
        $progress->finish();
        $this->newLine(2);

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, CryptoPair>
     */
    private function resolvePairs(): Collection
    {
        $query = CryptoPair::query()->where('is_active', true);
        $pairOption = trim((string) $this->option('pair'));

        if ($pairOption !== '') {
            if (ctype_digit($pairOption)) {
                $query->whereKey((int) $pairOption);
            } else {
                $query->where(function ($pairQuery) use ($pairOption): void {
                    $pairQuery
                        ->where('symbol', $pairOption)
                        ->orWhere('exchange_symbol', strtolower($pairOption));
                });
            }
        }

        return $query->orderBy('exchange')->orderBy('id')->get();
    }

    private function endpointFor(string $exchange): string
    {
        return match ($exchange) {
            'binance' => '/api/v3/klines',
            'indodax' => '/tradingview/history_v2',
            'coingecko' => '/api/v3/coins/{id}/ohlc',
            default => 'historical-backfill',
        };
    }

    private function recordLog(
        string $exchange,
        string $endpoint,
        string $status,
        int $recordsFetched,
        float $startedAt,
        string $message
    ): void {
        MiningLog::query()->create([
            'exchange' => $exchange,
            'endpoint' => $endpoint,
            'status' => $status,
            'records_fetched' => $recordsFetched,
            'response_time_ms' => max(0, (int) round((microtime(true) - $startedAt) * 1000)),
            'message' => $message,
        ]);
    }
}
