<?php

namespace Tests\Feature\Api\V1;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ReviewTest extends TestCase
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

    public function test_client_can_create_review(): void
    {
        $response = $this->postJson(
            '/api/v1/reviews',
            [
                'provider_id' => $this->prestataire->id,
                'rating' => 5,
                'comment' => 'Excellent prestataire, je recommande vivement !',
            ],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $response->assertCreated()
            ->assertJsonPath('rating', 5)
            ->assertJsonPath('status', 'published');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 5,
            'status' => 'published',
        ]);
    }

    public function test_create_review_rejects_non_client(): void
    {
        $response = $this->postJson(
            '/api/v1/reviews',
            [
                'provider_id' => $this->client->id,
                'rating' => 4,
            ],
            ['Authorization' => "Bearer {$this->prestataireToken}"]
        );

        $response->assertForbidden();
    }

    public function test_create_review_requires_auth(): void
    {
        $this->postJson('/api/v1/reviews', [
            'provider_id' => $this->prestataire->id,
            'rating' => 4,
        ])->assertUnauthorized();
    }

    public function test_create_review_validates_required_fields(): void
    {
        $response = $this->postJson(
            '/api/v1/reviews',
            ['provider_id' => $this->prestataire->id],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_create_review_validates_rating_range(): void
    {
        $response = $this->postJson(
            '/api/v1/reviews',
            [
                'provider_id' => $this->prestataire->id,
                'rating' => 6,
            ],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_create_review_rejects_non_prestataire(): void
    {
        $response = $this->postJson(
            '/api/v1/reviews',
            [
                'provider_id' => $this->client->id,
                'rating' => 5,
            ],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Ce n\'est pas un prestataire.');
    }

    public function test_client_can_only_review_once_per_provider(): void
    {
        Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 4,
            'status' => 'published',
        ]);

        $response = $this->postJson(
            '/api/v1/reviews',
            [
                'provider_id' => $this->prestataire->id,
                'rating' => 5,
                'comment' => 'Second review attempt',
            ],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Vous avez déjà laissé un avis pour ce prestataire.');
    }

    public function test_average_rating_recalled_on_create(): void
    {
        $this->prestataire->update(['average_rating' => 0, 'rating_count' => 0]);

        $this->postJson(
            '/api/v1/reviews',
            [
                'provider_id' => $this->prestataire->id,
                'rating' => 4,
            ],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $this->prestataire->refresh();
        $this->assertEquals(4.0, (float) $this->prestataire->average_rating);
        $this->assertEquals(1, $this->prestataire->rating_count);
    }

    public function test_average_rating_recalled_with_multiple_reviews(): void
    {
        $otherClient = User::factory()->create(['role' => 'client']);

        $this->postJson(
            '/api/v1/reviews',
            ['provider_id' => $this->prestataire->id, 'rating' => 4],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $this->postJson(
            '/api/v1/reviews',
            ['provider_id' => $this->prestataire->id, 'rating' => 2],
            ['Authorization' => "Bearer " . JWTAuth::fromUser($otherClient)]
        );

        $this->prestataire->refresh();
        $this->assertEquals(3.0, (float) $this->prestataire->average_rating);
        $this->assertEquals(2, $this->prestataire->rating_count);
    }

    public function test_average_rating_only_counts_published(): void
    {
        $otherClient = User::factory()->create(['role' => 'client']);

        Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 5,
            'status' => 'published',
        ]);

        Review::create([
            'user_id' => $otherClient->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 1,
            'status' => 'reported',
        ]);

        $this->prestataire->refresh();
        $this->assertEquals(5.0, (float) $this->prestataire->average_rating);
        $this->assertEquals(1, $this->prestataire->rating_count);
    }

    public function test_average_rating_updated_on_status_change(): void
    {
        $otherClient = User::factory()->create(['role' => 'client']);

        $review1 = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 5,
            'status' => 'published',
        ]);

        $review2 = Review::create([
            'user_id' => $otherClient->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 1,
            'status' => 'published',
        ]);

        $this->prestataire->refresh();
        $this->assertEquals(3.0, (float) $this->prestataire->average_rating);
        $this->assertEquals(2, $this->prestataire->rating_count);

        $review2->update(['status' => 'reported']);

        $this->prestataire->refresh();
        $this->assertEquals(5.0, (float) $this->prestataire->average_rating);
        $this->assertEquals(1, $this->prestataire->rating_count);
    }

    public function test_average_rating_updated_on_delete(): void
    {
        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 3,
            'status' => 'published',
        ]);

        $this->prestataire->refresh();
        $this->assertEquals(3.0, (float) $this->prestataire->average_rating);
        $this->assertEquals(1, $this->prestataire->rating_count);

        $review->delete();

        $this->prestataire->refresh();
        $this->assertEquals(0, (float) $this->prestataire->average_rating);
        $this->assertEquals(0, $this->prestataire->rating_count);
    }

    public function test_author_can_edit_within_48h(): void
    {
        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 3,
            'comment' => 'Original comment',
            'status' => 'published',
        ]);

        $response = $this->patchJson(
            "/api/v1/reviews/{$review->id}",
            [
                'rating' => 5,
                'comment' => 'Updated comment',
            ],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $response->assertOk()
            ->assertJsonPath('rating', 5)
            ->assertJsonPath('comment', 'Updated comment');
    }

    public function test_author_cannot_edit_after_48h(): void
    {
        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 3,
            'status' => 'published',
        ]);

        DB::table('reviews')
            ->where('id', $review->id)
            ->update(['created_at' => now()->subHours(49)]);

        $response = $this->patchJson(
            "/api/v1/reviews/{$review->id}",
            ['rating' => 5],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Vous ne pouvez plus modifier cet avis (délai de 48h dépassé).');
    }

    public function test_non_author_cannot_edit_review(): void
    {
        $otherClient = User::factory()->create(['role' => 'client']);
        $otherToken = JWTAuth::fromUser($otherClient);

        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 3,
            'status' => 'published',
        ]);

        $response = $this->patchJson(
            "/api/v1/reviews/{$review->id}",
            ['rating' => 1],
            ['Authorization' => "Bearer {$otherToken}"]
        );

        $response->assertForbidden();
    }

    public function test_prestataire_cannot_edit_review(): void
    {
        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 3,
            'status' => 'published',
        ]);

        $response = $this->patchJson(
            "/api/v1/reviews/{$review->id}",
            ['rating' => 5],
            ['Authorization' => "Bearer {$this->prestataireToken}"]
        );

        $response->assertForbidden();
    }

    public function test_any_user_can_report_review(): void
    {
        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 3,
            'status' => 'published',
        ]);

        $response = $this->postJson(
            "/api/v1/reviews/{$review->id}/report",
            [],
            ['Authorization' => "Bearer {$this->prestataireToken}"]
        );

        $response->assertOk()
            ->assertJsonPath('message', 'Avis signalé.');

        $review->refresh();
        $this->assertEquals('reported', $review->status);
    }

    public function test_cannot_report_own_review(): void
    {
        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 3,
            'status' => 'published',
        ]);

        $response = $this->postJson(
            "/api/v1/reviews/{$review->id}/report",
            [],
            ['Authorization' => "Bearer {$this->clientToken}"]
        );

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Vous ne pouvez pas signaler votre propre avis.');
    }

    public function test_cannot_report_already_reported_review(): void
    {
        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 3,
            'status' => 'reported',
        ]);

        $response = $this->postJson(
            "/api/v1/reviews/{$review->id}/report",
            [],
            ['Authorization' => "Bearer {$this->prestataireToken}"]
        );

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cet avis a déjà été signalé.');
    }

    public function test_report_requires_auth(): void
    {
        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 3,
            'status' => 'published',
        ]);

        $this->postJson("/api/v1/reviews/{$review->id}/report")
            ->assertUnauthorized();
    }

    public function test_provider_reviews_public_endpoint(): void
    {
        $otherClient = User::factory()->create(['role' => 'client']);

        Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 5,
            'comment' => 'Top prestataire !',
            'status' => 'published',
        ]);

        Review::create([
            'user_id' => $otherClient->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 3,
            'comment' => 'Comment should not appear',
            'status' => 'reported',
        ]);

        $response = $this->getJson("/api/v1/providers/{$this->prestataire->id}/reviews");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.comment', 'Top prestataire !');
    }

    public function test_provider_reviews_is_public(): void
    {
        Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 4,
            'status' => 'published',
        ]);

        $response = $this->getJson("/api/v1/providers/{$this->prestataire->id}/reviews");

        $response->assertOk();
    }

    public function test_provider_reviews_includes_user_name(): void
    {
        Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 5,
            'comment' => 'Great!',
            'status' => 'published',
        ]);

        $response = $this->getJson("/api/v1/providers/{$this->prestataire->id}/reviews");

        $response->assertOk()
            ->assertJsonPath('data.0.user.name', $this->client->name);
    }

    public function test_provider_reviews_empty_for_no_reviews(): void
    {
        $response = $this->getJson("/api/v1/providers/{$this->prestataire->id}/reviews");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
