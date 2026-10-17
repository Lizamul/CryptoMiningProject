<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CryptoPair;

class CryptoPairSeeder extends Seeder
{
    public function run(): void
    {
        $pairs = [
            // Indodax (Pasar Domestik IDR)
            [
                'exchange'        => 'indodax',
                'symbol'          => 'BTC/IDR',
                'exchange_symbol' => 'btcidr',
                'base_asset'      => 'BTC',
                'quote_asset'     => 'IDR',
                'is_active'       => true,
            ],
            [
                'exchange'        => 'indodax',
                'symbol'          => 'ETH/IDR',
                'exchange_symbol' => 'ethidr',
                'base_asset'      => 'ETH',
                'quote_asset'     => 'IDR',
                'is_active'       => true,
            ],

            // Binance (Likuiditas Global USDT)
            [
                'exchange'        => 'binance',
                'symbol'          => 'BTC/USDT',
                'exchange_symbol' => 'BTCUSDT',
                'base_asset'      => 'BTC',
                'quote_asset'     => 'USDT',
                'is_active'       => true,
            ],
            [
                'exchange'        => 'binance',
                'symbol'          => 'ETH/USDT',
                'exchange_symbol' => 'ETHUSDT',
                'base_asset'      => 'ETH',
                'quote_asset'     => 'USDT',
                'is_active'       => true,
            ],

            // CoinGecko (Data Agregat Global)
            [
                'exchange'        => 'coingecko',
                'symbol'          => 'BTC/USD',
                'exchange_symbol' => 'bitcoin',
                'base_asset'      => 'BTC',
                'quote_asset'     => 'USD',
                'is_active'       => true,
            ],
            [
                'exchange'        => 'coingecko',
                'symbol'          => 'ETH/USD',
                'exchange_symbol' => 'ethereum',
                'base_asset'      => 'ETH',
                'quote_asset'     => 'USD',
                'is_active'       => true,
            ],
        ];

        foreach ($pairs as $pair) {
            CryptoPair::updateOrCreate(
                [
                    'exchange' => $pair['exchange'],
                    'symbol'   => $pair['symbol'],
                ],
                $pair
            );
        }
    }
}
