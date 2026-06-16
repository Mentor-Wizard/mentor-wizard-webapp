<?php

declare(strict_types=1);

use App\Support\CurrencyConverter;

covers(CurrencyConverter::class);

describe('CurrencyConverter', function (): void {
    describe('toCents', function (): void {
        it('converts a whole amount to cents', function (): void {
            expect(CurrencyConverter::toCents(100.0))->toBe(10000);
        });

        it('converts a fractional amount to cents', function (): void {
            expect(CurrencyConverter::toCents(99.99))->toBe(9999);
        });

        it('rounds half up to the nearest cent', function (): void {
            expect(CurrencyConverter::toCents(0.005))->toBe(1);
        });

        it('returns zero for a zero amount', function (): void {
            expect(CurrencyConverter::toCents(0.0))->toBe(0);
        });
    });

    describe('fromCents', function (): void {
        it('converts whole cents to a float amount', function (): void {
            expect(CurrencyConverter::fromCents(10000))->toBe(100.0);
        });

        it('converts fractional cents to a float amount', function (): void {
            expect(CurrencyConverter::fromCents(9999))->toBe(99.99);
        });

        it('returns zero for zero cents', function (): void {
            expect(CurrencyConverter::fromCents(0))->toBe(0.0);
        });
    });
});
