<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ohlcv_candles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crypto_pair_id')->constrained()->cascadeOnDelete();
            $table->string('timeframe', 5);
            $table->decimal('open', 18, 8);
            $table->decimal('high', 18, 8);
            $table->decimal('low', 18, 8);
            $table->decimal('close', 18, 8);
            $table->decimal('volume', 24, 8);
            $table->timestamp('candle_time');
            $table->timestamps();

            $table->unique(['crypto_pair_id', 'timeframe', 'candle_time']);
            $table->index(['crypto_pair_id', 'candle_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ohlcv_candles');
    }
};
