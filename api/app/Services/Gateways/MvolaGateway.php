<?php

namespace App\Services\Gateways;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Models\Payment;

class MvolaGateway implements PaymentGatewayInterface
{
    public function initiate(Payment $payment): array
    {
        // Real implementation would call MVola API
        return [
            'status' => 'pending',
            'message' => 'MVola payment initiated',
            'external_reference' => null,
            'redirect_url' => null,
        ];
    }

    public function verifyWebhook(array $payload, array $headers): bool
    {
        $secret = config('payments.mvola_webhook_secret');
        $signature = $headers['x-mvola-signature'] ?? $headers['X-MVola-Signature'] ?? null;

        if (!$secret || !$signature) {
            return false;
        }

        return hash_equals($secret, $signature);
    }

    public function getStatus(Payment $payment): string
    {
        // Real implementation would call MVola API with external_reference
        return 'pending';
    }
}
