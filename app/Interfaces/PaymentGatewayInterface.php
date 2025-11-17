<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\DTO\PaymentRequestDataDTO;
use App\DTO\PaymentResponseDataDTO;

interface PaymentGatewayInterface
{
    /**
     * Initiate purchase
     */
    public function initiatePurchase(PaymentRequestDataDTO $data): PaymentResponseDataDTO;

    /**
     * Refund payment
     */
    public function refund(string $transactionId, float $amount, string $comment = ''): PaymentResponseDataDTO;

    /**
     * Get transaction status
     */
    public function getTransactionStatus(string $orderReference): PaymentResponseDataDTO;

    /**
     * Verify callback signature
     */
    public function verifyCallback(array $data): bool;
}
