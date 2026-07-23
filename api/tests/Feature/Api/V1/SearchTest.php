<?php

namespace Tests\Feature\Api\V1;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_prestataires_only(): void
    {
        User::factory()->prestataire()->create(['name' => 'Ahmed Catering']);
        User::factory()->create(['name' => 'Client User', 'role' => 'client']);
        User::factory()->prestataire()->create(['name' => 'Sara Photography']);

        $response = $this->getJson('/api/v1/search?q=Ahmed');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ahmed Catering');
    }

    public function test_search_by_name(): void
    {
        User::factory()->prestataire()->create(['name' => 'Al Noor Events']);
        User::factory()->prestataire()->create(['name' => 'Sultan Decoration']);
        User::factory()->prestataire()->create(['name' => 'Al Noor Flowers']);

        $response = $this->getJson('/api/v1/search?q=Noor');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_search_by_bio(): void
    {
        User::factory()->prestataire()->create([
            'name' => 'Test Provider',
            'bio' => 'Spécialiste du traiteur haut de gamme pour mariages royaux',
        ]);

        $response = $this->getJson('/api/v1/search?q=traiteur');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_by_city(): void
    {
        User::factory()->prestataire()->create(['city' => 'Muscat']);
        User::factory()->prestataire()->create(['city' => 'Salalah']);

        $response = $this->getJson('/api/v1/search?city=Muscat');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.city', 'Muscat');
    }

    public function test_search_by_category(): void
    {
        User::factory()->prestataire()->create(['category' => 'Traiteur']);
        User::factory()->prestataire()->create(['category' => 'DJ & Musique']);

        $response = $this->getJson('/api/v1/search?category=Traiteur');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'Traiteur');
    }

    public function test_search_by_category_is_partial_match(): void
    {
        User::factory()->prestataire()->create(['category' => 'Photographe']);
        User::factory()->prestataire()->create(['category' => 'Vidéaste']);

        $response = $this->getJson('/api/v1/search?category=Photo');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_by_price_min(): void
    {
        $cheap = User::factory()->prestataire()->create();
        Service::factory()->create(['provider_id' => $cheap->id, 'price' => 50]);

        $expensive = User::factory()->prestataire()->create();
        Service::factory()->create(['provider_id' => $expensive->id, 'price' => 500]);

        $response = $this->getJson('/api/v1/search?price_min=100');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $expensive->id);
    }

    public function test_search_by_price_max(): void
    {
        $cheap = User::factory()->prestataire()->create();
        Service::factory()->create(['provider_id' => $cheap->id, 'price' => 50]);

        $expensive = User::factory()->prestataire()->create();
        Service::factory()->create(['provider_id' => $expensive->id, 'price' => 500]);

        $response = $this->getJson('/api/v1/search?price_max=100');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cheap->id);
    }

    public function test_search_by_price_range(): void
    {
        $a = User::factory()->prestataire()->create();
        Service::factory()->create(['provider_id' => $a->id, 'price' => 50]);

        $b = User::factory()->prestataire()->create();
        Service::factory()->create(['provider_id' => $b->id, 'price' => 200]);

        $c = User::factory()->prestataire()->create();
        Service::factory()->create(['provider_id' => $c->id, 'price' => 500]);

        $response = $this->getJson('/api/v1/search?price_min=100&price_max=300');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $b->id);
    }

    public function test_search_by_rating_min(): void
    {
        User::factory()->prestataire()->create(['average_rating' => 2.5]);
        User::factory()->prestataire()->create(['average_rating' => 4.5]);

        $response = $this->getJson('/api/v1/search?rating_min=4');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_by_verified(): void
    {
        User::factory()->prestataire()->create([
            'email_verified_at' => now(),
            'name' => 'Verified Provider',
        ]);
        User::factory()->prestataire()->unverified()->create([
            'name' => 'Unverified Provider',
        ]);

        $response = $this->getJson('/api/v1/search?verified=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Verified Provider');
    }

    public function test_search_by_unverified(): void
    {
        User::factory()->prestataire()->create(['email_verified_at' => now()]);
        User::factory()->prestataire()->unverified()->create();

        $response = $this->getJson('/api/v1/search?verified=false');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_by_location_haversine(): void
    {
        User::factory()->prestataire()->create([
            'latitude' => 23.5880,
            'longitude' => 58.3829,
            'name' => 'Muscat Provider',
        ]);

        User::factory()->prestataire()->create([
            'latitude' => 17.0151,
            'longitude' => 54.0924,
            'name' => 'Salalah Provider',
        ]);

        $response = $this->getJson('/api/v1/search?lat=23.5880&lng=58.3829&radius=10');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Muscat Provider')
            ->assertJsonStructure(['data' => [0 => ['distance_km']]]);
    }

    public function test_search_by_location_excludes_far_providers(): void
    {
        User::factory()->prestataire()->create([
            'latitude' => 17.0151,
            'longitude' => 54.0924,
            'name' => 'Salalah Provider',
        ]);

        $response = $this->getJson('/api/v1/search?lat=23.5880&lng=58.3829&radius=10');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_search_by_city_name_fallback(): void
    {
        User::factory()->prestataire()->create([
            'city' => 'Sohar',
            'latitude' => 24.3640,
            'longitude' => 56.7468,
        ]);

        User::factory()->prestataire()->create([
            'city' => 'Salalah',
            'latitude' => 17.0151,
            'longitude' => 54.0924,
        ]);

        $response = $this->getJson('/api/v1/search?city=Sohar');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_returns_empty_when_no_match(): void
    {
        User::factory()->prestataire()->create(['name' => 'Ahmed Catering']);

        $response = $this->getJson('/api/v1/search?q=xyznonexistent');

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_search_combined_filters(): void
    {
        $match = User::factory()->prestataire()->create([
            'name' => 'Al Noor Catering',
            'city' => 'Muscat',
            'category' => 'Traiteur',
            'average_rating' => 4.8,
            'email_verified_at' => now(),
        ]);
        Service::factory()->create(['provider_id' => $match->id, 'price' => 200]);

        User::factory()->prestataire()->create([
            'name' => 'Al Noor Photography',
            'city' => 'Salalah',
            'category' => 'Photographe',
            'average_rating' => 3.0,
            'email_verified_at' => null,
        ]);

        $response = $this->getJson('/api/v1/search?q=Noor&city=Muscat&category=Traiteur&rating_min=4&verified=true&price_min=100&price_max=300');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }

    public function test_search_pagination(): void
    {
        User::factory()->prestataire()->count(25)->create();

        $page1 = $this->getJson('/api/v1/search?per_page=10&page=1');
        $page1->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.total', 25);

        $page2 = $this->getJson('/api/v1/search?per_page=10&page=2');
        $page2->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_search_default_pagination(): void
    {
        User::factory()->prestataire()->count(5)->create();

        $response = $this->getJson('/api/v1/search');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_search_includes_services(): void
    {
        $provider = User::factory()->prestataire()->create();
        Service::factory()->count(3)->create(['provider_id' => $provider->id]);

        $response = $this->getJson('/api/v1/search');

        $response->assertOk()
            ->assertJsonPath('data.0.services', fn ($services) => count($services) === 3);
    }

    public function test_search_clients_excluded(): void
    {
        User::factory()->create(['name' => 'Client User', 'role' => 'client']);

        $response = $this->getJson('/api/v1/search?q=Client');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_search_validation_rejects_invalid_params(): void
    {
        $this->getJson('/api/v1/search?lat=999')
            ->assertUnprocessable();

        $this->getJson('/api/v1/search?per_page=0')
            ->assertUnprocessable();

        $this->getJson('/api/v1/search?rating_min=10')
            ->assertUnprocessable();
    }

    public function test_search_performance_with_500_providers(): void
    {
        User::factory()->prestataire()->count(500)->create();

        $start = microtime(true);

        $response = $this->getJson('/api/v1/search?q=Traiteur&city=Muscat&per_page=20');

        $elapsed = microtime(true) - $start;

        $response->assertOk();
        $this->assertLessThan(2.0, $elapsed, "Search took {$elapsed}s, should be under 2s");
    }

    public function test_search_trigram_fuzzy_name(): void
    {
        User::factory()->prestataire()->create(['name' => 'Al Noor Events']);
        User::factory()->prestataire()->create(['name' => 'Al Noor Catering']);

        $response = $this->getJson('/api/v1/search?q=Noor');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_search_distance_sorted_nearest_first(): void
    {
        User::factory()->prestataire()->create([
            'latitude' => 23.6100,
            'longitude' => 58.5400,
            'name' => 'Far provider',
        ]);

        User::factory()->prestataire()->create([
            'latitude' => 23.5900,
            'longitude' => 58.3900,
            'name' => 'Close provider',
        ]);

        $response = $this->getJson('/api/v1/search?lat=23.5880&lng=58.3829&radius=50');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Close provider')
            ->assertJsonPath('data.1.name', 'Far provider');
    }
}
