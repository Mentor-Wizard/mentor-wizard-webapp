<?php

declare(strict_types=1);

use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Services\AppleCalDavExternalCalendarService;
use Modules\ExternalCalendar\Services\ExternalCalendarServiceFactory;
use Modules\ExternalCalendar\Services\GoogleAppExternalCalendarService;
use Modules\ExternalCalendar\Services\GoogleExternalCalendarService;
use Modules\ExternalCalendar\Services\OutlookExternalCalendarService;

mutates(ExternalCalendarServiceFactory::class);

describe('ExternalCalendarServiceFactory', function (): void {
    beforeEach(function (): void {
        $this->googleApp = Mockery::mock(GoogleAppExternalCalendarService::class);
        $this->googlePersonalApp = Mockery::mock(GoogleExternalCalendarService::class);
        $this->outlook = Mockery::mock(OutlookExternalCalendarService::class);
        $this->apple = Mockery::mock(AppleCalDavExternalCalendarService::class);

        $this->factory = new ExternalCalendarServiceFactory(
            googleApp: $this->googleApp,
            googlePersonalApp: $this->googlePersonalApp,
            outlook: $this->outlook,
            apple: $this->apple,
        );
    });

    it('returns GoogleAppExternalCalendarService for Google provider', function (): void {
        expect($this->factory->for(CalendarProviderEnum::GOOGLE))
            ->toBe($this->googleApp);
    });

    it('returns GoogleExternalCalendarService for GooglePersonalApp provider', function (): void {
        expect($this->factory->for(CalendarProviderEnum::GOOGLE_PERSONAL_APP))
            ->toBe($this->googlePersonalApp);
    });

    it('returns OutlookExternalCalendarService for Outlook provider', function (): void {
        expect($this->factory->for(CalendarProviderEnum::OUTLOOK))
            ->toBe($this->outlook);
    });

    it('returns AppleCalDavExternalCalendarService for Apple provider', function (): void {
        expect($this->factory->for(CalendarProviderEnum::APPLE))
            ->toBe($this->apple);
    });

    it('does not return the same service for different providers', function (): void {
        expect($this->factory->for(CalendarProviderEnum::GOOGLE))
            ->not->toBe($this->factory->for(CalendarProviderEnum::OUTLOOK))
            ->and($this->factory->for(CalendarProviderEnum::GOOGLE_PERSONAL_APP))
            ->not->toBe($this->factory->for(CalendarProviderEnum::APPLE));
    });
});
