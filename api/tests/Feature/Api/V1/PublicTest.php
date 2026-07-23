<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_returns_featured_categories_recent(): void
    {
        // Create premium provider
        $provider = User::factory()->prestataire()->create([
            'name' => 'Aisha Traiteur',
            'slug' => 'aisha-traiteur',
            'average_rating' => 4.5,
        ]);
        Subscription::create([
            'provider_id' => $provider->id,
            'plan' => 'premium',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        // Create category
        Category::create([
            'name' => 'Traiteur',
            'slug' => 'traiteur',
            'sort_order' => 1,
            'active' => true,
        ]);

        $response = $this->getJson('/api/v1/homepage');

        $response->assertOk()
            ->assertJsonStructure([
                'featured' => [['id', 'name', 'slug', 'category', 'city', 'average_rating']],
                'categories' => [['id', 'name', 'slug', 'provider_count']],
                'recent' => [['id', 'name', 'slug']],
            ]);

        $this->assertCount(1, $response->json('featured'));
        $this->assertCount(1, $response->json('categories'));
    }

    public function test_homepage_excludes_inactive_providers(): void
    {
        User::factory()->prestataire()->create([
            'status' => 'suspended',
        ]);

        $response = $this->getJson('/api/v1/homepage');

        $response->assertOk();
        $this->assertCount(0, $response->json('recent'));
    }

    public function test_homepage_orders_featured_by_rating(): void
    {
        $lowRated = User::factory()->prestataire()->create(['average_rating' => 3.0]);
        $highRated = User::factory()->prestataire()->create(['average_rating' => 5.0]);

        Subscription::create([
            'provider_id' => $lowRated->id,
            'plan' => 'premium',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
        Subscription::create([
            'provider_id' => $highRated->id,
            'plan' => 'premium',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $response = $this->getJson('/api/v1/homepage');

        $featured = $response->json('featured');
        $this->assertEquals($highRated->id, $featured[0]['id']);
        $this->assertEquals($lowRated->id, $featured[1]['id']);
    }

    public function test_provider_profile_by_slug(): void
    {
        $provider = User::factory()->prestataire()->create([
            'name' => 'Mohamed Photo',
            'slug' => 'mohamed-photo',
            'bio' => 'Photographe professionnel',
            'category' => 'Photographe',
            'city' => 'Muscat',
        ]);

        $response = $this->getJson('/api/v1/providers/slug/mohamed-photo');

        $response->assertOk()
            ->assertJsonStructure([
                'provider' => ['id', 'name', 'slug', 'bio', 'category', 'city', 'average_rating', 'rating_count'],
                'plan',
                'services',
                'portfolio',
                'reviews',
            ])
            ->assertJson([
                'provider' => [
                    'name' => 'Mohamed Photo',
                    'slug' => 'mohamed-photo',
                ],
            ]);
    }

    public function test_provider_profile_includes_services(): void
    {
        $provider = User::factory()->prestataire()->create(['slug' => 'test-provider']);
        $provider->services()->create([
            'title' => 'Pack Mariage',
            'price' => 500,
            'price_type' => 'from',
            'position' => 1,
        ]);

        $response = $this->getJson('/api/v1/providers/slug/test-provider');

        $response->assertOk();
        $this->assertCount(1, $response->json('services'));
        $this->assertEquals('Pack Mariage', $response->json('services.0.title'));
    }

    public function test_provider_profile_includes_reviews(): void
    {
        $provider = User::factory()->prestataire()->create(['slug' => 'reviewed-provider']);
        $client = User::factory()->create(['role' => 'client']);

        $provider->receivedReviews()->create([
            'user_id' => $client->id,
            'rating' => 5,
            'comment' => 'Excellent!',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/providers/slug/reviewed-provider');

        $response->assertOk();
        $this->assertEquals(5, $response->json('reviews.data.0.rating'));
    }

    public function test_provider_profile_returns_subscription_plan(): void
    {
        $provider = User::factory()->prestataire()->create(['slug' => 'pro-provider']);
        Subscription::create([
            'provider_id' => $provider->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $response = $this->getJson('/api/v1/providers/slug/pro-provider');

        $response->assertOk()
            ->assertJson(['plan' => 'pro']);
    }

    public function test_provider_profile_404_for_nonexistent_slug(): void
    {
        $response = $this->getJson('/api/v1/providers/slug/nonexistent');

        $response->assertNotFound();
    }

    public function test_provider_profile_404_for_client_role(): void
    {
        User::factory()->create([
            'slug' => 'client-user',
            'role' => 'client',
        ]);

        $response = $this->getJson('/api/v1/providers/slug/client-user');

        $response->assertNotFound();
    }

    public function test_category_by_slug(): void
    {
        $category = Category::create([
            'name' => 'Photographe',
            'slug' => 'photographe',
            'sort_order' => 1,
            'active' => true,
        ]);

        User::factory()->prestataire()->create([
            'category' => 'Photographe',
            'average_rating' => 4.5,
        ]);

        $response = $this->getJson('/api/v1/categories/slug/photographe');

        $response->assertOk()
            ->assertJsonStructure([
                'category' => ['id', 'name', 'slug'],
                'providers' => ['data' => [['id', 'name', 'category']]],
            ])
            ->assertJson([
                'category' => ['name' => 'Photographe'],
            ]);
    }

    public function test_category_by_slug_orders_by_rating(): void
    {
        Category::create([
            'name' => 'DJ',
            'slug' => 'dj',
            'active' => true,
        ]);

        $low = User::factory()->prestataire()->create([
            'category' => 'DJ',
            'average_rating' => 2.0,
        ]);
        $high = User::factory()->prestataire()->create([
            'category' => 'DJ',
            'average_rating' => 5.0,
        ]);

        $response = $this->getJson('/api/v1/categories/slug/dj');

        $providers = $response->json('providers.data');
        $this->assertEquals($high->id, $providers[0]['id']);
        $this->assertEquals($low->id, $providers[1]['id']);
    }

    public function test_category_by_slug_404_for_inactive(): void
    {
        Category::create([
            'name' => 'Inactive',
            'slug' => 'inactive-cat',
            'active' => false,
        ]);

        $response = $this->getJson('/api/v1/categories/slug/inactive-cat');

        $response->assertNotFound();
    }

    public function test_category_by_slug_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/v1/categories/slug/nonexistent');

        $response->assertNotFound();
    }

    public function test_categories_list(): void
    {
        Category::create([
            'name' => 'Traiteur',
            'slug' => 'traiteur',
            'sort_order' => 1,
            'active' => true,
        ]);
        Category::create([
            'name' => 'Inactive',
            'slug' => 'inactive',
            'sort_order' => 2,
            'active' => false,
        ]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertEquals('Traiteur', $response->json('0.name'));
    }

    public function test_categories_list_includes_provider_counts(): void
    {
        Category::create([
            'name' => 'Photographe',
            'slug' => 'photographe',
            'sort_order' => 1,
            'active' => true,
        ]);

        User::factory()->prestataire()->create(['category' => 'Photographe']);
        User::factory()->prestataire()->create(['category' => 'Photographe']);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $this->assertEquals(2, $response->json('0.provider_count'));
    }
}
