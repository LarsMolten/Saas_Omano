<?php

namespace App\Services\Gateways;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Models\Payment;

class FakeGateway implements PaymentGatewayInterface
{
    public function initiate(Payment $payment): array
    {
        return [
            'status' => 'pending',
            'message' => 'Paiement initié (mode test)',
            'external_reference' => 'FAKE-' . strtoupper(uniqid()),
            'redirect_url' => null,
        ];
    }

    public function verifyWebhook(array $payload, array $headers): bool
    {
        return ($payload['secret'] ?? null) === config('payments.fake_webhook_secret');
    }

    public function getStatus(Payment $payment): string
    {
        if ($payment->status === 'success' || $payment->status === 'failed') {
            return $payment->status;
        }

        return 'pending';
    }
}
