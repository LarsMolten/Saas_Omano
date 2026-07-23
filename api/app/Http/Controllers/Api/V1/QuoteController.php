<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isClient()) {
            $quotes = QuoteRequest::where('user_id', $user->id)
                ->with('provider:id,name,city,category')
                ->orderByDesc('created_at')
                ->get();
        } elseif ($user->isPrestataire()) {
            $quotes = QuoteRequest::where('provider_id', $user->id)
                ->with('user:id,name,email,phone')
                ->orderByDesc('created_at')
                ->get();
        } else {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        return response()->json($quotes);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isClient()) {
            return response()->json(['message' => 'Accès réservé aux clients.'], 403);
        }

        $dailyCount = QuoteRequest::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($dailyCount >= 10) {
            return response()->json([
                'message' => 'Limite de 10 demandes par jour atteinte.',
            ], 429);
        }

        $validated = $request->validate([
            'provider_id' => 'required|exists:users,id',
            'service_type' => 'required|string|max:255',
            'event_date' => 'nullable|date|after:today',
            'location' => 'nullable|string|max:255',
            'budget' => 'nullable|numeric|min:0|max:999999.99',
            'description' => 'nullable|string|max:5000',
        ]);

        $provider = User::findOrFail($validated['provider_id']);

        if (!$provider->isPrestataire()) {
            return response()->json(['message' => 'Ce n\'est pas un prestataire.'], 422);
        }

        $quote = QuoteRequest::create([
            'user_id' => $user->id,
            'provider_id' => $provider->id,
            'service_type' => $validated['service_type'],
            'event_date' => $validated['event_date'] ?? null,
            'location' => $validated['location'] ?? null,
            'budget' => $validated['budget'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        // TODO: dispatch notification to provider (step 10)

        return response()->json(
            $quote->load('provider:id,name,city,category'),
            201
        );
    }

    public function respond(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        if (!$user->isPrestataire()) {
            return response()->json(['message' => 'Accès réservé aux prestataires.'], 403);
        }

        $quote = QuoteRequest::findOrFail($id);

        if ($quote->provider_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        if ($quote->status !== 'pending') {
            return response()->json([
                'message' => 'Cette demande a déjà été traitée.',
            ], 422);
        }

        $validated = $request->validate([
            'status' => 'required|in:accepted,declined,answered',
            'provider_response' => 'required|string|max:5000',
        ]);

        $quote->update([
            'status' => $validated['status'],
            'provider_response' => $validated['provider_response'],
        ]);

        // TODO: dispatch notification to client (step 10)

        return response()->json(
            $quote->load('user:id,name,email')
        );
    }
}
