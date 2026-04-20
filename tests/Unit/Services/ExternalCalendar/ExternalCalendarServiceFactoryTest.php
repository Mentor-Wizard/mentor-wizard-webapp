<?php

declare(strict_types=1);

use App\Enums\CalendarProviderEnum;
use App\Services\ExternalCalendar\AppleCalDavExternalCalendarService;
use App\Services\ExternalCalendar\ExternalCalendarServiceFactory;
use App\Services\ExternalCalendar\GoogleAppExternalCalendarService;
use App\Services\ExternalCalendar\GoogleExternalCalendarService;
use App\Services\ExternalCalendar\OutlookExternalCalendarService;

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
        expect($this->factory->for(CalendarProviderEnum::Google))
            ->toBe($this->googleApp);
    });

    it('returns GoogleExternalCalendarService for GooglePersonalApp provider', function (): void {
        expect($this->factory->for(CalendarProviderEnum::GooglePersonalApp))
            ->toBe($this->googlePersonalApp);
    });

    it('returns OutlookExternalCalendarService for Outlook provider', function (): void {
        expect($this->factory->for(CalendarProviderEnum::Outlook))
            ->toBe($this->outlook);
    });

    it('returns AppleCalDavExternalCalendarService for Apple provider', function (): void {
        expect($this->factory->for(CalendarProviderEnum::Apple))
            ->toBe($this->apple);
    });

    it('does not return the same service for different providers', function (): void {
        expect($this->factory->for(CalendarProviderEnum::Google))
            ->not->toBe($this->factory->for(CalendarProviderEnum::Outlook))
            ->and($this->factory->for(CalendarProviderEnum::GooglePersonalApp))
            ->not->toBe($this->factory->for(CalendarProviderEnum::Apple));
    });
});
