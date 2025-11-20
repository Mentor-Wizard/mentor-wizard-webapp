<?php

declare(strict_types=1);

namespace App\Services\Payment;

class WayForPayService implements PaymentGatewayInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function initiatePurchase(PaymentRequestDataDTO $data): PaymentResponseDataDTO
    {
        // Implement method
    }

    public function refund(string $transactionId, float $amount, string $comment = ''): PaymentResponseDataDTO
    {
        // Implement method
    }

    public function getTransactionStatus(string $orderReference): PaymentResponseDataDTO
    {
        // Implement method
    }

    public function verifyCallback(array $data): bool
    {
        // Implement method
    }
}
