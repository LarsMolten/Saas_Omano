<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * GET /api/v1/subscriptions/plans
     * Public catalogue of plans and their limits.
     */
    public function plans(): JsonResponse
    {
        $plans = Config::get('subscription.plans', []);

        $catalogue = array_map(fn (array $plan) => [
            'label' => $plan['label'],
            'description' => $plan['description'],
            'monthly_price' => $plan['monthly_price'],
            'yearly_price' => $plan['yearly_price'],
            'limits' => $plan['limits'],
        ], $plans);

        return response()->json(array_values($catalogue));
    }

    /**
     * POST /api/v1/subscriptions/checkout
     * Create a subscription in "pending" status (payment integration later).
     */
    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isPrestataire()) {
            return response()->json(['message' => 'Seuls les prestataires peuvent souscrire.'], 403);
        }

        $validated = $request->validate([
            'plan' => 'required|in:starter,pro,premium',
            'period' => 'required|in:monthly,yearly',
        ]);

        $existing = $this->subscriptionService->getActiveSubscription($user->id);

        if ($existing && $existing->plan === $validated['plan'] && $existing->period === $validated['period']) {
            return response()->json(['message' => 'Vous avez déjà ce plan actif.'], 409);
        }

        // Cancel any existing active subscription before creating new one
        if ($existing) {
            $existing->update(['status' => 'cancelled']);
        }

        $startsAt = now();
        $endsAt = $validated['period'] === 'monthly'
            ? now()->addMonth()
            : now()->addYear();

        $subscription = Subscription::create([
            'provider_id' => $user->id,
            'plan' => $validated['plan'],
            'period' => $validated['period'],
            'status' => 'pending',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        return response()->json([
            'message' => 'Abonnement créé. Procédez au paiement pour l\'activer.',
            'subscription' => $subscription,
        ], 201);
    }

    /**
     * GET /api/v1/subscriptions/current
     * Return the current active subscription + limits for the authenticated user.
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isPrestataire()) {
            return response()->json(['message' => 'Seuls les prestataires ont des abonnements.'], 403);
        }

        $subscription = $this->subscriptionService->getActiveSubscription($user->id);
        $plan = $this->subscriptionService->getCurrentPlan($user->id);
        $limits = $this->subscriptionService->getLimits($plan);

        $remainingMedia = $this->subscriptionService->remainingMediaSlots($user->id);
        $remainingServices = $this->subscriptionService->remainingServiceSlots($user->id);

        return response()->json([
            'subscription' => $subscription,
            'plan' => $plan,
            'limits' => $limits,
            'remaining' => [
                'media' => $remainingMedia,
                'services' => $remainingServices,
            ],
        ]);
    }
}
