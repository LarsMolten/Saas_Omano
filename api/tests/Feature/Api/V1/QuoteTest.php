<?php

namespace Tests\Feature\Api\V1;

use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class QuoteTest extends TestCase
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

    public function test_client_can_create_quote(): void
    {
        $response = $this->postJson(
            '/api/v1/quotes',
            [
                'provider_id' => $this->prestataire->id,
                'service_type' => 'Traiteur mariage',
                'event_date' => now()->addDays(30)->format('Y-m-d'),
                'location' => 'Muscat',
                'budget' => 2000,
                'description' => 'Mariage de 150 personnes',
            ],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $response->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('service_type', 'Traiteur mariage');

        $this->assertDatabaseHas('quote_requests', [
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'status' => 'pending',
        ]);
    }

    public function test_create_quote_rejects_non_client(): void
    {
        $response = $this->postJson(
            '/api/v1/quotes',
            [
                'provider_id' => $this->client->id,
                'service_type' => 'Test',
            ],
            ['Authorization' => "Bearer {$this->prestataireToken}"]
        );

        $response->assertForbidden();
    }

    public function test_create_quote_requires_auth(): void
    {
        $this->postJson('/api/v1/quotes', [
            'provider_id' => $this->prestataire->id,
            'service_type' => 'Test',
        ])->assertUnauthorized();
    }

    public function test_create_quote_validates_required_fields(): void
    {
        $response = $this->postJson(
            '/api/v1/quotes',
            ['provider_id' => $this->prestataire->id],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['service_type']);
    }

    public function test_create_quote_rejects_non_prestataire(): void
    {
        $response = $this->postJson(
            '/api/v1/quotes',
            [
                'provider_id' => $this->client->id,
                'service_type' => 'Test',
            ],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Ce n\'est pas un prestataire.');
    }

    public function test_rate_limiting_rejects_after_10_quotes(): void
    {
        for ($i = 0; $i < 10; $i++) {
            QuoteRequest::create([
                'user_id' => $this->client->id,
                'provider_id' => $this->prestataire->id,
                'service_type' => "Service $i",
            ]);
        }

        $response = $this->postJson(
            '/api/v1/quotes',
            [
                'provider_id' => $this->prestataire->id,
                'service_type' => 'Over limit',
            ],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $response->assertStatus(429);
    }

    public function test_client_sees_only_own_quotes(): void
    {
        $other = User::factory()->create(['role' => 'client']);

        QuoteRequest::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'service_type' => 'Mine',
        ]);
        QuoteRequest::create([
            'user_id' => $other->id,
            'provider_id' => $this->prestataire->id,
            'service_type' => 'Theirs',
        ]);

        $response = $this->getJson('/api/v1/quotes', [
            'Authorization' => "Bearer {$this->clientToken}",
        ]);

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.service_type', 'Mine');
    }

    public function test_prestataire_sees_only_received_quotes(): void
    {
        $otherProvider = User::factory()->prestataire()->create();

        QuoteRequest::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'service_type' => 'For me',
        ]);
        QuoteRequest::create([
            'user_id' => $this->client->id,
            'provider_id' => $otherProvider->id,
            'service_type' => 'For other',
        ]);

        $response = $this->getJson('/api/v1/quotes', [
            'Authorization' => "Bearer {$this->prestataireToken}",
        ]);

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.service_type', 'For me');
    }

    public function test_prestataire_can_respond_to_quote(): void
    {
        $quote = QuoteRequest::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'service_type' => 'Traiteur',
            'status' => 'pending',
        ]);

        $response = $this->patchJson(
            "/api/v1/quotes/{$quote->id}/respond",
            [
                'status' => 'accepted',
                'provider_response' => 'Je suis disponible !',
            ],
            ['Authorization' => "Bearer {$this->prestataireToken}"]
        );

        $response->assertOk()
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('provider_response', 'Je suis disponible !');
    }

    public function test_prestataire_can_decline(): void
    {
        $quote = QuoteRequest::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'service_type' => 'DJ',
            'status' => 'pending',
        ]);

        $response = $this->patchJson(
            "/api/v1/quotes/{$quote->id}/respond",
            [
                'status' => 'declined',
                'provider_response' => 'Désolé, je ne suis pas disponible.',
            ],
            ['Authorization' => "Bearer {$this->prestataireToken}"]
        );

        $response->assertOk()
            ->assertJsonPath('status', 'declined');
    }

    public function test_respond_rejects_already_processed(): void
    {
        $quote = QuoteRequest::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'service_type' => 'Test',
            'status' => 'accepted',
            'provider_response' => 'Already done',
        ]);

        $response = $this->patchJson(
            "/api/v1/quotes/{$quote->id}/respond",
            [
                'status' => 'declined',
                'provider_response' => 'Changed my mind',
            ],
            ['Authorization' => "Bearer {$this->prestataireToken}"]
        );

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cette demande a déjà été traitée.');
    }

    public function test_respond_rejects_non_owner(): void
    {
        $other = User::factory()->prestataire()->create();
        $otherToken = JWTAuth::fromUser($other);

        $quote = QuoteRequest::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'service_type' => 'Not yours',
            'status' => 'pending',
        ]);

        $response = $this->patchJson(
            "/api/v1/quotes/{$quote->id}/respond",
            [
                'status' => 'accepted',
                'provider_response' => 'Hacked',
            ],
            ['Authorization' => "Bearer {$otherToken}"]
        );

        $response->assertForbidden();
    }

    public function test_respond_rejects_client(): void
    {
        $quote = QuoteRequest::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'service_type' => 'Test',
            'status' => 'pending',
        ]);

        $response = $this->patchJson(
            "/api/v1/quotes/{$quote->id}/respond",
            [
                'status' => 'accepted',
                'provider_response' => 'Client cant respond',
            ],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $response->assertForbidden();
    }

    public function test_full_cycle_create_then_respond(): void
    {
        $create = $this->postJson(
            '/api/v1/quotes',
            [
                'provider_id' => $this->prestataire->id,
                'service_type' => 'Photographe',
                'event_date' => now()->addDays(45)->format('Y-m-d'),
                'location' => 'Salalah',
                'budget' => 800,
                'description' => 'Photos de mariage',
            ],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $create->assertCreated();
        $quoteId = $create->json('id');

        $respond = $this->patchJson(
            "/api/v1/quotes/{$quoteId}/respond",
            [
                'status' => 'answered',
                'provider_response' => 'Merci pour votre demande. Voici mon offre : 900 OMR.',
            ],
            ['Authorization' => "Bearer {$this->prestataireToken}"]
        );

        $respond->assertOk()
            ->assertJsonPath('status', 'answered')
            ->assertJsonPath('provider_response', 'Merci pour votre demande. Voici mon offre : 900 OMR.');

        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteId,
            'status' => 'answered',
        ]);
    }
}
