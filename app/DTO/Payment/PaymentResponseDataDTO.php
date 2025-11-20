<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\Enums\Payments\PaymentStatusEnum;
use App\Enums\Payments\PaymentTypeEnum;

readonly class PaymentResponseDataDTO
{
    public function __construct(
        public bool $success,
        public ?string $transactionId = null,
        public ?string $orderReference = null,
        public ?PaymentStatusEnum $status = null,
        public ?PaymentTypeEnum $paymentType = null,
        public ?int $amount = null,
        public ?string $currency = null,
        public ?string $paymentUrl = null,
        public ?string $paymentSystem = null,
        public ?string $rectoken = null,
        public ?string $cardPan = null,
        public ?string $cardType = null,
        public ?string $issuerBankName = null,
        public ?string $phone = null,
        public ?string $accountNumber = null,
        public ?int $refundAmount = null,
        public ?string $refundedAt = null,
        public ?bool $isRegular = null,
        public ?int $parentPaymentId = null,
        public ?array $metadata = null,
        public ?string $reason = null,
        public ?string $reasonCode = null,
        public ?array $rawData = null,
        public ?string $errorMessage = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function success(array $data): self
    {
        return new self(
            success: true,
            transactionId: $data['transaction_id'] ?? null,
            orderReference: $data['order_reference'] ?? null,
            status: isset($data['status']) ? PaymentStatusEnum::from($data['status']) : null,
            paymentType: isset($data['payment_type']) ? PaymentTypeEnum::from($data['payment_type']) : null,
            amount: isset($data['amount']) ? (int) $data['amount'] : null,
            currency: $data['currency'] ?? null,
            paymentUrl: $data['payment_url'] ?? null,
            paymentSystem: $data['payment_system'] ?? 'WayForPay',
            rectoken: $data['rectoken'] ?? null,
            cardPan: $data['card_pan'] ?? null,
            cardType: $data['card_type'] ?? null,
            issuerBankName: $data['issuer_bank_name'] ?? null,
            phone: $data['phone'] ?? null,
            accountNumber: $data['account_number'] ?? null,
            refundAmount: isset($data['refund_amount']) ? (int) $data['refund_amount'] : null,
            refundedAt: $data['refunded_at'] ?? null,
            isRegular: $data['is_regular'] ?? null,
            parentPaymentId: $data['parent_payment_id'] ?? null,
            metadata: $data['metadata'] ?? null,
            reason: $data['reason'] ?? null,
            reasonCode: $data['reason_code'] ?? null,
            rawData: $data,
        );
    }

    public static function failure(string $errorMessage, ?array $rawData = null): self
    {
        return new self(
            success: false,
            rawData: $rawData,
            errorMessage: $errorMessage,
        );
    }
}
