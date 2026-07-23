<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\User;
use App\Services\ProviderEventTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favorites = Favorite::where('user_id', $request->user()->id)
            ->with('provider:id,name,bio,category,city,average_rating,email_verified_at')
            ->get()
            ->pluck('provider');

        return response()->json($favorites);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isClient()) {
            return response()->json(['message' => 'Accès réservé aux clients.'], 403);
        }

        $validated = $request->validate([
            'provider_id' => 'required|exists:users,id',
        ]);

        $provider = User::findOrFail($validated['provider_id']);

        if (!$provider->isPrestataire()) {
            return response()->json(['message' => 'Ce n\'est pas un prestataire.'], 422);
        }

        $exists = Favorite::where('user_id', $user->id)
            ->where('provider_id', $provider->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Déjà en favori.'], 409);
        }

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'provider_id' => $provider->id,
        ]);

        ProviderEventTracker::track($provider->id, 'favorite');

        return response()->json($favorite->load('provider:id,name,bio,category,city,average_rating,email_verified_at'), 201);
    }

    public function destroy(Request $request, string $providerId): JsonResponse
    {
        $user = $request->user();

        if (!$user->isClient()) {
            return response()->json(['message' => 'Accès réservé aux clients.'], 403);
        }

        $deleted = Favorite::where('user_id', $user->id)
            ->where('provider_id', $providerId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Favori non trouvé.'], 404);
        }

        return response()->json(['message' => 'Favori supprimé.']);
    }
}
