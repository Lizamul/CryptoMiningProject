<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CryptoPair extends Model
{
    use HasFactory;

    protected $fillable = [
        'exchange',
        'symbol',
        'exchange_symbol',
        'base_asset',
        'quote_asset',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function candles(): HasMany
    {
        return $this->hasMany(OhlcvCandle::class);
    }
}
