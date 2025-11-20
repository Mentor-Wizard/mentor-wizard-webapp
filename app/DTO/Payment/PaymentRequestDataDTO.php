<?php

declare(strict_types=1);

namespace App\DTO\Payment;

use App\Enums\Payment\PaymentTypeEnum;

readonly class PaymentRequestDataDTO
{
    public function __construct(
        public string $orderReference,
        public float $amount,
        public string $currency,
        public string $productName,
        public int $productCount,
        public float $productPrice,
        public PaymentTypeEnum $paymentType,
        public ?int $mentorSessionId = null,
        public ?string $clientFirstName = null,
        public ?string $clientLastName = null,
        public ?string $clientEmail = null,
        public ?string $clientPhone = null,
        public ?string $clientCountry = null,
        public ?string $clientCity = null,
        public ?string $clientAddress = null,
        public ?string $rectoken = null,
        public bool $isRegular = false,
        public ?int $parentPaymentId = null,
        public array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderReference: $data['order_reference'],
            amount: $data['amount'],
            currency: $data['currency'] ?? 'UAH',
            productName: $data['product_name'],
            productCount: $data['product_count'] ?? 1,
            productPrice: $data['product_price'],
            paymentType: $data['payment_type'] ?? PaymentTypeEnum::Purchase,
            mentorSessionId: $data['mentor_session_id'] ?? null,
            clientFirstName: $data['client_first_name'] ?? null,
            clientLastName: $data['client_last_name'] ?? null,
            clientEmail: $data['client_email'] ?? null,
            clientPhone: $data['client_phone'] ?? null,
            clientCountry: $data['client_country'] ?? 'UA',
            clientCity: $data['client_city'] ?? null,
            clientAddress: $data['client_address'] ?? null,
            rectoken: $data['rectoken'] ?? null,
            isRegular: $data['is_regular'] ?? false,
            parentPaymentId: $data['parent_payment_id'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
