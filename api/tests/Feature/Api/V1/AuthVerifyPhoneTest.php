<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AuthVerifyPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_phone_verification(): void
    {
        $user = User::factory()->create([
            'phone' => '+33612345678',
            'phone_verified' => false,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/send-phone-verification');

        $response->assertOk()
            ->assertJson(['message' => 'Code de vérification envoyé.']);

        $user->refresh();
        $this->assertNotNull($user->phone_verification_code);
    }

    public function test_send_phone_verification_no_phone(): void
    {
        $user = User::factory()->create([
            'phone' => null,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/send-phone-verification');

        $response->assertUnprocessable()
            ->assertJson(['message' => 'Aucun numéro de téléphone configuré.']);
    }

    public function test_send_phone_verification_already_verified(): void
    {
        $user = User::factory()->create([
            'phone' => '+33612345678',
            'phone_verified' => true,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/send-phone-verification');

        $response->assertOk()
            ->assertJson(['message' => 'Téléphone déjà vérifié.']);
    }

    public function test_verify_phone_with_valid_code(): void
    {
        $user = User::factory()->create([
            'phone' => '+33612345678',
            'phone_verified' => false,
            'phone_verification_code' => '123456',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/verify-phone', ['code' => '123456']);

        $response->assertOk()
            ->assertJson(['message' => 'Téléphone vérifié avec succès.']);

        $user->refresh();
        $this->assertTrue($user->phone_verified);
        $this->assertNull($user->phone_verification_code);
    }

    public function test_verify_phone_rejects_invalid_code(): void
    {
        $user = User::factory()->create([
            'phone' => '+33612345678',
            'phone_verified' => false,
            'phone_verification_code' => '123456',
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/verify-phone', ['code' => '000000']);

        $response->assertUnprocessable()
            ->assertJson(['message' => 'Code invalide.']);
    }

    public function test_verify_phone_rejects_missing_code(): void
    {
        $user = User::factory()->create();

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/verify-phone', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }
}
