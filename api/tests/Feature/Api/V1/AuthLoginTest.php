<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'email', 'role'],
                'access_token',
                'token_type',
                'expires_in',
            ]);
    }

    public function test_login_rejects_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Identifiants incorrects.']);
    }

    public function test_login_rejects_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_rejects_missing_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_returns_user_role(): void
    {
        User::factory()->create([
            'email' => 'client@example.com',
            'password' => 'password123',
            'role' => 'client',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'client@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', 'client');
    }

    public function test_me_returns_current_user_info(): void
    {
        $user = User::factory()->create([
            'email' => 'me@example.com',
            'password' => 'password123',
            'role' => 'prestataire',
        ]);

        $token = \Tymon\JWTAuth\Facades\JWTAuth::attempt([
            'email' => 'me@example.com',
            'password' => 'password123',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonStructure([
                'id', 'name', 'email', 'role', 'status',
                'email_verified_at', 'slug',
            ])
            ->assertJsonPath('role', 'prestataire')
            ->assertJsonPath('email', 'me@example.com');
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    public function test_me_returns_client_role(): void
    {
        $user = User::factory()->create([
            'email' => 'client-me@example.com',
            'password' => 'password123',
            'role' => 'client',
        ]);

        $token = \Tymon\JWTAuth\Facades\JWTAuth::attempt([
            'email' => 'client-me@example.com',
            'password' => 'password123',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('role', 'client');
    }
}
