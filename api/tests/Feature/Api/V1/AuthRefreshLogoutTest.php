<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AuthRefreshLogoutTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithToken(): User
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);
        $user->makeVisible('email_verification_token');
        return $user;
    }

    public function test_refresh_returns_new_token(): void
    {
        $user = $this->createUserWithToken();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);
    }

    public function test_refresh_rejects_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh');

        $response->assertUnauthorized();
    }

    public function test_refresh_rejects_invalid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/api/v1/auth/refresh');

        $response->assertUnauthorized();
    }

    public function test_logout_invalidates_token(): void
    {
        $user = $this->createUserWithToken();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJson(['message' => 'Déconnexion réussie.']);
    }

    public function test_logout_rejects_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized();
    }

    public function test_token_is_invalidated_after_logout(): void
    {
        $user = $this->createUserWithToken();
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/refresh');

        $response->assertStatus(401);
    }
}
