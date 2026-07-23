<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\AggregateProviderStats;
use App\Models\ProviderEvent;
use App\Models\ProviderStatsDaily;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    private User $prestataire;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prestataire = User::factory()->prestataire()->create();
        $this->token = JWTAuth::fromUser($this->prestataire);
    }

    // ── Access control ──

    public function test_stats_requires_auth(): void
    {
        $this->getJson('/api/v1/providers/' . $this->prestataire->id . '/stats')
            ->assertUnauthorized();
    }

    public function test_stats_requires_prestataire(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $clientToken = JWTAuth::fromUser($client);

        $this->getJson("/api/v1/providers/{$client->id}/stats", [
            'Authorization' => "Bearer {$clientToken}",
        ])->assertForbidden();
    }

    public function test_stats_rejects_other_users(): void
    {
        $other = User::factory()->prestataire()->create();

        $this->getJson("/api/v1/providers/{$other->id}/stats", [
            'Authorization' => "Bearer {$this->token}",
        ])->assertForbidden();
    }

    public function test_stats_validates_period(): void
    {
        $this->getJson("/api/v1/providers/{$this->prestataire->id}/stats?period=invalid", [
            'Authorization' => "Bearer {$this->token}",
        ])->assertStatus(422);
    }

    // ── Basic stats (7d) ──

    public function test_stats_returns_7d_data(): void
    {
        ProviderStatsDaily::create([
            'provider_id' => $this->prestataire->id,
            'date' => now()->subDays(2),
            'visits' => 10,
            'clicks' => 5,
            'contacts' => 2,
            'favorites_count' => 3,
            'quote_requests_count' => 1,
        ]);

        ProviderStatsDaily::create([
            'provider_id' => $this->prestataire->id,
            'date' => now()->subDays(1),
            'visits' => 15,
            'clicks' => 8,
            'contacts' => 3,
            'favorites_count' => 1,
            'quote_requests_count' => 2,
        ]);

        $response = $this->getJson("/api/v1/providers/{$this->prestataire->id}/stats?period=7d", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('period', '7d')
            ->assertJsonPath('totals.visits', 25)
            ->assertJsonPath('totals.clicks', 13)
            ->assertJsonPath('totals.contacts', 5)
            ->assertJsonPath('totals.favorites_count', 4)
            ->assertJsonPath('totals.quote_requests_count', 3)
            ->assertJsonCount(2, 'daily');
    }

    public function test_stats_7d_available_without_subscription(): void
    {
        ProviderStatsDaily::create([
            'provider_id' => $this->prestataire->id,
            'date' => now()->subDay(),
            'visits' => 5,
            'clicks' => 2,
            'contacts' => 1,
            'favorites_count' => 0,
            'quote_requests_count' => 1,
        ]);

        $this->getJson("/api/v1/providers/{$this->prestataire->id}/stats?period=7d", [
            'Authorization' => "Bearer {$this->token}",
        ])->assertOk();
    }

    // ── Advanced stats (30d, 12m) ──

    public function test_stats_30d_requires_advanced_plan(): void
    {
        $this->getJson("/api/v1/providers/{$this->prestataire->id}/stats?period=30d", [
            'Authorization' => "Bearer {$this->token}",
        ])->assertForbidden()
            ->assertJsonPath('message', 'Statistiques avancées réservées aux plans Pro et Premium.');
    }

    public function test_stats_12m_requires_advanced_plan(): void
    {
        $this->getJson("/api/v1/providers/{$this->prestataire->id}/stats?period=12m", [
            'Authorization' => "Bearer {$this->token}",
        ])->assertForbidden();
    }

    public function test_stats_30d_allows_pro_plan(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        ProviderStatsDaily::create([
            'provider_id' => $this->prestataire->id,
            'date' => now()->subDays(5),
            'visits' => 50,
            'clicks' => 20,
            'contacts' => 10,
            'favorites_count' => 5,
            'quote_requests_count' => 8,
        ]);

        $this->getJson("/api/v1/providers/{$this->prestataire->id}/stats?period=30d", [
            'Authorization' => "Bearer {$this->token}",
        ])->assertOk()
            ->assertJsonPath('totals.visits', 50);
    }

    public function test_stats_12m_allows_premium_plan(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'premium',
            'period' => 'yearly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);

        ProviderStatsDaily::create([
            'provider_id' => $this->prestataire->id,
            'date' => now()->subMonths(3),
            'visits' => 100,
            'clicks' => 40,
            'contacts' => 15,
            'favorites_count' => 10,
            'quote_requests_count' => 12,
        ]);

        $this->getJson("/api/v1/providers/{$this->prestataire->id}/stats?period=12m", [
            'Authorization' => "Bearer {$this->token}",
        ])->assertOk()
            ->assertJsonPath('totals.visits', 100);
    }

    // ── Period filtering ──

    public function test_stats_7d_only_includes_last_7_days(): void
    {
        // Inside 7d
        ProviderStatsDaily::create([
            'provider_id' => $this->prestataire->id,
            'date' => now()->subDays(3),
            'visits' => 10,
            'clicks' => 0,
            'contacts' => 0,
            'favorites_count' => 0,
            'quote_requests_count' => 0,
        ]);

        // Outside 7d
        ProviderStatsDaily::create([
            'provider_id' => $this->prestataire->id,
            'date' => now()->subDays(10),
            'visits' => 999,
            'clicks' => 0,
            'contacts' => 0,
            'favorites_count' => 0,
            'quote_requests_count' => 0,
        ]);

        $this->getJson("/api/v1/providers/{$this->prestataire->id}/stats?period=7d", [
            'Authorization' => "Bearer {$this->token}",
        ])->assertOk()
            ->assertJsonPath('totals.visits', 10)
            ->assertJsonCount(1, 'daily');
    }

    // ── Event tracking ──

    public function test_portfolio_view_logs_visit_event(): void
    {
        $this->postJson("/api/v1/providers/{$this->prestataire->id}/portfolio", [
            'title' => 'Test',
        ], ['Authorization' => "Bearer {$this->token}"]);

        // Actually we test via the public index endpoint
        $this->getJson("/api/v1/providers/{$this->prestataire->id}/portfolio");

        $this->assertDatabaseHas('provider_events', [
            'provider_id' => $this->prestataire->id,
            'event_type' => 'visit',
        ]);
    }

    public function test_favorite_logs_favorite_event(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $clientToken = JWTAuth::fromUser($client);

        $this->postJson('/api/v1/favorites', [
            'provider_id' => $this->prestataire->id,
        ], ['Authorization' => "Bearer {$clientToken}"]);

        $this->assertDatabaseHas('provider_events', [
            'provider_id' => $this->prestataire->id,
            'event_type' => 'favorite',
        ]);
    }

    public function test_quote_request_logs_event(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $clientToken = JWTAuth::fromUser($client);

        $this->postJson('/api/v1/quotes', [
            'provider_id' => $this->prestataire->id,
            'service_type' => 'DJ',
        ], ['Authorization' => "Bearer {$clientToken}"]);

        $this->assertDatabaseHas('provider_events', [
            'provider_id' => $this->prestataire->id,
            'event_type' => 'quote_request',
        ]);
    }

    // ── Aggregation job ──

    public function test_aggregation_job_counts_events(): void
    {
        $yesterday = now()->subDay()->toDateString();

        ProviderEvent::create(['provider_id' => $this->prestataire->id, 'event_type' => 'visit', 'created_at' => now()->subDay()->setTime(10, 0)]);
        ProviderEvent::create(['provider_id' => $this->prestataire->id, 'event_type' => 'visit', 'created_at' => now()->subDay()->setTime(14, 0)]);
        ProviderEvent::create(['provider_id' => $this->prestataire->id, 'event_type' => 'click_contact', 'created_at' => now()->subDay()->setTime(11, 0)]);
        ProviderEvent::create(['provider_id' => $this->prestataire->id, 'event_type' => 'favorite', 'created_at' => now()->subDay()->setTime(12, 0)]);
        ProviderEvent::create(['provider_id' => $this->prestataire->id, 'event_type' => 'quote_request', 'created_at' => now()->subDay()->setTime(13, 0)]);

        (new AggregateProviderStats())->handle();

        $this->assertDatabaseHas('provider_stats_daily', [
            'provider_id' => $this->prestataire->id,
            'date' => $yesterday,
            'visits' => 2,
            'clicks' => 1,
            'contacts' => 0,
            'favorites_count' => 1,
            'quote_requests_count' => 1,
        ]);
    }

    public function test_aggregation_job_is_idempotent(): void
    {
        $yesterday = now()->subDay()->toDateString();

        ProviderEvent::create(['provider_id' => $this->prestataire->id, 'event_type' => 'visit', 'created_at' => now()->subDay()->setTime(10, 0)]);

        (new AggregateProviderStats())->handle();
        (new AggregateProviderStats())->handle(); // run twice

        $count = ProviderStatsDaily::where('provider_id', $this->prestataire->id)
            ->where('date', $yesterday)
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_aggregation_job_multiple_providers(): void
    {
        $other = User::factory()->prestataire()->create();
        $yesterday = now()->subDay()->toDateString();

        ProviderEvent::create(['provider_id' => $this->prestataire->id, 'event_type' => 'visit', 'created_at' => now()->subDay()]);
        ProviderEvent::create(['provider_id' => $other->id, 'event_type' => 'visit', 'created_at' => now()->subDay()]);
        ProviderEvent::create(['provider_id' => $other->id, 'event_type' => 'visit', 'created_at' => now()->subDay()]);

        (new AggregateProviderStats())->handle();

        $this->assertDatabaseHas('provider_stats_daily', [
            'provider_id' => $this->prestataire->id,
            'visits' => 1,
        ]);

        $this->assertDatabaseHas('provider_stats_daily', [
            'provider_id' => $other->id,
            'visits' => 2,
        ]);
    }

    // ── Empty data ──

    public function test_stats_returns_zeros_when_no_data(): void
    {
        $this->getJson("/api/v1/providers/{$this->prestataire->id}/stats?period=7d", [
            'Authorization' => "Bearer {$this->token}",
        ])->assertOk()
            ->assertJsonPath('totals.visits', 0)
            ->assertJsonCount(0, 'daily');
    }

    // ── Has advanced stats helper ──

    public function test_has_advanced_stats_false_for_starter(): void
    {
        $service = app(\App\Services\SubscriptionService::class);
        $this->assertFalse($service->hasAdvancedStats($this->prestataire->id));
    }

    public function test_has_advanced_stats_true_for_pro(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $service = app(\App\Services\SubscriptionService::class);
        $this->assertTrue($service->hasAdvancedStats($this->prestataire->id));
    }

    // ── My stats endpoint ──

    public function test_my_stats_returns_own_data_without_id(): void
    {
        ProviderStatsDaily::create([
            'provider_id' => $this->prestataire->id,
            'date' => now()->subDay(),
            'visits' => 7,
            'clicks' => 3,
            'contacts' => 1,
            'favorites_count' => 2,
            'quote_requests_count' => 0,
        ]);

        $this->getJson('/api/v1/my/stats?period=7d', [
            'Authorization' => "Bearer {$this->token}",
        ])->assertOk()
            ->assertJsonPath('totals.visits', 7)
            ->assertJsonCount(1, 'daily');
    }

    public function test_my_stats_requires_auth(): void
    {
        $this->getJson('/api/v1/my/stats')->assertUnauthorized();
    }

    public function test_my_stats_rejects_client(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $clientToken = JWTAuth::fromUser($client);

        $this->getJson('/api/v1/my/stats', [
            'Authorization' => "Bearer {$clientToken}",
        ])->assertForbidden();
    }
}
