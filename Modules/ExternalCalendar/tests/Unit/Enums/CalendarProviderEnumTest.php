<?php

declare(strict_types=1);

use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Services\AppleCalDavExternalCalendarService;
use Modules\ExternalCalendar\Services\GoogleAppExternalCalendarService;
use Modules\ExternalCalendar\Services\GoogleExternalCalendarService;
use Modules\ExternalCalendar\Services\OutlookExternalCalendarService;

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

            expect(array_unique($values))->toHaveSameSize($values);
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
            expect(CalendarProviderEnum::GOOGLE->getService())->toBe(GoogleAppExternalCalendarService::class)
                ->and(CalendarProviderEnum::GOOGLE_PERSONAL_APP->getService())->toBe(GoogleExternalCalendarService::class)
                ->and(CalendarProviderEnum::OUTLOOK->getService())->toBe(OutlookExternalCalendarService::class)
                ->and(CalendarProviderEnum::APPLE->getService())->toBe(AppleCalDavExternalCalendarService::class);
        });
    });

    describe('usesAppCredentials()', function (): void {
        it('returns true for providers using shared app credentials', function (): void {
            expect(CalendarProviderEnum::GOOGLE->usesAppCredentials())->toBeTrue()
                ->and(CalendarProviderEnum::OUTLOOK->usesAppCredentials())->toBeTrue();
        });

        it('returns false for providers using per-user credentials', function (): void {
            expect(CalendarProviderEnum::GOOGLE_PERSONAL_APP->usesAppCredentials())->toBeFalse()
                ->and(CalendarProviderEnum::APPLE->usesAppCredentials())->toBeFalse();
        });
    });

    describe('isCalDav()', function (): void {
        it('returns true only for the Apple provider', function (): void {
            expect(CalendarProviderEnum::APPLE->isCalDav())->toBeTrue();
        });

        it('returns false for all OAuth providers', function (): void {
            expect(CalendarProviderEnum::GOOGLE->isCalDav())->toBeFalse()
                ->and(CalendarProviderEnum::GOOGLE_PERSONAL_APP->isCalDav())->toBeFalse()
                ->and(CalendarProviderEnum::OUTLOOK->isCalDav())->toBeFalse();
        });
    });

    describe('label()', function (): void {
        it('returns the human-readable label for each provider', function (): void {
            expect(CalendarProviderEnum::GOOGLE->label())->toBe('Google Calendar')
                ->and(CalendarProviderEnum::GOOGLE_PERSONAL_APP->label())->toBe('Google Calendar (personal)')
                ->and(CalendarProviderEnum::OUTLOOK->label())->toBe('Outlook Calendar')
                ->and(CalendarProviderEnum::APPLE->label())->toBe('Apple Calendar');
        });

        it('returns a non-empty label for every case', function (): void {
            foreach (CalendarProviderEnum::cases() as $case) {
                expect($case->label())->toBeString()->not->toBeEmpty();
            }
        });
    });
});
