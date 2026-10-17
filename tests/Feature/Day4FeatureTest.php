<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CryptoPair;
use App\Models\OhlcvCandle;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Day4FeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard_and_exports(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/export/csv')->assertRedirect(route('login'));
        $this->get('/export/json')->assertRedirect(route('login'));
    }

    public function test_admin_can_login_and_access_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'admin@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk()->assertSee('Candlestick explorer');
    }

    public function test_invalid_login_is_rejected_and_logout_invalidates_session(): void
    {
        User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->actingAs(User::query()->first())
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_authenticated_user_can_stream_csv_and_json_dataset(): void
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $pair = CryptoPair::query()->create([
            'exchange' => 'indodax',
            'symbol' => 'BTC/IDR',
            'exchange_symbol' => 'btc_idr',
            'base_asset' => 'BTC',
            'quote_asset' => 'IDR',
            'is_active' => true,
        ]);
        OhlcvCandle::query()->create([
            'crypto_pair_id' => $pair->id,
            'timeframe' => '1h',
            'open' => '100.00000000',
            'high' => '110.00000000',
            'low' => '90.00000000',
            'close' => '105.00000000',
            'volume' => '12.50000000',
            'candle_time' => CarbonImmutable::parse('2026-01-02 03:04:05', 'UTC'),
        ]);

        $csv = $this->actingAs($user)->get('/export/csv?crypto_pair_id='.$pair->id.'&timeframe=1h');
        $csv->assertOk()
            ->assertStreamed()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename="dataset_btc_idr_1h.csv"');
        $this->assertStringContainsString('timestamp,datetime_utc,open,high,low,close,volume', $csv->streamedContent());
        $this->assertStringContainsString('100.00000000', $csv->streamedContent());

        $json = $this->actingAs($user)->get('/export/json?crypto_pair_id='.$pair->id.'&timeframe=1h');
        $json->assertOk()
            ->assertStreamed()
            ->assertHeader('content-type', 'application/json; charset=UTF-8');
        $jsonPayload = json_decode($json->streamedContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('100.00000000', $jsonPayload[0]['open']);
        $this->assertSame('12.50000000', $jsonPayload[0]['volume']);
    }
}
