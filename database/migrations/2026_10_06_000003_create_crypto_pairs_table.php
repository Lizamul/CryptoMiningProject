<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_pairs', function (Blueprint $table): void {
            $table->id();
            $table->string('exchange');
            $table->string('symbol');
            $table->string('exchange_symbol');
            $table->string('base_asset');
            $table->string('quote_asset');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['exchange', 'symbol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_pairs');
    }
};
