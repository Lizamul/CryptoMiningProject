<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiningLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'exchange',
        'endpoint',
        'status',
        'records_fetched',
        'response_time_ms',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'records_fetched' => 'integer',
            'response_time_ms' => 'integer',
        ];
    }
}
