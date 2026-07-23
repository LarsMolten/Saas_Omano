<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotification;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Gateways\FakeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class PaymentController extends Controller
{
    /**
     * Resolve the active gateway from config.
     */
    private function gateway(): \App\Contracts\Payments\PaymentGatewayInterface
    {
        $driver = Config::get('payments.gateway', 'fake');
        $class = Config::get("payments.gateways.{$driver}", FakeGateway::class);

        return App::make($class);
    }

    /**
     * POST /api/v1/payments/initiate
     * Create a pending payment for a subscription.
     */
    public function initiate(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isPrestataire()) {
            return response()->json(['message' => 'Seuls les prestataires peuvent payer.'], 403);
        }

        $validated = $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'operator' => 'required|in:mvola,orange_money,airtel_money',
        ]);

        $subscription = Subscription::findOrFail($validated['subscription_id']);

        if ($subscription->provider_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        if ($subscription->status === 'active' && $subscription->ends_at->isFuture()) {
            return response()->json(['message' => 'Cet abonnement est déjà actif.'], 409);
        }

        $planKey = $subscription->plan;
        $period = $subscription->period;
        $amount = Config::get("subscription.plans.{$planKey}.{$period}_price");

        if ($amount === null) {
            return response()->json(['message' => 'Prix introuvable pour ce plan.'], 422);
        }

        $payment = Payment::create([
            'subscription_id' => $subscription->id,
            'operator' => $validated['operator'],
            'amount' => $amount,
            'status' => 'pending',
        ]);

        $gateway = $this->gateway();
        $result = $gateway->initiate($payment);

        if (!empty($result['external_reference'])) {
            $payment->update(['external_reference' => $result['external_reference']]);
        }

        return response()->json([
            'message' => 'Paiement initié.',
            'payment' => $payment->fresh(),
            'gateway' => $result,
        ], 201);
    }

    /**
     * POST /api/v1/payments/webhook/{operator}
     * Public, idempotent. Verifies signature, updates payment status, activates subscription.
     */
    public function webhook(Request $request, string $operator): JsonResponse
    {
        $gateway = $this->gateway();
        $payload = $request->all();
        $headers = $request->headers->all();

        // Flatten headers for gateway (first value of each)
        $flatHeaders = array_map(fn ($v) => is_array($v) ? $v[0] : $v, $headers);

        if (!$gateway->verifyWebhook($payload, $flatHeaders)) {
            return response()->json(['message' => 'Signature invalide.'], 403);
        }

        $externalRef = $payload['external_reference'] ?? null;
        $newStatus = $payload['status'] ?? null;

        if (!$externalRef || !$newStatus) {
            return response()->json(['message' => 'Payload invalide.'], 422);
        }

        $payment = Payment::where('external_reference', $externalRef)->first();

        if (!$payment) {
            return response()->json(['message' => 'Paiement introuvable.'], 404);
        }

        // Idempotency: skip if already terminal
        if ($payment->status === 'success' || $payment->status === 'failed') {
            return response()->json(['message' => 'Événement déjà traité.']);
        }

        $payment->update([
            'status' => $newStatus,
            'webhook_payload' => $payload,
        ]);

        if ($newStatus === 'success') {
            $this->activateSubscription($payment);
        }

        return response()->json(['message' => 'Webhook traité.']);
    }

    /**
     * GET /api/v1/payments/{id}/status
     */
    public function status(Request $request, string $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        $user = $request->user();

        if ($payment->subscription->provider_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $gateway = $this->gateway();
        $operatorStatus = $gateway->getStatus($payment);

        return response()->json([
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'operator_status' => $operatorStatus,
        ]);
    }

    /**
     * Activate the subscription linked to a successful payment.
     */
    private function activateSubscription(Payment $payment): void
    {
        $subscription = $payment->subscription;

        if ($subscription->status === 'active' && $subscription->ends_at->isFuture()) {
            return;
        }

        $startsAt = now();
        $endsAt = $subscription->period === 'monthly'
            ? now()->addMonth()
            : now()->addYear();

        $subscription->update([
            'status' => 'active',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        $user = $subscription->provider;

        SendNotification::dispatch(
            userId: $user->id,
            type: 'payment.success',
            payload: [
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id,
                'plan' => $subscription->plan,
                'amount' => $payment->amount,
                'operator' => $payment->operator,
            ],
            emailSubject: 'Paiement confirmé',
            emailBody: "Votre paiement de {$payment->amount} {$payment->operator} a été confirmé. Votre abonnement {$subscription->plan} est maintenant actif.",
        );
    }
}
