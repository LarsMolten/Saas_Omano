<?php

namespace Tests\Feature\Api\V1;

use App\Console\Commands\ExpireSubscriptions;
use App\Jobs\SendNotification;
use App\Models\PortfolioItem;
use App\Models\PortfolioMedia;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class SubscriptionTest extends TestCase
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

    // ── Plans endpoint ──

    public function test_plans_returns_plan_catalogue(): void
    {
        $response = $this->getJson('/api/v1/subscriptions/plans');

        $response->assertOk()
            ->assertJsonCount(3)
            ->assertJsonFragment(['label' => 'Starter'])
            ->assertJsonFragment(['label' => 'Pro'])
            ->assertJsonFragment(['label' => 'Premium']);
    }

    public function test_plans_includes_limits(): void
    {
        $response = $this->getJson('/api/v1/subscriptions/plans');

        $response->assertOk();

        $plans = $response->json();
        $starter = collect($plans)->firstWhere('label', 'Starter');

        $this->assertNotNull($starter);
        $this->assertEquals(10, $starter['limits']['max_portfolio_media']);
        $this->assertEquals(3, $starter['limits']['max_services']);
        $this->assertFalse($starter['limits']['allows_video']);
        $this->assertFalse($starter['limits']['has_pro_badge']);
        $this->assertFalse($starter['limits']['has_search_boost']);

        $premium = collect($plans)->firstWhere('label', 'Premium');
        $this->assertNull($premium['limits']['max_portfolio_media']);
        $this->assertNull($premium['limits']['max_services']);
        $this->assertTrue($premium['limits']['has_search_boost']);
        $this->assertTrue($premium['limits']['has_advanced_stats']);
    }

    // ── Checkout ──

    public function test_checkout_creates_subscription(): void
    {
        $response = $this->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'pro',
            'period' => 'monthly',
        ], ['Authorization' => "Bearer {$this->token}"]);

        $response->assertCreated()
            ->assertJsonFragment(['plan' => 'pro', 'period' => 'monthly', 'status' => 'active']);

        $this->assertDatabaseHas('subscriptions', [
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
        ]);
    }

    public function test_checkout_sets_correct_end_date_monthly(): void
    {
        $this->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'starter',
            'period' => 'monthly',
        ], ['Authorization' => "Bearer {$this->token}"]);

        $sub = Subscription::where('provider_id', $this->prestataire->id)->first();
        $this->assertTrue($sub->ends_at->isNextMonth());
    }

    public function test_checkout_sets_correct_end_date_yearly(): void
    {
        $this->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'premium',
            'period' => 'yearly',
        ], ['Authorization' => "Bearer {$this->token}"]);

        $sub = Subscription::where('provider_id', $this->prestataire->id)->first();
        $this->assertTrue($sub->ends_at->isNextYear());
    }

    public function test_checkout_rejects_client(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $clientToken = JWTAuth::fromUser($client);

        $this->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'pro',
            'period' => 'monthly',
        ], ['Authorization' => "Bearer {$clientToken}"])->assertForbidden();
    }

    public function test_checkout_cancels_previous_subscription(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'starter',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addWeek(),
        ]);

        $this->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'pro',
            'period' => 'yearly',
        ], ['Authorization' => "Bearer {$this->token}"])->assertCreated();

        $this->assertDatabaseHas('subscriptions', [
            'provider_id' => $this->prestataire->id,
            'plan' => 'starter',
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'status' => 'active',
        ]);
    }

    public function test_checkout_rejects_duplicate_active_plan(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'pro',
            'period' => 'monthly',
        ], ['Authorization' => "Bearer {$this->token}"])->assertStatus(409);
    }

    public function test_checkout_requires_auth(): void
    {
        $this->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'pro',
            'period' => 'monthly',
        ])->assertUnauthorized();
    }

    public function test_checkout_validates_plan(): void
    {
        $this->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'ultra',
            'period' => 'monthly',
        ], ['Authorization' => "Bearer {$this->token}"])->assertUnprocessable();
    }

    // ── Current subscription ──

    public function test_current_returns_active_subscription(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'yearly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);

        $response = $this->getJson('/api/v1/subscriptions/current', [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('plan', 'pro')
            ->assertJsonPath('limits.allows_video', true)
            ->assertJsonPath('limits.has_pro_badge', true)
            ->assertJsonPath('remaining.media', null)
            ->assertJsonPath('remaining.services', null);
    }

    public function test_current_returns_starter_when_no_subscription(): void
    {
        $response = $this->getJson('/api/v1/subscriptions/current', [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('plan', 'starter')
            ->assertJsonPath('subscription', null)
            ->assertJsonPath('limits.max_portfolio_media', 10)
            ->assertJsonPath('limits.max_services', 3);
    }

    public function test_current_requires_prestataire(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $clientToken = JWTAuth::fromUser($client);

        $this->getJson('/api/v1/subscriptions/current', [
            'Authorization' => "Bearer {$clientToken}",
        ])->assertForbidden();
    }

    // ── Portfolio media limits ──

    public function test_starter_portfolio_limit_enforced(): void
    {
        Config::set('subscription.plans.starter.limits.max_portfolio_media', 2);

        $item = PortfolioItem::create([
            'provider_id' => $this->prestataire->id,
            'title' => 'Test item',
        ]);

        // Already at limit
        PortfolioMedia::create(['portfolio_item_id' => $item->id, 'type' => 'image', 'url' => 'a.jpg', 'position' => 1]);
        PortfolioMedia::create(['portfolio_item_id' => $item->id, 'type' => 'image', 'url' => 'b.jpg', 'position' => 2]);

        $subscriptionService = app(\App\Services\SubscriptionService::class);

        $this->assertFalse($subscriptionService->canAddMedia($this->prestataire->id));
        $this->assertEquals(0, $subscriptionService->remainingMediaSlots($this->prestataire->id));
    }

    public function test_pro_allows_unlimited_portfolio_media(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $item = PortfolioItem::create([
            'provider_id' => $this->prestataire->id,
            'title' => 'Test item',
        ]);

        for ($i = 0; $i < 20; $i++) {
            PortfolioMedia::create(['portfolio_item_id' => $item->id, 'type' => 'image', 'url' => "{$i}.jpg", 'position' => $i + 1]);
        }

        $subscriptionService = app(\App\Services\SubscriptionService::class);

        $this->assertTrue($subscriptionService->canAddMedia($this->prestataire->id));
        $this->assertNull($subscriptionService->remainingMediaSlots($this->prestataire->id));
    }

    // ── Service limits ──

    public function test_starter_service_limit_enforced(): void
    {
        Config::set('subscription.plans.starter.limits.max_services', 2);

        Service::create(['provider_id' => $this->prestataire->id, 'title' => 'S1', 'price_type' => 'fixed']);
        Service::create(['provider_id' => $this->prestataire->id, 'title' => 'S2', 'price_type' => 'fixed']);

        $subscriptionService = app(\App\Services\SubscriptionService::class);

        $this->assertFalse($subscriptionService->canAddService($this->prestataire->id));
        $this->assertEquals(0, $subscriptionService->remainingServiceSlots($this->prestataire->id));
    }

    public function test_starter_service_limit_blocks_store(): void
    {
        Config::set('subscription.plans.starter.limits.max_services', 1);

        Service::create(['provider_id' => $this->prestataire->id, 'title' => 'S1', 'price_type' => 'fixed']);

        $this->postJson("/api/v1/providers/{$this->prestataire->id}/services", [
            'title' => 'S2',
            'price_type' => 'fixed',
        ], ['Authorization' => "Bearer {$this->token}"])->assertForbidden();
    }

    public function test_pro_allows_unlimited_services(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        for ($i = 0; $i < 5; $i++) {
            Service::create(['provider_id' => $this->prestataire->id, 'title' => "S{$i}", 'price_type' => 'fixed']);
        }

        $subscriptionService = app(\App\Services\SubscriptionService::class);
        $this->assertTrue($subscriptionService->canAddService($this->prestataire->id));
    }

    // ── Expiration ──

    public function test_expiration_command_expires_old_subscriptions(): void
    {
        Bus::fake();

        $sub = Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);

        Artisan::call(ExpireSubscriptions::class);

        $sub->refresh();
        $this->assertEquals('expired', $sub->status);

        Bus::assertDispatched(SendNotification::class, function ($job) {
            return $job->userId === $this->prestataire->id
                && $job->type === 'subscription.expired'
                && $job->payload['plan'] === 'pro'
                && $job->emailSubject === 'Votre abonnement a expiré';
        });
    }

    public function test_expiration_command_ignores_active_subscriptions(): void
    {
        Bus::fake();

        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        Artisan::call(ExpireSubscriptions::class);

        $this->assertDatabaseHas('subscriptions', [
            'provider_id' => $this->prestataire->id,
            'status' => 'active',
        ]);

        Bus::assertNotDispatched(SendNotification::class);
    }

    // ── Plan change (upgrade/downgrade) ──

    public function test_upgrade_downgrade_replaces_subscription(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'starter',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'premium',
            'period' => 'yearly',
        ], ['Authorization' => "Bearer {$this->token}"])->assertCreated();

        $this->assertDatabaseHas('subscriptions', [
            'provider_id' => $this->prestataire->id,
            'plan' => 'starter',
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'provider_id' => $this->prestataire->id,
            'plan' => 'premium',
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/subscriptions/current', [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('plan', 'premium')
            ->assertJsonPath('limits.has_search_boost', true);
    }

    public function test_downgrade_changes_limits(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'premium',
            'period' => 'yearly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);

        $subscriptionService = app(\App\Services\SubscriptionService::class);
        $this->assertTrue($subscriptionService->allowsVideo($this->prestataire->id));
        $this->assertTrue($subscriptionService->hasSearchBoost($this->prestataire->id));

        // Cancel and switch to starter
        $this->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'starter',
            'period' => 'monthly',
        ], ['Authorization' => "Bearer {$this->token}"])->assertCreated();

        // Refresh service cache (plan resolved from DB each call)
        $this->assertFalse($subscriptionService->allowsVideo($this->prestataire->id));
        $this->assertFalse($subscriptionService->hasSearchBoost($this->prestataire->id));
    }

    // ── Video permission ──

    public function test_starter_cannot_upload_video(): void
    {
        $subscriptionService = app(\App\Services\SubscriptionService::class);
        $this->assertFalse($subscriptionService->allowsVideo($this->prestataire->id));
    }

    public function test_pro_allows_video(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $subscriptionService = app(\App\Services\SubscriptionService::class);
        $this->assertTrue($subscriptionService->allowsVideo($this->prestataire->id));
    }

    // ── Pro badge ──

    public function test_pro_has_badge(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $subscriptionService = app(\App\Services\SubscriptionService::class);
        $this->assertTrue($subscriptionService->hasProBadge($this->prestataire->id));
        $this->assertFalse($subscriptionService->hasSearchBoost($this->prestataire->id));
    }

    // ── Config is not hard-coded ──

    public function test_limits_are_configurable(): void
    {
        Config::set('subscription.plans.starter.limits.max_services', 99);

        $subscriptionService = app(\App\Services\SubscriptionService::class);
        $limits = $subscriptionService->getLimits('starter');

        $this->assertEquals(99, $limits['max_services']);
    }
}
