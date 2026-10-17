<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CryptoPair;
use App\Models\MiningLog;
use App\Models\OhlcvCandle;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $activePairs = CryptoPair::query()
            ->withCount('candles')
            ->where('is_active', true)
            ->orderBy('exchange')
            ->orderBy('symbol')
            ->get();
        $auditLogs = MiningLog::query()->latest()->take(10)->get();
        $totalCandles = OhlcvCandle::query()->count();

        return view('dashboard.index', compact('activePairs', 'auditLogs', 'totalCandles'));
    }
}
