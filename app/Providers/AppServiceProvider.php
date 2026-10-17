<?php

namespace App\Providers;

use App\Services\CryptoMining\Contracts\ExchangeInterface;
use App\Services\CryptoMining\Drivers\IndodaxDriver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ExchangeInterface::class, IndodaxDriver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
