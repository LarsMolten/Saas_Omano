<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class CheckRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_route_rejects_client(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users');

        $response->assertForbidden()
            ->assertJson(['message' => 'Accès non autorisé.']);
    }

    public function test_admin_route_allows_admin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/users');

        $response->assertOk();
    }

    public function test_protected_route_rejects_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized();
    }
}
