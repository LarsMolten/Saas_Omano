<?php

namespace App\Services\Gateways;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Models\Payment;

class AirtelMoneyGateway implements PaymentGatewayInterface
{
    public function initiate(Payment $payment): array
    {
        return [
            'status' => 'pending',
            'message' => 'Airtel Money payment initiated',
            'external_reference' => null,
            'redirect_url' => null,
        ];
    }

    public function verifyWebhook(array $payload, array $headers): bool
    {
        $secret = config('payments.airtel_webhook_secret');
        $signature = $headers['x-airtel-signature'] ?? $headers['X-Airtel-Signature'] ?? null;

        if (!$secret || !$signature) {
            return false;
        }

        return hash_equals($secret, $signature);
    }

    public function getStatus(Payment $payment): string
    {
        return 'pending';
    }
}
