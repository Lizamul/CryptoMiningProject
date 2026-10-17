<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CryptoPair;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MarketChartController extends Controller
{
    public function getCandles(Request $request, CryptoPair $pair): JsonResponse
    {
        $validated = $request->validate([
            'timeframe' => ['nullable', 'string', 'in:1m,5m,15m,1h,1d'],
            'limit' => ['nullable', 'integer', 'min:50', 'max:300'],
        ]);

        $timeframe = (string) ($validated['timeframe'] ?? '1h');
        $limit = (int) ($validated['limit'] ?? 100);

        $candles = $pair->candles()
            ->where('timeframe', $timeframe)
            ->orderByDesc('candle_time')
            ->limit($limit)
            ->get()
            ->sortBy('candle_time')
            ->values()
            ->map(fn ($candle): array => [
                'time' => (int) $candle->candle_time->timestamp,
                'open' => (float) $candle->open,
                'high' => (float) $candle->high,
                'low' => (float) $candle->low,
                'close' => (float) $candle->close,
                'volume' => (float) $candle->volume,
            ]);

        return response()->json($candles);
    }
}
