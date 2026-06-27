<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Charge a payment.
     *
     * @param  int  $amountCents  Amount in the smallest currency unit (e.g. cents)
     * @param  array<string, mixed>  $metadata  Additional data for the payment provider
     * @return array{success: bool, transaction_id: ?string, error: ?string}
     */
    public function charge(int $amountCents, array $metadata = []): array;

    /**
     * Refund a previous charge.
     *
     * @param  string  $transactionId  The provider's transaction/charge ID
     * @param  int  $amountCents  Amount to refund (0 = full refund)
     * @return array{success: bool, refund_id: ?string, error: ?string}
     */
    public function refund(string $transactionId, int $amountCents = 0): array;
}
