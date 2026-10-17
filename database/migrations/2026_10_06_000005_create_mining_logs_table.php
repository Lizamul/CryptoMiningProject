<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mining_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('exchange');
            $table->string('endpoint');
            $table->string('status', 10);
            $table->unsignedInteger('records_fetched')->default(0);
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['exchange', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mining_logs');
    }
};
