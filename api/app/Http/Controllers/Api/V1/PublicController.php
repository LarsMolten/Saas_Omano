<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Services\ProviderEventTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    public function homepage(): JsonResponse
    {
        // Premium providers with active subscription
        $premiumProviders = User::query()
            ->where('users.role', 'prestataire')
            ->where('users.status', 'active')
            ->with('services')
            ->leftJoin('subscriptions', function ($join) {
                $join->on('users.id', '=', 'subscriptions.provider_id')
                    ->where('subscriptions.status', '=', 'active')
                    ->where('subscriptions.ends_at', '>', now())
                    ->where('subscriptions.plan', '=', 'premium');
            })
            ->whereNotNull('subscriptions.id')
            ->orderByDesc('users.average_rating')
            ->limit(6)
            ->get([
                'users.id', 'users.name', 'users.slug', 'users.bio',
                'users.category', 'users.city', 'users.average_rating',
                'users.rating_count', 'users.email_verified_at',
            ]);

        // Active categories with provider counts
        $categories = Category::active()
            ->select('categories.*')
            ->leftJoin('users', function ($join) {
                $join->on('categories.name', '=', 'users.category')
                    ->where('users.role', '=', 'prestataire')
                    ->where('users.status', '=', 'active');
            })
            ->selectRaw('categories.*, COUNT(users.id) as provider_count')
            ->groupBy('categories.id')
            ->orderBy('categories.sort_order')
            ->get();

        // Recent providers
        $recentProviders = User::query()
            ->where('users.role', 'prestataire')
            ->where('users.status', 'active')
            ->with('services')
            ->orderByDesc('users.created_at')
            ->limit(8)
            ->get([
                'users.id', 'users.name', 'users.slug', 'users.bio',
                'users.category', 'users.city', 'users.average_rating',
                'users.rating_count', 'users.email_verified_at', 'users.created_at',
            ]);

        return response()->json([
            'featured' => $premiumProviders,
            'categories' => $categories,
            'recent' => $recentProviders,
        ]);
    }

    public function providerProfile(string $slug): JsonResponse
    {
        $provider = User::where('slug', $slug)
            ->where('role', 'prestataire')
            ->firstOrFail();

        $services = $provider->services()
            ->with('options')
            ->orderBy('position')
            ->get();

        $portfolioItems = $provider->portfolioItems()
            ->with('media')
            ->orderByDesc('position')
            ->limit(12)
            ->get();

        $reviews = $provider->receivedReviews()
            ->where('status', 'published')
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(10);

        // Track visit
        ProviderEventTracker::track($provider->id, 'visit');

        // Get subscription plan
        $subscription = $provider->subscription()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        return response()->json([
            'provider' => [
                'id' => $provider->id,
                'name' => $provider->name,
                'slug' => $provider->slug,
                'bio' => $provider->bio,
                'category' => $provider->category,
                'city' => $provider->city,
                'average_rating' => $provider->average_rating,
                'rating_count' => $provider->rating_count,
                'email_verified_at' => $provider->email_verified_at,
                'created_at' => $provider->created_at,
            ],
            'plan' => $subscription?->plan ?? 'free',
            'services' => $services,
            'portfolio' => $portfolioItems,
            'reviews' => $reviews,
        ]);
    }

    public function categoryBySlug(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->where('active', true)
            ->firstOrFail();

        $providers = User::query()
            ->where('role', 'prestataire')
            ->where('status', 'active')
            ->where('category', $category->name)
            ->with('services')
            ->orderByDesc('average_rating')
            ->paginate(20);

        return response()->json([
            'category' => $category,
            'providers' => $providers,
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::active()
            ->select('categories.*')
            ->leftJoin('users', function ($join) {
                $join->on('categories.name', '=', 'users.category')
                    ->where('users.role', '=', 'prestataire')
                    ->where('users.status', '=', 'active');
            })
            ->selectRaw('categories.*, COUNT(users.id) as provider_count')
            ->groupBy('categories.id')
            ->orderBy('categories.sort_order')
            ->get();

        return response()->json($categories);
    }
}
