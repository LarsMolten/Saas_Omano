<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\SendNotification;
use App\Models\Notification;
use App\Models\QuoteRequest;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $prestataire;
    private string $clientToken;
    private string $prestataireToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->create(['role' => 'client']);
        $this->prestataire = User::factory()->prestataire()->create();
        $this->clientToken = JWTAuth::fromUser($this->client);
        $this->prestataireToken = JWTAuth::fromUser($this->prestataire);
    }

    public function test_quote_created_dispatches_notification_to_provider(): void
    {
        Bus::fake();

        $this->postJson(
            '/api/v1/quotes',
            [
                'provider_id' => $this->prestataire->id,
                'service_type' => 'Traiteur mariage',
                'event_date' => now()->addDays(30)->format('Y-m-d'),
            ],
            ['Authorization' => "Bearer {$this->clientToken}"]
        )->assertCreated();

        Bus::assertDispatched(SendNotification::class, function ($job) {
            return $job->userId === $this->prestataire->id
                && $job->type === 'quote.received'
                && $job->payload['client_name'] === $this->client->name
                && $job->payload['service_type'] === 'Traiteur mariage'
                && $job->emailSubject === 'Nouvelle demande de devis reçue';
        });
    }

    public function test_quote_responded_dispatches_notification_to_client(): void
    {
        Bus::fake();

        $quote = QuoteRequest::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'service_type' => 'DJ',
            'status' => 'pending',
        ]);

        $this->patchJson(
            "/api/v1/quotes/{$quote->id}/respond",
            [
                'status' => 'accepted',
                'provider_response' => 'Disponible !',
            ],
            ['Authorization' => "Bearer {$this->prestataireToken}"]
        )->assertOk();

        Bus::assertDispatched(SendNotification::class, function ($job) {
            return $job->userId === $this->client->id
                && $job->type === 'quote.responded'
                && $job->payload['status'] === 'accepted'
                && $job->payload['provider_name'] === $this->prestataire->name
                && $job->emailSubject === 'Réponse à votre demande de devis';
        });
    }

    public function test_review_created_dispatches_notification_to_provider(): void
    {
        Bus::fake();

        $this->postJson(
            '/api/v1/reviews',
            [
                'provider_id' => $this->prestataire->id,
                'rating' => 5,
                'comment' => 'Excellent !',
            ],
            ['Authorization' => "Bearer {$this->clientToken}"]
        )->assertCreated();

        Bus::assertDispatched(SendNotification::class, function ($job) {
            return $job->userId === $this->prestataire->id
                && $job->type === 'review.received'
                && $job->payload['client_name'] === $this->client->name
                && $job->payload['rating'] === 5
                && $job->emailSubject === 'Nouvel avis reçu';
        });
    }

    public function test_review_reported_dispatches_notification_to_admins(): void
    {
        Bus::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $adminToken = JWTAuth::fromUser($admin);

        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 5,
            'status' => 'published',
        ]);

        $this->postJson(
            "/api/v1/reviews/{$review->id}/report",
            [],
            ['Authorization' => "Bearer {$adminToken}"]
        )->assertOk();

        Bus::assertDispatched(SendNotification::class, function ($job) use ($admin) {
            return $job->userId === $admin->id
                && $job->type === 'review.reported'
                && $job->payload['reporter_name'] !== null
                && $job->emailSubject === 'Avis signalé';
        });
    }

    public function test_notification_created_in_database(): void
    {
        $notification = Notification::create([
            'user_id' => $this->client->id,
            'type' => 'quote.responded',
            'payload' => ['quote_id' => 1, 'status' => 'accepted'],
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->client->id,
            'type' => 'quote.responded',
        ]);
    }

    public function test_index_returns_notifications(): void
    {
        Notification::create([
            'user_id' => $this->client->id,
            'type' => 'quote.responded',
            'payload' => ['quote_id' => 1],
            'created_at' => now(),
        ]);

        Notification::create([
            'user_id' => $this->client->id,
            'type' => 'review.received',
            'payload' => ['review_id' => 1],
            'read_at' => now(),
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/notifications', [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_index_only_returns_own_notifications(): void
    {
        Notification::create([
            'user_id' => $this->client->id,
            'type' => 'quote.responded',
            'payload' => ['quote_id' => 1],
            'created_at' => now(),
        ]);

        Notification::create([
            'user_id' => $this->prestataire->id,
            'type' => 'review.received',
            'payload' => ['review_id' => 1],
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/notifications', [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('unread_count', 1);
    }

    public function test_mark_as_read(): void
    {
        $notification = Notification::create([
            'user_id' => $this->client->id,
            'type' => 'quote.responded',
            'payload' => ['quote_id' => 1],
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);

        $response = $this->patchJson("/api/v1/notifications/{$notification->id}/read", [], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
        ]);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_mark_as_read_only_own_notification(): void
    {
        $notification = Notification::create([
            'user_id' => $this->prestataire->id,
            'type' => 'review.received',
            'payload' => ['review_id' => 1],
            'created_at' => now(),
        ]);

        $response = $this->patchJson("/api/v1/notifications/{$notification->id}/read", [], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertNotFound();
    }

    public function test_read_all(): void
    {
        Notification::create([
            'user_id' => $this->client->id,
            'type' => 'quote.responded',
            'payload' => ['quote_id' => 1],
            'created_at' => now(),
        ]);

        Notification::create([
            'user_id' => $this->client->id,
            'type' => 'review.received',
            'payload' => ['review_id' => 1],
            'created_at' => now(),
        ]);

        $response = $this->patchJson('/api/v1/notifications/read-all', [], [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->client->id,
        ]);

        $this->assertEquals(0, Notification::where('user_id', $this->client->id)->whereNull('read_at')->count());
    }

    public function test_read_all_only_own_notifications(): void
    {
        $myNotification = Notification::create([
            'user_id' => $this->client->id,
            'type' => 'quote.responded',
            'payload' => ['quote_id' => 1],
            'created_at' => now(),
        ]);

        $otherNotification = Notification::create([
            'user_id' => $this->prestataire->id,
            'type' => 'review.received',
            'payload' => ['review_id' => 1],
            'created_at' => now(),
        ]);

        $this->patchJson('/api/v1/notifications/read-all', [], [
            'Authorization' => "Bearer {$this->clientToken}",
        ])->assertOk();

        $myNotification->refresh();
        $otherNotification->refresh();

        $this->assertNotNull($myNotification->read_at);
        $this->assertNull($otherNotification->read_at);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
    }

    public function test_read_requires_auth(): void
    {
        $this->patchJson('/api/v1/notifications/1/read')->assertUnauthorized();
    }

    public function test_read_all_requires_auth(): void
    {
        $this->patchJson('/api/v1/notifications/read-all')->assertUnauthorized();
    }

    public function test_notification_job_uses_database_queue(): void
    {
        $job = new SendNotification(
            userId: $this->client->id,
            type: 'test',
            payload: ['key' => 'value'],
        );

        $this->assertEquals('database', $job->queue);
    }
}
