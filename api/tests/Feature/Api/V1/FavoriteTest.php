<?php

namespace Tests\Feature\Api\V1;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_favorite(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->prestataire()->create();
        $token = JWTAuth::fromUser($client);

        $response = $this->postJson(
            '/api/v1/favorites',
            ['provider_id' => $provider->id],
            ['Authorization' => "Bearer {$token}"]
        );

        $response->assertCreated()
            ->assertJsonPath('provider.id', $provider->id);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $client->id,
            'provider_id' => $provider->id,
        ]);
    }

    public function test_add_favorite_rejects_duplicate(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->prestataire()->create();
        $token = JWTAuth::fromUser($client);

        Favorite::create(['user_id' => $client->id, 'provider_id' => $provider->id]);

        $response = $this->postJson(
            '/api/v1/favorites',
            ['provider_id' => $provider->id],
            ['Authorization' => "Bearer {$token}"]
        );

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Déjà en favori.');
    }

    public function test_add_favorite_rejects_non_client(): void
    {
        $prestataire = User::factory()->prestataire()->create();
        $other = User::factory()->prestataire()->create();
        $token = JWTAuth::fromUser($prestataire);

        $response = $this->postJson(
            '/api/v1/favorites',
            ['provider_id' => $other->id],
            ['Authorization' => "Bearer {$token}"]
        );

        $response->assertForbidden();
    }

    public function test_add_favorite_rejects_favoriting_self(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $token = JWTAuth::fromUser($client);

        $response = $this->postJson(
            '/api/v1/favorites',
            ['provider_id' => $client->id],
            ['Authorization' => "Bearer {$token}"]
        );

        $response->assertUnprocessable();
    }

    public function test_add_favorite_requires_auth(): void
    {
        $provider = User::factory()->prestataire()->create();

        $this->postJson('/api/v1/favorites', ['provider_id' => $provider->id])
            ->assertUnauthorized();
    }

    public function test_list_favorites(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $p1 = User::factory()->prestataire()->create(['name' => 'Alpha']);
        $p2 = User::factory()->prestataire()->create(['name' => 'Beta']);
        $token = JWTAuth::fromUser($client);

        Favorite::create(['user_id' => $client->id, 'provider_id' => $p1->id]);
        Favorite::create(['user_id' => $client->id, 'provider_id' => $p2->id]);

        $response = $this->getJson('/api/v1/favorites', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonCount(2);
    }

    public function test_list_favorites_only_own(): void
    {
        $client1 = User::factory()->create(['role' => 'client']);
        $client2 = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->prestataire()->create();

        Favorite::create(['user_id' => $client1->id, 'provider_id' => $provider->id]);
        $token = JWTAuth::fromUser($client2);

        $response = $this->getJson('/api/v1/favorites', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJsonCount(0);
    }

    public function test_remove_favorite(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->prestataire()->create();
        $token = JWTAuth::fromUser($client);

        Favorite::create(['user_id' => $client->id, 'provider_id' => $provider->id]);

        $response = $this->deleteJson("/api/v1/favorites/{$provider->id}", [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $client->id,
            'provider_id' => $provider->id,
        ]);
    }

    public function test_remove_favorite_not_found(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $provider = User::factory()->prestataire()->create();
        $token = JWTAuth::fromUser($client);

        $response = $this->deleteJson("/api/v1/favorites/{$provider->id}", [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertNotFound();
    }

    public function test_remove_favorite_rejects_non_client(): void
    {
        $prestataire = User::factory()->prestataire()->create();
        $other = User::factory()->prestataire()->create();
        $token = JWTAuth::fromUser($prestataire);

        $response = $this->deleteJson("/api/v1/favorites/{$other->id}", [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertForbidden();
    }
}
