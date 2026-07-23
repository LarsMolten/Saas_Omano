<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(string $providerId): JsonResponse
    {
        $provider = User::findOrFail($providerId);

        if (!$provider->isPrestataire()) {
            return response()->json(['message' => 'Prestataire introuvable.'], 404);
        }

        $services = $provider->services()
            ->with('options')
            ->orderBy('position')
            ->get();

        return response()->json($services);
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

        $subscriptionService = app(SubscriptionService::class);
        if (!$subscriptionService->canAddService($user->id)) {
            $remaining = $subscriptionService->remainingServiceSlots($user->id);
            return response()->json([
                'message' => "Limite de services atteinte. Passez à un plan supérieur pour en créer davantage.",
                'remaining' => $remaining,
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'price_type' => 'required|in:fixed,from,quote',
            'position' => 'nullable|integer|min:0',
            'options' => 'nullable|array',
            'options.*.label' => 'required_with:options|string|max:255',
            'options.*.extra_price' => 'nullable|numeric|min:0|max:999999.99',
        ]);

        $maxPosition = $user->services()->max('position') ?? 0;

        $service = $user->services()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'] ?? null,
            'price_type' => $validated['price_type'],
            'position' => $validated['position'] ?? ($maxPosition + 1),
        ]);

        if (!empty($validated['options'])) {
            foreach ($validated['options'] as $option) {
                $service->options()->create([
                    'label' => $option['label'],
                    'extra_price' => $option['extra_price'] ?? 0,
                ]);
            }
        }

        return response()->json(
            $service->load('options'),
            201
        );
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $service = Service::findOrFail($id);

        if ($service->provider_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'price_type' => 'sometimes|required|in:fixed,from,quote',
            'position' => 'nullable|integer|min:0',
            'options' => 'nullable|array',
            'options.*.id' => 'nullable|integer',
            'options.*.label' => 'required|string|max:255',
            'options.*.extra_price' => 'nullable|numeric|min:0|max:999999.99',
        ]);

        $service->update([
            'title' => $validated['title'] ?? $service->title,
            'description' => $validated['description'] ?? $service->description,
            'price' => $validated['price'] ?? $service->price,
            'price_type' => $validated['price_type'] ?? $service->price_type,
            'position' => $validated['position'] ?? $service->position,
        ]);

        if (array_key_exists('options', $validated)) {
            $existingIds = collect($validated['options'])
                ->pluck('id')
                ->filter()
                ->toArray();

            $service->options()
                ->whereNotIn('id', $existingIds)
                ->delete();

            foreach ($validated['options'] as $optionData) {
                if (!empty($optionData['id'])) {
                    $option = $service->options()->find($optionData['id']);
                    if ($option) {
                        $option->update([
                            'label' => $optionData['label'],
                            'extra_price' => $optionData['extra_price'] ?? 0,
                        ]);
                    }
                } else {
                    $service->options()->create([
                        'label' => $optionData['label'],
                        'extra_price' => $optionData['extra_price'] ?? 0,
                    ]);
                }
            }
        }

        return response()->json($service->load('options'));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $service = Service::findOrFail($id);

        if ($service->provider_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $service->delete();

        return response()->json(['message' => 'Service supprimé.']);
    }
}
