<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\SendNotification;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $prestataire;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('payments.gateway', 'fake');
        Config::set('payments.fake_webhook_secret', 'test-secret');

        $this->prestataire = User::factory()->prestataire()->create();
        $this->token = JWTAuth::fromUser($this->prestataire);
    }

    // ── Initiate ──

    public function test_initiate_creates_pending_payment(): void
    {
        $subscription = Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'starter',
            'period' => 'monthly',
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $response = $this->postJson('/api/v1/payments/initiate', [
            'subscription_id' => $subscription->id,
            'operator' => 'mvola',
        ], ['Authorization' => "Bearer {$this->token}"]);

        $response->assertCreated()
            ->assertJsonPath('payment.status', 'pending')
            ->assertJsonPath('payment.operator', 'mvola')
            ->assertJsonPath('payment.amount', '19.90');

        $this->assertDatabaseHas('payments', [
            'subscription_id' => $subscription->id,
            'operator' => 'mvola',
            'status' => 'pending',
        ]);
    }

    public function test_initiate_rejects_client(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $clientToken = JWTAuth::fromUser($client);

        $subscription = Subscription::create([
            'provider_id' => $client->id,
            'plan' => 'starter',
            'period' => 'monthly',
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->postJson('/api/v1/payments/initiate', [
            'subscription_id' => $subscription->id,
            'operator' => 'mvola',
        ], ['Authorization' => "Bearer {$clientToken}"])->assertForbidden();
    }

    public function test_initiate_rejects_already_active(): void
    {
        $subscription = Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->postJson('/api/v1/payments/initiate', [
            'subscription_id' => $subscription->id,
            'operator' => 'mvola',
        ], ['Authorization' => "Bearer {$this->token}"])->assertStatus(409);
    }

    public function test_initiate_rejects_other_users_subscription(): void
    {
        $other = User::factory()->prestataire()->create();

        $subscription = Subscription::create([
            'provider_id' => $other->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->postJson('/api/v1/payments/initiate', [
            'subscription_id' => $subscription->id,
            'operator' => 'orange_money',
        ], ['Authorization' => "Bearer {$this->token}"])->assertForbidden();
    }

    public function test_initiate_requires_auth(): void
    {
        $this->postJson('/api/v1/payments/initiate', [
            'subscription_id' => 1,
            'operator' => 'mvola',
        ])->assertUnauthorized();
    }

    public function test_initiate_validates_operator(): void
    {
        $subscription = Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->postJson('/api/v1/payments/initiate', [
            'subscription_id' => $subscription->id,
            'operator' => 'bitcoin',
        ], ['Authorization' => "Bearer {$this->token}"])->assertUnprocessable();
    }

    public function test_initiate_sets_correct_amount_for_plan(): void
    {
        $subscription = Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'premium',
            'period' => 'yearly',
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);

        $this->postJson('/api/v1/payments/initiate', [
            'subscription_id' => $subscription->id,
            'operator' => 'airtel_money',
        ], ['Authorization' => "Bearer {$this->token}"])
            ->assertCreated()
            ->assertJsonPath('payment.amount', '999.00');
    }

    // ── Webhook ──

    public function test_webhook_activates_subscription_on_success(): void
    {
        Bus::fake();

        $subscription = Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $payment = Payment::create([
            'subscription_id' => $subscription->id,
            'operator' => 'mvola',
            'amount' => 49.90,
            'status' => 'pending',
            'external_reference' => 'MVOLA-12345',
        ]);

        $response = $this->postJson('/api/v1/payments/webhook/mvola', [
            'external_reference' => 'MVOLA-12345',
            'status' => 'success',
            'secret' => 'test-secret',
        ]);

        $response->assertOk();

        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
        $this->assertTrue($subscription->ends_at->isFuture());

        $payment->refresh();
        $this->assertEquals('success', $payment->status);
        $this->assertNotNull($payment->webhook_payload);

        Bus::assertDispatched(SendNotification::class, function ($job) {
            return $job->userId === $this->prestataire->id
                && $job->type === 'payment.success'
                && $job->emailSubject === 'Paiement confirmé';
        });
    }

    public function test_webhook_sets_correct_ends_at_period(): void
    {
        Bus::fake();

        $subscription = Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'premium',
            'period' => 'yearly',
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        Payment::create([
            'subscription_id' => $subscription->id,
            'operator' => 'orange_money',
            'amount' => 999.00,
            'status' => 'pending',
            'external_reference' => 'OM-99999',
        ]);

        $this->postJson('/api/v1/payments/webhook/orange_money', [
            'external_reference' => 'OM-99999',
            'status' => 'success',
            'secret' => 'test-secret',
        ])->assertOk();

        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
        $this->assertTrue($subscription->ends_at->isAfter(now()->addMonths(11)));
    }

    public function test_webhook_idempotent_on_duplicate_event(): void
    {
        Bus::fake();

        $subscription = Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        Payment::create([
            'subscription_id' => $subscription->id,
            'operator' => 'mvola',
            'amount' => 49.90,
            'status' => 'success',
            'external_reference' => 'MVOLA-12345',
        ]);

        $response = $this->postJson('/api/v1/payments/webhook/mvola', [
            'external_reference' => 'MVOLA-12345',
            'status' => 'success',
            'secret' => 'test-secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Événement déjà traité.');

        $subscription->refresh();
        $this->assertEquals('pending', $subscription->status);

        Bus::assertNotDispatched(SendNotification::class);
    }

    public function test_webhook_failure_does_not_activate_subscription(): void
    {
        $subscription = Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $payment = Payment::create([
            'subscription_id' => $subscription->id,
            'operator' => 'orange_money',
            'amount' => 49.90,
            'status' => 'pending',
            'external_reference' => 'OM-67890',
        ]);

        $response = $this->postJson('/api/v1/payments/webhook/orange_money', [
            'external_reference' => 'OM-67890',
            'status' => 'failed',
            'secret' => 'test-secret',
        ]);

        $response->assertOk();

        $subscription->refresh();
        $this->assertEquals('pending', $subscription->status);

        $payment->refresh();
        $this->assertEquals('failed', $payment->status);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/api/v1/payments/webhook/mvola', [
            'external_reference' => 'MVOLA-12345',
            'status' => 'success',
            'secret' => 'wrong-secret',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'Signature invalide.');
    }

    public function test_webhook_rejects_missing_payload_fields(): void
    {
        $response = $this->postJson('/api/v1/payments/webhook/mvola', [
            'secret' => 'test-secret',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Payload invalide.');
    }

    public function test_webhook_rejects_unknown_external_reference(): void
    {
        $response = $this->postJson('/api/v1/payments/webhook/mvola', [
            'external_reference' => 'UNKNOWN-REF',
            'status' => 'success',
            'secret' => 'test-secret',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Paiement introuvable.');
    }

    public function test_webhook_is_public_no_auth_needed(): void
    {
        $subscription = Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        Payment::create([
            'subscription_id' => $subscription->id,
            'operator' => 'mvola',
            'amount' => 49.90,
            'status' => 'pending',
            'external_reference' => 'MVOLA-PUBLIC',
        ]);

        $this->postJson('/api/v1/payments/webhook/mvola', [
            'external_reference' => 'MVOLA-PUBLIC',
            'status' => 'success',
            'secret' => 'test-secret',
        ])->assertOk();
    }

    // ── Status ──

    public function test_status_returns_payment_info(): void
    {
        $subscription = Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $payment = Payment::create([
            'subscription_id' => $subscription->id,
            'operator' => 'mvola',
            'amount' => 49.90,
            'status' => 'pending',
            'external_reference' => 'MVOLA-12345',
        ]);

        $response = $this->getJson("/api/v1/payments/{$payment->id}/status", [
            'Authorization' => "Bearer {$this->token}",
        ]);

        $response->assertOk()
            ->assertJsonPath('payment_id', $payment->id)
            ->assertJsonPath('status', 'pending');
    }

    public function test_status_rejects_other_users_payment(): void
    {
        $other = User::factory()->prestataire()->create();

        $subscription = Subscription::create([
            'provider_id' => $other->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'pending',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $payment = Payment::create([
            'subscription_id' => $subscription->id,
            'operator' => 'mvola',
            'amount' => 49.90,
            'status' => 'pending',
            'external_reference' => 'OTHER-REF',
        ]);

        $this->getJson("/api/v1/payments/{$payment->id}/status", [
            'Authorization' => "Bearer {$this->token}",
        ])->assertForbidden();
    }

    public function test_status_requires_auth(): void
    {
        $this->getJson('/api/v1/payments/1/status')->assertUnauthorized();
    }

    // ── Config ──

    public function test_gateway_is_configurable(): void
    {
        Config::set('payments.gateway', 'fake');

        $gateway = Config::get('payments.gateway');
        $this->assertEquals('fake', $gateway);
    }

    public function test_operators_list(): void
    {
        $gateways = Config::get('payments.gateways');

        $this->assertArrayHasKey('mvola', $gateways);
        $this->assertArrayHasKey('orange_money', $gateways);
        $this->assertArrayHasKey('airtel_money', $gateways);
        $this->assertArrayHasKey('fake', $gateways);
    }
}
