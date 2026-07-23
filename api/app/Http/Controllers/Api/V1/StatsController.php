<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProviderStatsDaily;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * GET /api/v1/providers/{id}/stats?period=7d|30d|12m
     *
     * Basic stats (7d) available to all providers.
     * Advanced stats (30d, 12m) require Pro/Premium plan (has_advanced_stats).
     */
    public function index(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$user->isPrestataire()) {
            return response()->json(['message' => 'Accès réservé aux prestataires.'], 403);
        }

        if ((string) $user->id !== (string) $id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $period = $request->query('period', '7d');

        if (!in_array($period, ['7d', '30d', '12m'])) {
            return response()->json(['message' => 'Période invalide. Valide: 7d, 30d, 12m'], 422);
        }

        // Advanced periods require has_advanced_stats
        if (in_array($period, ['30d', '12m']) && !$this->subscriptionService->hasAdvancedStats($user->id)) {
            return response()->json([
                'message' => 'Statistiques avancées réservées aux plans Pro et Premium.',
                'required_plan' => 'pro',
            ], 403);
        }

        $stats = ProviderStatsDaily::forProvider($user->id)
            ->period($period)
            ->orderBy('date')
            ->get();

        $totals = [
            'visits' => $stats->sum('visits'),
            'clicks' => $stats->sum('clicks'),
            'contacts' => $stats->sum('contacts'),
            'favorites_count' => $stats->sum('favorites_count'),
            'quote_requests_count' => $stats->sum('quote_requests_count'),
        ];

        $daily = $stats->map(fn ($s) => [
            'date' => $s->date->format('Y-m-d'),
            'visits' => $s->visits,
            'clicks' => $s->clicks,
            'contacts' => $s->contacts,
            'favorites_count' => $s->favorites_count,
            'quote_requests_count' => $s->quote_requests_count,
        ]);

        return response()->json([
            'period' => $period,
            'totals' => $totals,
            'daily' => $daily,
        ]);
    }

    public function myStats(Request $request): JsonResponse
    {
        return $this->index($request, (string) $request->user()->id);
    }
}
