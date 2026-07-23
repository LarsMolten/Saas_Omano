<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AuthVerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_email_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email_verification_token' => 'valid-token-123',
        ]);

        $response = $this->postJson('/api/v1/auth/verify-email', [
            'token' => 'valid-token-123',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Email vérifié avec succès.']);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->email_verification_token);
    }

    public function test_verify_email_rejects_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-email', [
            'token' => 'invalid-token',
        ]);

        $response->assertUnprocessable()
            ->assertJson(['message' => 'Token invalide.']);
    }

    public function test_verify_email_rejects_missing_token(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-email', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_send_email_verification(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/send-email-verification');

        $response->assertOk()
            ->assertJson(['message' => 'Lien de vérification envoyé.']);
    }

    public function test_send_email_verification_already_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/send-email-verification');

        $response->assertOk()
            ->assertJson(['message' => 'Email déjà vérifié.']);
    }
}
