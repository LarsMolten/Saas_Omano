<?php

namespace App\Contracts\Payments;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Initiate a payment with the operator.
     * Returns an array with operator-specific data (e.g. redirect URL, USSD code).
     */
    public function initiate(Payment $payment): array;

    /**
     * Verify the authenticity of a webhook payload via signature.
     */
    public function verifyWebhook(array $payload, array $headers): bool;

    /**
     * Check the status of a payment with the operator.
     * Returns 'pending', 'success', or 'failed'.
     */
    public function getStatus(Payment $payment): string;
}
