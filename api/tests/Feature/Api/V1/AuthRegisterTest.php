<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRegisterTest extends TestCase
{
    use RefreshDatabase;

    private array $validData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'client',
    ];

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validData);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'role'],
                'access_token',
                'token_type',
                'expires_in',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'client',
        ]);
    }

    public function test_register_with_prestataire_role(): void
    {
        $data = [...$this->validData, 'role' => 'prestataire'];

        $response = $this->postJson('/api/v1/auth/register', $data);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['role' => 'prestataire']);
    }

    public function test_register_rejects_invalid_role(): void
    {
        $data = [...$this->validData, 'role' => 'admin'];

        $response = $this->postJson('/api/v1/auth/register', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/auth/register', $this->validData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_register_rejects_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    public function test_register_rejects_short_password(): void
    {
        $data = [...$this->validData, 'password' => '123', 'password_confirmation' => '123'];

        $response = $this->postJson('/api/v1/auth/register', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_register_rejects_unconfirmed_password(): void
    {
        $data = [...$this->validData];
        unset($data['password_confirmation']);

        $response = $this->postJson('/api/v1/auth/register', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_register_hashes_password_with_argon2id(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validData);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertStringStartsWith('$argon2id$', $user->password);
    }

    public function test_register_sets_email_verification_token(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validData);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user->email_verification_token);
    }
}
