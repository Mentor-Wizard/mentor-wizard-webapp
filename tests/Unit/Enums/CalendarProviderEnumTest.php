<?php

declare(strict_types=1);

use App\Enums\CalendarProviderEnum;
use App\Services\ExternalCalendar\AppleCalDavExternalCalendarService;
use App\Services\ExternalCalendar\GoogleAppExternalCalendarService;
use App\Services\ExternalCalendar\GoogleExternalCalendarService;
use App\Services\ExternalCalendar\OutlookExternalCalendarService;

mutates(CalendarProviderEnum::class);

describe('CalendarProviderEnum', function (): void {
    describe('values()', function (): void {
        it('returns all string values', function (): void {
            $values = CalendarProviderEnum::values();

            expect($values)
                ->toContain('google')
                ->toContain('google_personal_app')
                ->toContain('outlook')
                ->toContain('apple')
                ->toHaveCount(4);
        });

        it('contains no duplicate values', function (): void {
            $values = CalendarProviderEnum::values();

            expect(array_unique($values))->toHaveCount(count($values));
        });
    });

    describe('isValid()', function (): void {
        it('returns true for known provider values', function (): void {
            expect(CalendarProviderEnum::isValid('google'))->toBeTrue()
                ->and(CalendarProviderEnum::isValid('outlook'))->toBeTrue()
                ->and(CalendarProviderEnum::isValid('apple'))->toBeTrue()
                ->and(CalendarProviderEnum::isValid('google_personal_app'))->toBeTrue();
        });

        it('returns false for unknown provider values', function (): void {
            expect(CalendarProviderEnum::isValid('unknown'))->toBeFalse()
                ->and(CalendarProviderEnum::isValid(''))->toBeFalse()
                ->and(CalendarProviderEnum::isValid('GOOGLE'))->toBeFalse();
        });
    });

    describe('getService()', function (): void {
        it('maps each provider to the correct service class', function (): void {
            expect(CalendarProviderEnum::Google->getService())->toBe(GoogleAppExternalCalendarService::class)
                ->and(CalendarProviderEnum::GooglePersonalApp->getService())->toBe(GoogleExternalCalendarService::class)
                ->and(CalendarProviderEnum::Outlook->getService())->toBe(OutlookExternalCalendarService::class)
                ->and(CalendarProviderEnum::Apple->getService())->toBe(AppleCalDavExternalCalendarService::class);
        });
    });

    describe('usesAppCredentials()', function (): void {
        it('returns true for providers using shared app credentials', function (): void {
            expect(CalendarProviderEnum::Google->usesAppCredentials())->toBeTrue()
                ->and(CalendarProviderEnum::Outlook->usesAppCredentials())->toBeTrue();
        });

        it('returns false for providers using per-user credentials', function (): void {
            expect(CalendarProviderEnum::GooglePersonalApp->usesAppCredentials())->toBeFalse()
                ->and(CalendarProviderEnum::Apple->usesAppCredentials())->toBeFalse();
        });
    });

    describe('isCalDav()', function (): void {
        it('returns true only for the Apple provider', function (): void {
            expect(CalendarProviderEnum::Apple->isCalDav())->toBeTrue();
        });

        it('returns false for all OAuth providers', function (): void {
            expect(CalendarProviderEnum::Google->isCalDav())->toBeFalse()
                ->and(CalendarProviderEnum::GooglePersonalApp->isCalDav())->toBeFalse()
                ->and(CalendarProviderEnum::Outlook->isCalDav())->toBeFalse();
        });
    });

    describe('label()', function (): void {
        it('returns the human-readable label for each provider', function (): void {
            expect(CalendarProviderEnum::Google->label())->toBe('Google Calendar')
                ->and(CalendarProviderEnum::GooglePersonalApp->label())->toBe('Google Calendar (personal)')
                ->and(CalendarProviderEnum::Outlook->label())->toBe('Outlook Calendar')
                ->and(CalendarProviderEnum::Apple->label())->toBe('Apple Calendar');
        });

        it('returns a non-empty label for every case', function (): void {
            foreach (CalendarProviderEnum::cases() as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
            }
        });
    });
});
