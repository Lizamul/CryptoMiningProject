<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@cryptomining.local'],
            [
                'name' => 'Crypto Mining Administrator',
                'password' => Hash::make('ChangeMe!2026'),
            ]
        );
    }
}
