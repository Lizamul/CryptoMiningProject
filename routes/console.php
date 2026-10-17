<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('crypto:mine --exchange=all --timeframe=1h --limit=10')
    ->hourly();

Schedule::command('crypto:mine --exchange=indodax --timeframe=5m --limit=5')
    ->everyFiveMinutes();
