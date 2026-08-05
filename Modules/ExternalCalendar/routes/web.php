<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ExternalCalendar\Actions\ExternalCalendar\ExternalCalendarConnectCallback;
use Modules\ExternalCalendar\Actions\ExternalCalendar\ExternalCalendarConnectDirect;
use Modules\ExternalCalendar\Actions\ExternalCalendar\ExternalCalendarConnectRedirect;
use Modules\ExternalCalendar\Actions\ExternalCalendar\ExternalCalendarDisconnect;
use Modules\ExternalCalendar\Actions\ExternalCalendar\ExternalCalendarRetrySync;
use Modules\ExternalCalendar\Actions\ExternalCalendar\ExternalCalendarSelectCalendar;
use Modules\ExternalCalendar\Actions\ExternalCalendar\ExternalCalendarSyncSingleEvent;
use Modules\ExternalCalendar\Actions\ExternalCalendar\RerunExternalCalendarEventSync;
use Modules\ExternalCalendar\Actions\ExternalCalendar\SyncCalendarEventToIntegration;
use Modules\ExternalCalendar\Actions\ExternalCalendarLog\AcknowledgeExternalCalendarEventLog;
use Modules\ExternalCalendar\Actions\Pages\ExternalCalendarSettingsPage;

Route::middleware(['auth', 'verified'])->prefix('settings/external-calendar')->group(function (): void {
    Route::get('/', ExternalCalendarSettingsPage::class)
        ->name('pages.settings.external-calendar');
    Route::post('connect/{provider}', ExternalCalendarConnectRedirect::class)
        ->middleware('throttle:calendar-connect')
        ->name('external-calendar.connect.redirect');
    Route::post('connect-direct/{provider}', ExternalCalendarConnectDirect::class)
        ->middleware('throttle:calendar-connect')
        ->name('external-calendar.connect.direct');
    Route::post('select/{provider}', ExternalCalendarSelectCalendar::class)
        ->middleware('throttle:calendar-connect')
        ->name('external-calendar.select');
    Route::delete('disconnect/{provider}', ExternalCalendarDisconnect::class)
        ->middleware('throttle:calendar-connect')
        ->name('external-calendar.disconnect');
    Route::post('retry/{provider}', ExternalCalendarRetrySync::class)
        ->middleware('throttle:calendar-retry')
        ->name('external-calendar.retry');
    Route::post('sync-event/{calendarEvent}/{provider}', ExternalCalendarSyncSingleEvent::class)
        ->middleware('throttle:calendar-sync')
        ->name('external-calendar.sync-event');
    Route::post('rerun/{calendarEvent:id}/{externalCalendarEvent:id}', RerunExternalCalendarEventSync::class)
        ->middleware('throttle:calendar-retry')
        ->name('external-calendar.rerun')
        ->can('sync', 'externalCalendarEvent')
        ->can('view', 'calendarEvent')
        ->withoutScopedBindings();
    Route::post('sync-integration/{calendarEvent:id}/{integration:id}', SyncCalendarEventToIntegration::class)
        ->middleware('throttle:calendar-sync')
        ->name('external-calendar.sync-integration')
        ->can('sync', 'integration')
        ->can('view', 'calendarEvent')
        ->withoutScopedBindings();
    Route::patch('log/{log:id}/acknowledge', AcknowledgeExternalCalendarEventLog::class)
        ->name('external-calendar.log.acknowledge')
        ->middleware('can:acknowledge,log');
});

Route::get('settings/external-calendar/callback/{provider}', ExternalCalendarConnectCallback::class)
    ->middleware('throttle:20,1')
    ->name('external-calendar.connect.callback');
