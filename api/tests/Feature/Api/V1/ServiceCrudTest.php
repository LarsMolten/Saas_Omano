<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Service;
use App\Models\ServiceOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ServiceCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $prestataire;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prestataire = User::factory()->create([
            'role' => 'prestataire',
        ]);
        $this->token = JWTAuth::fromUser($this->prestataire);
    }

    public function test_list_services_public(): void
    {
        Service::factory()->count(3)->create([
            'provider_id' => $this->prestataire->id,
        ]);

        $response = $this->getJson("/api/v1/providers/{$this->prestataire->id}/services");

        $response->assertOk()
            ->assertJsonCount(3);
    }

    public function test_list_services_includes_options(): void
    {
        $service = Service::factory()->create([
            'provider_id' => $this->prestataire->id,
        ]);
        ServiceOption::factory()->count(2)->create([
            'service_id' => $service->id,
        ]);

        $response = $this->getJson("/api/v1/providers/{$this->prestataire->id}/services");

        $response->assertOk()
            ->assertJsonPath('0.options', fn (array $options) => count($options) === 2);
    }

    public function test_list_services_ordered_by_position(): void
    {
        Service::factory()->create([
            'provider_id' => $this->prestataire->id,
            'position' => 2,
        ]);
        Service::factory()->create([
            'provider_id' => $this->prestataire->id,
            'position' => 1,
        ]);

        $response = $this->getJson("/api/v1/providers/{$this->prestataire->id}/services");

        $response->assertOk();
        $data = $response->json();
        $this->assertEquals(1, $data[0]['position']);
        $this->assertEquals(2, $data[1]['position']);
    }

    public function test_list_services_404_for_nonexistent_provider(): void
    {
        $response = $this->getJson('/api/v1/providers/99999/services');

        $response->assertNotFound();
    }

    public function test_list_services_404_for_client(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->getJson("/api/v1/providers/{$client->id}/services");

        $response->assertNotFound();
    }

    public function test_create_service(): void
    {
        $data = [
            'title' => 'Coiffure homme',
            'description' => 'Coupe classique',
            'price' => 25.00,
            'price_type' => 'fixed',
            'options' => [
                ['label' => 'Barbe', 'extra_price' => 10.00],
            ],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/providers/{$this->prestataire->id}/services", $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'price',
                'price_type',
                'position',
                'options' => [['id', 'label', 'extra_price']],
            ]);

        $this->assertDatabaseHas('services', [
            'provider_id' => $this->prestataire->id,
            'title' => 'Coiffure homme',
        ]);
    }

    public function test_create_service_rejects_unauthenticated(): void
    {
        $response = $this->postJson("/api/v1/providers/{$this->prestataire->id}/services", [
            'title' => 'Test',
            'price_type' => 'fixed',
        ]);

        $response->assertUnauthorized();
    }

    public function test_create_service_rejects_other_provider(): void
    {
        $other = User::factory()->create(['role' => 'prestataire']);
        $otherToken = JWTAuth::fromUser($other);

        $response = $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->postJson("/api/v1/providers/{$this->prestataire->id}/services", [
                'title' => 'Test',
                'price_type' => 'fixed',
            ]);

        $response->assertForbidden();
    }

    public function test_create_service_rejects_client(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $clientToken = JWTAuth::fromUser($client);

        $response = $this->withHeader('Authorization', "Bearer {$clientToken}")
            ->postJson("/api/v1/providers/{$this->prestataire->id}/services", [
                'title' => 'Test',
                'price_type' => 'fixed',
            ]);

        $response->assertForbidden();
    }

    public function test_create_service_rejects_negative_price(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/providers/{$this->prestataire->id}/services", [
                'title' => 'Test',
                'price' => -10,
                'price_type' => 'fixed',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('price');
    }

    public function test_create_service_rejects_missing_title(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/providers/{$this->prestataire->id}/services", [
                'price' => 10,
                'price_type' => 'fixed',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    }

    public function test_create_service_rejects_invalid_price_type(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/providers/{$this->prestataire->id}/services", [
                'title' => 'Test',
                'price_type' => 'invalid',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('price_type');
    }

    public function test_update_service(): void
    {
        $service = Service::factory()->create([
            'provider_id' => $this->prestataire->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/v1/services/{$service->id}", [
                'title' => 'Nouveau titre',
                'price' => 50.00,
            ]);

        $response->assertOk()
            ->assertJsonPath('title', 'Nouveau titre')
            ->assertJsonPath('price', '50.00');
    }

    public function test_update_service_rejects_other_provider(): void
    {
        $service = Service::factory()->create([
            'provider_id' => $this->prestataire->id,
        ]);

        $other = User::factory()->create(['role' => 'prestataire']);
        $otherToken = JWTAuth::fromUser($other);

        $response = $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->patchJson("/api/v1/services/{$service->id}", [
                'title' => 'Hack',
            ]);

        $response->assertForbidden();
    }

    public function test_delete_service(): void
    {
        $service = Service::factory()->create([
            'provider_id' => $this->prestataire->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/services/{$service->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Service supprimé.']);

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_delete_service_cascades_options(): void
    {
        $service = Service::factory()->create([
            'provider_id' => $this->prestataire->id,
        ]);
        $optionIds = ServiceOption::factory()->count(2)->create([
            'service_id' => $service->id,
        ])->pluck('id');

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/services/{$service->id}");

        foreach ($optionIds as $optionId) {
            $this->assertDatabaseMissing('service_options', ['id' => $optionId]);
        }
    }

    public function test_delete_service_rejects_other_provider(): void
    {
        $service = Service::factory()->create([
            'provider_id' => $this->prestataire->id,
        ]);

        $other = User::factory()->create(['role' => 'prestataire']);
        $otherToken = JWTAuth::fromUser($other);

        $response = $this->withHeader('Authorization', "Bearer {$otherToken}")
            ->deleteJson("/api/v1/services/{$service->id}");

        $response->assertForbidden();
    }
}
