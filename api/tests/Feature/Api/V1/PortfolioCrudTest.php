<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\ProcessPortfolioImage;
use App\Models\PortfolioItem;
use App\Models\PortfolioMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class PortfolioCrudTest extends TestCase
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

    public function test_list_portfolio_public(): void
    {
        PortfolioItem::factory()->count(3)->create([
            'provider_id' => $this->prestataire->id,
        ]);

        $response = $this->getJson("/api/v1/providers/{$this->prestataire->id}/portfolio");

        $response->assertOk()
            ->assertJsonCount(3);
    }

    public function test_list_portfolio_includes_media(): void
    {
        $item = PortfolioItem::factory()->create([
            'provider_id' => $this->prestataire->id,
        ]);
        PortfolioMedia::factory()->count(2)->create([
            'portfolio_item_id' => $item->id,
        ]);

        $response = $this->getJson("/api/v1/providers/{$this->prestataire->id}/portfolio");

        $response->assertOk()
            ->assertJsonPath('0.media', fn (array $media) => count($media) === 2);
    }

    public function test_list_portfolio_ordered_by_position(): void
    {
        PortfolioItem::factory()->create([
            'provider_id' => $this->prestataire->id,
            'position' => 2,
        ]);
        PortfolioItem::factory()->create([
            'provider_id' => $this->prestataire->id,
            'position' => 1,
        ]);

        $response = $this->getJson("/api/v1/providers/{$this->prestataire->id}/portfolio");

        $response->assertOk()
            ->assertJsonPath('0.position', 1);
    }

    public function test_list_portfolio_404_for_non_prestataire(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $response = $this->getJson("/api/v1/providers/{$client->id}/portfolio");

        $response->assertNotFound();
    }

    public function test_store_portfolio_item(): void
    {
        Queue::fake();

        $response = $this->postJson(
            "/api/v1/providers/{$this->prestataire->id}/portfolio",
            [
                'title' => 'Gros mariage au palace',
                'description' => 'Un superbe mariage de 300 personnes',
                'event_date' => '2025-06-15',
                'location' => 'Palace Royal',
                'budget_approx' => 15000,
            ],
            ['Authorization' => "Bearer {$this->token}"]
        );

        $response->assertCreated()
            ->assertJsonStructure(['id', 'title', 'description', 'event_date', 'location', 'budget_approx']);

        $this->assertDatabaseHas('portfolio_items', [
            'provider_id' => $this->prestataire->id,
            'title' => 'Gros mariage au palace',
        ]);
    }

    public function test_store_portfolio_item_with_media(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not installed.');
        }

        Queue::fake();
        Storage::fake('public');

        $content = $this->createTestJpegContent();
        $file = UploadedFile::fake()->createWithContent('photo.jpg', $content);

        $response = $this->postJson(
            "/api/v1/providers/{$this->prestataire->id}/portfolio",
            [
                'title' => 'Mariage avec photos',
                'media' => [$file],
            ],
            ['Authorization' => "Bearer {$this->token}"]
        );

        $response->assertCreated()
            ->assertJsonPath('media', fn (array $media) => count($media) === 1);

        $this->assertDatabaseHas('portfolio_media', [
            'type' => 'image',
        ]);

        Queue::assertPushed(ProcessPortfolioImage::class);
    }

    public function test_store_portfolio_requires_auth(): void
    {
        $response = $this->postJson(
            "/api/v1/providers/{$this->prestataire->id}/portfolio",
            ['title' => 'Test']
        );

        $response->assertUnauthorized();
    }

    public function test_store_portfolio_rejects_non_owner(): void
    {
        $other = User::factory()->create(['role' => 'prestataire']);
        $otherToken = JWTAuth::fromUser($other);

        $response = $this->postJson(
            "/api/v1/providers/{$this->prestataire->id}/portfolio",
            ['title' => 'Test'],
            ['Authorization' => "Bearer {$otherToken}"]
        );

        $response->assertForbidden();
    }

    public function test_update_portfolio_item(): void
    {
        $item = PortfolioItem::factory()->create([
            'provider_id' => $this->prestataire->id,
        ]);

        $response = $this->patchJson(
            "/api/v1/portfolio/{$item->id}",
            ['title' => 'Titre modifié'],
            ['Authorization' => "Bearer {$this->token}"]
        );

        $response->assertOk()
            ->assertJsonPath('title', 'Titre modifié');
    }

    public function test_update_portfolio_rejects_non_owner(): void
    {
        $item = PortfolioItem::factory()->create([
            'provider_id' => $this->prestataire->id,
        ]);

        $other = User::factory()->create(['role' => 'prestataire']);
        $otherToken = JWTAuth::fromUser($other);

        $response = $this->patchJson(
            "/api/v1/portfolio/{$item->id}",
            ['title' => 'Hack'],
            ['Authorization' => "Bearer {$otherToken}"]
        );

        $response->assertForbidden();
    }

    public function test_delete_portfolio_item(): void
    {
        $item = PortfolioItem::factory()->create([
            'provider_id' => $this->prestataire->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/portfolio/{$item->id}",
            [],
            ['Authorization' => "Bearer {$this->token}"]
        );

        $response->assertOk();
        $this->assertDatabaseMissing('portfolio_items', ['id' => $item->id]);
    }

    public function test_delete_portfolio_cascades_media(): void
    {
        $item = PortfolioItem::factory()->create([
            'provider_id' => $this->prestataire->id,
        ]);
        PortfolioMedia::factory()->count(2)->create([
            'portfolio_item_id' => $item->id,
        ]);

        $this->deleteJson(
            "/api/v1/portfolio/{$item->id}",
            [],
            ['Authorization' => "Bearer {$this->token}"]
        )->assertOk();

        $this->assertDatabaseMissing('portfolio_media', [
            'portfolio_item_id' => $item->id,
        ]);
    }

    private function createTestJpegContent(): string
    {
        $image = imagecreatetruecolor(100, 100);
        ob_start();
        imagejpeg($image, null, 80);
        $content = ob_get_clean();
        imagedestroy($image);

        return $content;
    }
}
