<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OhlcvCandle extends Model
{
    use HasFactory;

    protected $fillable = [
        'crypto_pair_id',
        'timeframe',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'candle_time',
    ];

    protected function casts(): array
    {
        return [
            'crypto_pair_id' => 'integer',
            'open' => 'decimal:8',
            'high' => 'decimal:8',
            'low' => 'decimal:8',
            'close' => 'decimal:8',
            'volume' => 'decimal:8',
            'candle_time' => 'datetime',
        ];
    }

    public function pair(): BelongsTo
    {
        return $this->belongsTo(CryptoPair::class, 'crypto_pair_id');
    }
}
