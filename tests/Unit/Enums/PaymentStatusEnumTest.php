<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;

covers(PaymentStatusEnum::class);

describe('PaymentStatusEnum', function (): void {
    describe('fromWayForPay', function (): void {
        it('maps Approved to APPROVED', function (): void {
            expect(PaymentStatusEnum::fromWayForPay('Approved'))->toBe(PaymentStatusEnum::APPROVED);
        });

        it('maps Declined to DECLINED', function (): void {
            expect(PaymentStatusEnum::fromWayForPay('Declined'))->toBe(PaymentStatusEnum::DECLINED);
        });

        it('maps Voided to DECLINED', function (): void {
            expect(PaymentStatusEnum::fromWayForPay('Voided'))->toBe(PaymentStatusEnum::DECLINED);
        });

        it('maps Expired to DECLINED', function (): void {
            expect(PaymentStatusEnum::fromWayForPay('Expired'))->toBe(PaymentStatusEnum::DECLINED);
        });

        it('maps Refunded to REFUNDED', function (): void {
            expect(PaymentStatusEnum::fromWayForPay('Refunded'))->toBe(PaymentStatusEnum::REFUNDED);
        });

        it('maps unknown status to PENDING', function (): void {
            expect(PaymentStatusEnum::fromWayForPay('UnknownStatus'))->toBe(PaymentStatusEnum::PENDING);
        });

        it('maps empty string to PENDING', function (): void {
            expect(PaymentStatusEnum::fromWayForPay(''))->toBe(PaymentStatusEnum::PENDING);
        });
    });

    describe('isTerminal', function (): void {
        it('returns true for APPROVED', function (): void {
            expect(PaymentStatusEnum::APPROVED->isTerminal())->toBeTrue();
        });

        it('returns true for DECLINED', function (): void {
            expect(PaymentStatusEnum::DECLINED->isTerminal())->toBeTrue();
        });

        it('returns true for REFUNDED', function (): void {
            expect(PaymentStatusEnum::REFUNDED->isTerminal())->toBeTrue();
        });

        it('returns true for EXPIRED', function (): void {
            expect(PaymentStatusEnum::EXPIRED->isTerminal())->toBeTrue();
        });

        it('returns false for PENDING', function (): void {
            expect(PaymentStatusEnum::PENDING->isTerminal())->toBeFalse();
        });
    });

    describe('enum values', function (): void {
        it('has correct backing values', function (): void {
            expect(PaymentStatusEnum::PENDING->value)->toBe('pending');
            expect(PaymentStatusEnum::APPROVED->value)->toBe('approved');
            expect(PaymentStatusEnum::DECLINED->value)->toBe('declined');
            expect(PaymentStatusEnum::REFUNDED->value)->toBe('refunded');
            expect(PaymentStatusEnum::EXPIRED->value)->toBe('expired');
        });
    });
});
