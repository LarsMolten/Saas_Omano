<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PortfolioItem;
use App\Models\User;
use App\Services\ProviderEventTracker;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
    public function index(string $providerId): JsonResponse
    {
        $provider = User::findOrFail($providerId);

        if (!$provider->isPrestataire()) {
            return response()->json(['message' => 'Prestataire introuvable.'], 404);
        }

        $items = $provider->portfolioItems()
            ->with('media')
            ->orderBy('position')
            ->get();

        ProviderEventTracker::track($provider->id, 'visit');

        return response()->json($items);
    }

    public function store(Request $request, string $providerId): JsonResponse
    {
        $user = $request->user();

        if ((string) $user->id !== (string) $providerId) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        if (!$user->isPrestataire()) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'event_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'budget_approx' => 'nullable|numeric|min:0|max:999999.99',
            'position' => 'nullable|integer|min:0',
            'media' => 'nullable|array|max:10',
            'media.*' => 'file|mimes:jpeg,jpg,png,gif,webp,mp4,quicktime|max:10240',
        ]);

        $maxPosition = $user->portfolioItems()->max('position') ?? 0;

        $item = $user->portfolioItems()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'event_date' => $validated['event_date'] ?? null,
            'location' => $validated['location'] ?? null,
            'budget_approx' => $validated['budget_approx'] ?? null,
            'position' => $validated['position'] ?? ($maxPosition + 1),
        ]);

        if ($request->hasFile('media')) {
            $this->handleMediaUpload($request, $item);
        }

        return response()->json(
            $item->load('media'),
            201
        );
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $item = PortfolioItem::findOrFail($id);

        if ($item->provider_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'event_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'budget_approx' => 'nullable|numeric|min:0|max:999999.99',
            'position' => 'nullable|integer|min:0',
        ]);

        $item->update($validated);

        return response()->json($item->load('media'));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $item = PortfolioItem::with('media')->findOrFail($id);

        if ($item->provider_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        foreach ($item->media as $media) {
            if (Storage::disk('public')->exists($media->url)) {
                Storage::disk('public')->delete($media->url);
            }
            if ($media->url_processed && Storage::disk('public')->exists($media->url_processed)) {
                Storage::disk('public')->delete($media->url_processed);
            }
        }

        $item->delete();

        return response()->json(['message' => 'Réalisation supprimée.']);
    }

    private function handleMediaUpload(Request $request, PortfolioItem $item): void
    {
        $subscriptionService = app(SubscriptionService::class);
        $userId = $item->provider_id;
        $limits = $subscriptionService->getLimitsForProvider($userId);

        // Check video permission
        $allowsVideo = $limits['allows_video'] ?? false;

        $existingCount = $item->media()->count();
        $files = $request->file('media');

        // Filter out videos if not allowed
        if (!$allowsVideo) {
            $files = array_filter($files, fn ($file) => in_array($file->getClientOriginalExtension(), ['jpeg', 'jpg', 'png', 'gif', 'webp']));
            $files = array_values($files);
        }

        // Apply media limit (total across all items)
        $maxMedia = $limits['max_portfolio_media'] ?? null;
        if ($maxMedia !== null) {
            $totalExisting = \App\Models\PortfolioMedia::whereHas('portfolioItem', function ($q) use ($userId) {
                $q->where('provider_id', $userId);
            })->count();
            $allowed = $maxMedia - $totalExisting;
            $files = array_slice($files, 0, $allowed);
        }

        $maxPosition = $item->media()->max('position') ?? 0;

        foreach ($files as $index => $file) {
            $isImage = in_array($file->getClientOriginalExtension(), ['jpeg', 'jpg', 'png', 'gif', 'webp']);
            $type = $isImage ? 'image' : 'video';

            $path = $file->store('portfolio', 'public');

            $media = $item->media()->create([
                'type' => $type,
                'url' => $path,
                'position' => $maxPosition + $index + 1,
            ]);

            if ($isImage) {
                \App\Jobs\ProcessPortfolioImage::dispatch($media);
            }
        }
    }
}
