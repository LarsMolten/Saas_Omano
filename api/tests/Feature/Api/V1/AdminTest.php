<?php

namespace Tests\Feature\Api\V1;

use App\Models\AdminActionLog;
use App\Models\Category;
use App\Models\Report;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $adminToken;
    private User $client;
    private string $clientToken;
    private User $prestataire;
    private string $prestataireToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->adminToken = JWTAuth::fromUser($this->admin);

        $this->client = User::factory()->create(['role' => 'client']);
        $this->clientToken = JWTAuth::fromUser($this->client);

        $this->prestataire = User::factory()->prestataire()->create();
        $this->prestataireToken = JWTAuth::fromUser($this->prestataire);
    }

    // ── Access control ──

    public function test_admin_routes_require_auth(): void
    {
        $this->getJson('/api/v1/admin/users')->assertUnauthorized();
        $this->getJson('/api/v1/admin/categories')->assertUnauthorized();
        $this->getJson('/api/v1/admin/subscriptions')->assertUnauthorized();
        $this->getJson('/api/v1/admin/reports')->assertUnauthorized();
        $this->getJson('/api/v1/admin/stats')->assertUnauthorized();
    }

    public function test_admin_routes_reject_client(): void
    {
        $this->getJson('/api/v1/admin/users', [
            'Authorization' => "Bearer {$this->clientToken}",
        ])->assertForbidden();

        $this->getJson('/api/v1/admin/categories', [
            'Authorization' => "Bearer {$this->clientToken}",
        ])->assertForbidden();

        $this->getJson('/api/v1/admin/stats', [
            'Authorization' => "Bearer {$this->clientToken}",
        ])->assertForbidden();
    }

    public function test_admin_routes_reject_prestataire(): void
    {
        $this->getJson('/api/v1/admin/users', [
            'Authorization' => "Bearer {$this->prestataireToken}",
        ])->assertForbidden();

        $this->getJson('/api/v1/admin/categories', [
            'Authorization' => "Bearer {$this->prestataireToken}",
        ])->assertForbidden();

        $this->getJson('/api/v1/admin/stats', [
            'Authorization' => "Bearer {$this->prestataireToken}",
        ])->assertForbidden();
    }

    public function test_admin_routes_accept_admin(): void
    {
        $this->getJson('/api/v1/admin/users', [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk();

        $this->getJson('/api/v1/admin/categories', [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk();

        $this->getJson('/api/v1/admin/stats', [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk();
    }

    // ── Users management ──

    public function test_list_users(): void
    {
        $this->getJson('/api/v1/admin/users', [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()
            ->assertJsonCount(3, 'data'); // admin, client, prestataire
    }

    public function test_filter_users_by_role(): void
    {
        $this->getJson('/api/v1/admin/users?role=client', [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_users(): void
    {
        $this->getJson('/api/v1/admin/users?search=' . $this->client->name, [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_suspend_user(): void
    {
        $this->patchJson('/api/v1/admin/users', [
            'user_id' => $this->client->id,
            'status' => 'suspended',
            'reason' => 'Comportement inapproprié',
        ], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $this->client->id,
            'status' => 'suspended',
        ]);

        $this->assertDatabaseHas('admin_actions_log', [
            'admin_id' => $this->admin->id,
            'action' => 'user.update',
            'target_type' => 'user',
            'target_id' => $this->client->id,
        ]);
    }

    public function test_ban_user(): void
    {
        $this->patchJson('/api/v1/admin/users', [
            'user_id' => $this->client->id,
            'status' => 'banned',
        ], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $this->client->id,
            'status' => 'banned',
        ]);
    }

    public function test_change_user_role(): void
    {
        $this->patchJson('/api/v1/admin/users', [
            'user_id' => $this->client->id,
            'role' => 'prestataire',
        ], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $this->client->id,
            'role' => 'prestataire',
        ]);
    }

    public function test_update_user_validates(): void
    {
        $this->patchJson('/api/v1/admin/users', [
            'user_id' => $this->client->id,
            'status' => 'invalid_status',
        ], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertUnprocessable();
    }

    // ── Categories CRUD ──

    public function test_list_categories(): void
    {
        Category::create(['name' => 'Traiteur', 'slug' => 'traiteur']);
        Category::create(['name' => 'DJ', 'slug' => 'dj']);

        $this->getJson('/api/v1/admin/categories', [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()
            ->assertJsonCount(2);
    }

    public function test_create_category(): void
    {
        $this->postJson('/api/v1/admin/categories', [
            'name' => 'Photographe',
        ], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertCreated()
            ->assertJsonFragment(['name' => 'Photographe', 'slug' => 'photographe']);

        $this->assertDatabaseHas('admin_actions_log', [
            'admin_id' => $this->admin->id,
            'action' => 'category.create',
            'target_type' => 'category',
        ]);
    }

    public function test_create_category_validates_unique_name(): void
    {
        Category::create(['name' => 'DJ', 'slug' => 'dj']);

        $this->postJson('/api/v1/admin/categories', [
            'name' => 'DJ',
        ], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertUnprocessable();
    }

    public function test_update_category(): void
    {
        $cat = Category::create(['name' => 'DJ', 'slug' => 'dj']);

        $this->patchJson("/api/v1/admin/categories/{$cat->id}", [
            'name' => 'DJ & Musique',
        ], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()
            ->assertJsonFragment(['name' => 'DJ & Musique', 'slug' => 'dj-musique']);
    }

    public function test_delete_category(): void
    {
        $cat = Category::create(['name' => 'DJ', 'slug' => 'dj']);

        $this->deleteJson("/api/v1/admin/categories/{$cat->id}", [], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);

        $this->assertDatabaseHas('admin_actions_log', [
            'admin_id' => $this->admin->id,
            'action' => 'category.delete',
            'target_type' => 'category',
            'target_id' => $cat->id,
        ]);
    }

    public function test_category_crud_rejects_non_admin(): void
    {
        $this->postJson('/api/v1/admin/categories', ['name' => 'Test'], [
            'Authorization' => "Bearer {$this->clientToken}",
        ])->assertForbidden();
    }

    // ── Subscriptions ──

    public function test_list_subscriptions(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->getJson('/api/v1/admin/subscriptions', [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_subscriptions_by_plan(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->getJson('/api/v1/admin/subscriptions?plan=pro', [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/admin/subscriptions?plan=premium', [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_update_subscription_status(): void
    {
        $sub = Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->patchJson("/api/v1/admin/subscriptions/{$sub->id}", [
            'status' => 'cancelled',
        ], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'id' => $sub->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('admin_actions_log', [
            'admin_id' => $this->admin->id,
            'action' => 'subscription.update',
            'target_type' => 'subscription',
            'target_id' => $sub->id,
        ]);
    }

    // ── Reports ──

    public function test_list_reports(): void
    {
        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 2,
            'comment' => 'Bad service',
            'status' => 'reported',
        ]);

        Report::create([
            'reporter_id' => $this->prestataire->id,
            'reportable_type' => 'review',
            'reportable_id' => $review->id,
            'reason' => 'fake',
        ]);

        $this->getJson('/api/v1/admin/reports', [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_dismiss_report(): void
    {
        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 2,
            'comment' => 'Bad service',
            'status' => 'reported',
        ]);

        $report = Report::create([
            'reporter_id' => $this->prestataire->id,
            'reportable_type' => 'review',
            'reportable_id' => $review->id,
            'reason' => 'fake',
        ]);

        $this->patchJson("/api/v1/admin/reports/{$report->id}", [
            'action' => 'dismiss',
            'resolution_note' => 'Avis légitime',
        ], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk();

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'dismissed',
            'resolved_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('admin_actions_log', [
            'admin_id' => $this->admin->id,
            'action' => 'report.dismiss',
            'target_type' => 'review',
            'target_id' => $review->id,
        ]);
    }

    public function test_delete_reported_content(): void
    {
        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 1,
            'comment' => 'Offensive content',
            'status' => 'reported',
        ]);

        $report = Report::create([
            'reporter_id' => $this->prestataire->id,
            'reportable_type' => 'review',
            'reportable_id' => $review->id,
            'reason' => 'inappropriate',
        ]);

        $this->patchJson("/api/v1/admin/reports/{$report->id}", [
            'action' => 'content_deleted',
        ], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk();

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_sanction_user(): void
    {
        $review = Review::create([
            'user_id' => $this->client->id,
            'provider_id' => $this->prestataire->id,
            'rating' => 1,
            'comment' => 'Fake reviews',
            'status' => 'reported',
        ]);

        $report = Report::create([
            'reporter_id' => $this->prestataire->id,
            'reportable_type' => 'review',
            'reportable_id' => $review->id,
            'reason' => 'spam',
        ]);

        $this->patchJson("/api/v1/admin/reports/{$report->id}", [
            'action' => 'sanction',
            'resolution_note' => 'Spam confirmed',
        ], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $this->prestataire->id,
            'status' => 'suspended',
        ]);
    }

    public function test_cannot_resolve_already_resolved_report(): void
    {
        $report = Report::create([
            'reporter_id' => $this->client->id,
            'reportable_type' => 'review',
            'reportable_id' => 1,
            'reason' => 'fake',
            'status' => 'dismissed',
            'resolved_by' => $this->admin->id,
            'resolved_at' => now(),
        ]);

        $this->patchJson("/api/v1/admin/reports/{$report->id}", [
            'action' => 'dismiss',
        ], [
            'Authorization' => "Bearer {$this->adminToken}",
        ])->assertStatus(422);
    }

    // ── Stats ──

    public function test_stats_returns_global_stats(): void
    {
        Subscription::create([
            'provider_id' => $this->prestataire->id,
            'plan' => 'pro',
            'period' => 'monthly',
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $response = $this->getJson('/api/v1/admin/stats', [
            'Authorization' => "Bearer {$this->adminToken}",
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'users' => ['total', 'clients', 'prestataires', 'active_prestataires', 'suspended', 'banned'],
                'subscriptions' => ['active', 'by_plan'],
                'revenue' => ['by_plan', 'total'],
                'reports' => ['pending'],
                'reviews' => ['total', 'reported'],
            ])
            ->assertJsonPath('users.total', 3)
            ->assertJsonPath('users.clients', 1)
            ->assertJsonPath('users.prestataires', 1)
            ->assertJsonPath('subscriptions.active', 1);
    }

    // ── Admin action logging ──

    public function test_all_admin_actions_are_logged(): void
        {
            // User update
            $this->patchJson('/api/v1/admin/users', [
                'user_id' => $this->client->id,
                'status' => 'suspended',
                'reason' => 'Test',
            ], ['Authorization' => "Bearer {$this->adminToken}"]);

            // Category create
            $this->postJson('/api/v1/admin/categories', [
                'name' => 'Logged Category',
            ], ['Authorization' => "Bearer {$this->adminToken}"]);

            $logs = AdminActionLog::where('admin_id', $this->admin->id)->get();
            $this->assertCount(2, $logs);
            $this->assertEquals('user.update', $logs[0]->action);
            $this->assertEquals('category.create', $logs[1]->action);
        }
}
