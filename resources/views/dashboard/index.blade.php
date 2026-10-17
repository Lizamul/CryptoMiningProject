@extends('layouts.app')

@section('title', 'Market Dashboard | '.config('app.name'))

@section('content')
    @php
        $latestLog = $auditLogs->first();
        $latestStatus = $latestLog?->status ?? 'PENDING';
    @endphp

    <section class="mb-8 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
        <div>
            <p class="mb-3 text-xs font-bold uppercase tracking-[0.22em] text-signal">Market observatory / Day 3</p>
            <h1 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">Market data, in focus.</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500">Monitor the latest OHLCV collection across connected exchanges and inspect price action from one quiet, operational surface.</p>
        </div>
        <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
            <a id="export-csv" href="#" class="inline-flex items-center justify-center gap-2 rounded-xl bg-ink px-4 py-3 text-xs font-bold uppercase tracking-[0.08em] text-white shadow-lg shadow-ink/15 transition hover:-translate-y-0.5 hover:bg-slate-800">
                <span aria-hidden="true">↓</span>
                Export ML Dataset (CSV)
            </a>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">System clock</p>
                <p class="mt-1 font-display text-sm font-semibold text-ink">{{ now()->utc()->format('d M Y · H:i') }} UTC</p>
            </div>
        </div>
    </section>

    <section class="mb-8 grid gap-4 md:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Total candles stored</p>
                <span class="rounded-lg bg-signal/10 px-2 py-1 text-[10px] font-bold text-signal">OHLCV</span>
            </div>
            <p class="mt-5 font-display text-3xl font-bold tracking-tight text-ink">{{ number_format($totalCandles) }}</p>
            <p class="mt-2 text-xs text-slate-400">Across all active and historical pairs</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Active pairs</p>
                <span class="rounded-lg bg-ink/5 px-2 py-1 text-[10px] font-bold text-ink">{{ $activePairs->count() }} LIVE</span>
            </div>
            <p class="mt-5 font-display text-3xl font-bold tracking-tight text-ink">{{ number_format($activePairs->count()) }}</p>
            <p class="mt-2 text-xs text-slate-400">{{ number_format($activePairs->sum('candles_count')) }} candles linked to active pairs</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Latest sync</p>
                <span class="rounded-lg px-2 py-1 text-[10px] font-bold {{ $latestStatus === 'SUCCESS' ? 'bg-signal/10 text-signal' : ($latestStatus === 'FAILED' ? 'bg-coral/10 text-coral' : 'bg-slate-100 text-slate-500') }}">{{ $latestStatus }}</span>
            </div>
            <p class="mt-5 font-display text-2xl font-bold tracking-tight text-ink">{{ $latestLog?->created_at?->diffForHumans() ?? 'Awaiting data' }}</p>
            <p class="mt-2 text-xs text-slate-400">{{ $latestLog?->exchange ? strtoupper($latestLog->exchange).' · '.number_format($latestLog->response_time_ms ?? 0).' ms' : 'No audit event recorded' }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-5 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center lg:px-7">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-signal">Price action</p>
                <h2 class="mt-1 font-display text-xl font-bold tracking-tight text-ink">Candlestick explorer</h2>
                <div class="mt-4 flex flex-wrap items-end gap-x-6 gap-y-3" aria-live="polite">
                    <div>
                        <p id="active-symbol" class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Select a pair</p>
                        <p id="last-price" class="mt-1 font-display text-2xl font-bold tracking-tight text-ink">—</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Last candle</p>
                        <p id="price-change" class="mt-1 text-sm font-bold text-slate-500">—</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Range high</p>
                        <p id="range-high" class="mt-1 text-sm font-semibold text-ink">—</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Range low</p>
                        <p id="range-low" class="mt-1 text-sm font-semibold text-ink">—</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                    <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Pair</span>
                    <select id="pair-select" class="min-w-36 cursor-pointer bg-transparent text-sm font-semibold text-ink outline-none">
                        @forelse ($activePairs as $pair)
                            <option value="{{ $pair->id }}">{{ strtoupper($pair->exchange) }} · {{ $pair->symbol }}</option>
                        @empty
                            <option value="">No active pairs</option>
                        @endforelse
                    </select>
                </label>
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                    <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Frame</span>
                    <select id="timeframe-select" class="cursor-pointer bg-transparent text-sm font-semibold text-ink outline-none">
                        @foreach (['1m', '5m', '1h', '1d'] as $timeframe)
                            <option value="{{ $timeframe }}" @selected($timeframe === '1h')>{{ $timeframe }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>
        <div class="relative bg-[#0f172a] px-3 py-5 sm:px-5 lg:px-7">
            <div id="chart-legend" class="pointer-events-none absolute left-6 top-5 z-10 text-xs font-medium tracking-wide text-[#94a3b8] sm:left-8">O: — | H: — | L: — | C: — | Vol: —</div>
            <div id="chart-status" class="absolute left-1/2 top-1/2 z-10 hidden -translate-x-1/2 -translate-y-1/2 rounded-xl border border-slate-700 bg-slate-800 px-4 py-3 text-sm font-medium text-slate-300 shadow-lg"></div>
            <div id="candlestick-chart" class="h-[30rem] w-full"></div>
        </div>
    </section>

    <section class="mt-8 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-5 lg:px-7">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-signal">Execution trail</p>
            <h2 class="mt-1 font-display text-xl font-bold tracking-tight text-ink">Mining audit log</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[680px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">
                    <tr>
                        <th class="px-5 py-3 lg:px-7">Time</th>
                        <th class="px-5 py-3">Exchange</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Duration</th>
                        <th class="px-5 py-3 lg:px-7">Message</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($auditLogs as $log)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-5 py-4 text-slate-500 lg:px-7">{{ $log->created_at?->format('d M Y, H:i:s') }}</td>
                            <td class="px-5 py-4 font-semibold uppercase text-ink">{{ $log->exchange }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-bold {{ $log->status === 'SUCCESS' ? 'bg-signal/10 text-signal' : 'bg-coral/10 text-coral' }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $log->status }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">{{ number_format($log->response_time_ms ?? 0) }} ms</td>
                            <td class="max-w-sm truncate px-5 py-4 text-slate-500 lg:px-7" title="{{ $log->message }}">{{ $log->message ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">Belum ada aktivitas mining yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>
    <script>
        (() => {
            const chartContainer = document.getElementById('candlestick-chart');
            const chartStatus = document.getElementById('chart-status');
            const pairSelect = document.getElementById('pair-select');
            const timeframeSelect = document.getElementById('timeframe-select');
            const exportCsv = document.getElementById('export-csv');
            const activeSymbol = document.getElementById('active-symbol');
            const lastPrice = document.getElementById('last-price');
            const priceChange = document.getElementById('price-change');
            const rangeHigh = document.getElementById('range-high');
            const rangeLow = document.getElementById('range-low');
            const chartLegend = document.getElementById('chart-legend');
            let activeRequest = null;

            const priceFormatter = new Intl.NumberFormat('en-US', { maximumFractionDigits: 8 });
            const formatPrice = (value) => priceFormatter.format(value);
            const addSeries = (type, legacyMethod, options) => {
                if (typeof chart[legacyMethod] === 'function') {
                    return chart[legacyMethod](options);
                }
                return chart.addSeries(LightweightCharts[type], options);
            };

            const updateExportLink = () => {
                const params = new URLSearchParams({
                    crypto_pair_id: pairSelect.value,
                    timeframe: timeframeSelect.value,
                });
                exportCsv.href = pairSelect.value ? `{{ route('export.csv') }}?${params.toString()}` : '#';
                exportCsv.classList.toggle('pointer-events-none', !pairSelect.value);
                exportCsv.classList.toggle('opacity-50', !pairSelect.value);
            };

            const chart = LightweightCharts.createChart(chartContainer, {
                layout: {
                    background: { color: '#0f172a' },
                    textColor: '#94a3b8',
                    fontFamily: 'DM Sans, sans-serif',
                },
                grid: {
                    vertLines: { color: '#1e293b' },
                    horzLines: { color: '#1e293b' },
                },
                crosshair: { mode: LightweightCharts.CrosshairMode.Normal },
                rightPriceScale: { borderColor: '#334155', scaleMargins: { top: 0.08, bottom: 0.22 } },
                timeScale: { borderColor: '#334155', timeVisible: true },
            });
            const candleSeries = addSeries('CandlestickSeries', 'addCandlestickSeries', {
                upColor: '#26a69a',
                downColor: '#ef5350',
                borderVisible: false,
                wickUpColor: '#26a69a',
                wickDownColor: '#ef5350',
            });
            const volumeSeries = addSeries('HistogramSeries', 'addHistogramSeries', {
                priceFormat: { type: 'volume' },
                priceScaleId: '',
                scaleMargins: { top: 0.8, bottom: 0 },
            });
            const MOVING_AVERAGE_PERIOD = 9;
            const maSeries = addSeries('LineSeries', 'addLineSeries', {
                color: '#f59e0b',
                lineWidth: 2,
                priceScaleId: 'right',
                priceLineVisible: false,
                lastValueVisible: false,
            });

            const calculateMovingAverage = (candles, period = MOVING_AVERAGE_PERIOD) => candles.reduce((result, candle, index) => {
                if (index < period - 1) {
                    return result;
                }

                const window = candles.slice(index - period + 1, index + 1);
                const average = window.reduce((sum, item) => sum + item.close, 0) / period;
                result.push({ time: candle.time, value: average });
                return result;
            }, []);

            const updateMarketStats = (candles) => {
                const selectedOption = pairSelect.options[pairSelect.selectedIndex];
                activeSymbol.textContent = selectedOption?.textContent || 'Select a pair';

                if (!candles.length) {
                    lastPrice.textContent = '—';
                    priceChange.textContent = '—';
                    rangeHigh.textContent = '—';
                    rangeLow.textContent = '—';
                    priceChange.className = 'mt-1 text-sm font-bold text-slate-500';
                    return;
                }

                const latest = candles[candles.length - 1];
                const change = latest.open === 0 ? 0 : ((latest.close - latest.open) / latest.open) * 100;
                const high = Math.max(...candles.map((candle) => candle.high));
                const low = Math.min(...candles.map((candle) => candle.low));
                lastPrice.textContent = formatPrice(latest.close);
                priceChange.textContent = `${change >= 0 ? '+' : ''}${change.toFixed(2)}%`;
                priceChange.className = `mt-1 text-sm font-bold ${change >= 0 ? 'text-signal' : 'text-coral'}`;
                rangeHigh.textContent = formatPrice(high);
                rangeLow.textContent = formatPrice(low);
            };

            const setStatus = (message, visible = true) => {
                chartStatus.textContent = message;
                chartStatus.classList.toggle('hidden', !visible);
            };

            const loadCandles = async ({ fitContent = true } = {}) => {
                const pairId = pairSelect.value;
                const timeframe = timeframeSelect.value;

                if (!pairId) {
                    candleSeries.setData([]);
                    setStatus('Pilih pasangan koin untuk memuat grafik.');
                    return;
                }

                if (activeRequest) {
                    activeRequest.abort();
                }
                const requestController = new AbortController();
                activeRequest = requestController;
                setStatus('Memuat data pasar...');

                try {
                    const response = await fetch(`/api/chart/${pairId}?timeframe=${encodeURIComponent(timeframe)}&limit=300`, {
                        headers: { 'Accept': 'application/json' },
                        signal: requestController.signal,
                    });

                    if (!response.ok) {
                        throw new Error('Chart data request failed');
                    }

                    const candles = await response.json();
                    const candleData = candles.map(({ time, open, high, low, close }) => ({ time, open, high, low, close }));
                    const volumeData = candles.map((candle) => ({
                        time: candle.time,
                        value: candle.volume,
                        color: candle.close >= candle.open ? 'rgba(38, 166, 154, 0.5)' : 'rgba(239, 83, 80, 0.5)',
                    }));
                    candleSeries.setData(candleData);
                    volumeSeries.setData(volumeData);
                    maSeries.setData(calculateMovingAverage(candles));
                    updateMarketStats(candles);
                    if (fitContent) {
                        chart.timeScale().fitContent();
                    }
                    setStatus(candles.length ? '' : 'Belum ada riwayat candle untuk pilihan ini.', !candles.length);
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    candleSeries.setData([]);
                    volumeSeries.setData([]);
                    maSeries.setData([]);
                    updateMarketStats([]);
                    setStatus('Data grafik belum dapat dimuat. Coba lagi sebentar.');
                } finally {
                    if (activeRequest === requestController) {
                        activeRequest = null;
                    }
                }
            };

            chart.subscribeCrosshairMove((param) => {
                if (!param.time || !param.seriesData) {
                    chartLegend.textContent = 'O: — | H: — | L: — | C: — | Vol: —';
                    return;
                }

                const candle = param.seriesData.get(candleSeries);
                const volume = param.seriesData.get(volumeSeries);
                if (!candle) {
                    return;
                }
                chartLegend.textContent = `O: ${formatPrice(candle.open)} | H: ${formatPrice(candle.high)} | L: ${formatPrice(candle.low)} | C: ${formatPrice(candle.close)} | Vol: ${formatPrice(volume?.value || 0)}`;
            });

            pairSelect.addEventListener('change', loadCandles);
            pairSelect.addEventListener('change', updateExportLink);
            timeframeSelect.addEventListener('change', loadCandles);
            timeframeSelect.addEventListener('change', updateExportLink);
            const refreshTimer = window.setInterval(() => loadCandles({ fitContent: false }), 20000);
            window.addEventListener('resize', () => chart.applyOptions({ width: chartContainer.clientWidth }));
            window.addEventListener('beforeunload', () => {
                window.clearInterval(refreshTimer);
                activeRequest?.abort();
                chart.remove();
            });
            updateExportLink();
            loadCandles();
        })();
    </script>
@endpush
