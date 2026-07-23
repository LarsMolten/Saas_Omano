<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isClient()) {
            return response()->json(['message' => 'Accès réservé aux clients.'], 403);
        }

        $validated = $request->validate([
            'provider_id' => 'required|exists:users,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $provider = User::findOrFail($validated['provider_id']);

        if (!$provider->isPrestataire()) {
            return response()->json(['message' => 'Ce n\'est pas un prestataire.'], 422);
        }

        $exists = Review::where('user_id', $user->id)
            ->where('provider_id', $provider->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Vous avez déjà laissé un avis pour ce prestataire.',
            ], 422);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'provider_id' => $provider->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'status' => 'published',
        ]);

        return response()->json(
            $review->load('user:id,name'),
            201
        );
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $review = Review::findOrFail($id);

        if ($review->user_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        if (!$review->isEditable()) {
            return response()->json([
                'message' => 'Vous ne pouvez plus modifier cet avis (délai de 48h dépassé).',
            ], 422);
        }

        $validated = $request->validate([
            'rating' => 'sometimes|required|integer|between:1,5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $review->update($validated);

        return response()->json($review->fresh('user:id,name'));
    }

    public function report(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $review = Review::findOrFail($id);

        if ($review->user_id === $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas signaler votre propre avis.',
            ], 422);
        }

        if ($review->status === 'reported') {
            return response()->json([
                'message' => 'Cet avis a déjà été signalé.',
            ], 422);
        }

        $review->update(['status' => 'reported']);

        return response()->json(['message' => 'Avis signalé.']);
    }

    public function providerReviews(Request $request, string $id): JsonResponse
    {
        $reviews = Review::where('provider_id', $id)
            ->where('status', 'published')
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        return response()->json($reviews);
    }
}
