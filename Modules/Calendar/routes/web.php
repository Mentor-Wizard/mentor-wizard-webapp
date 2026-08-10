<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Calendar\Actions\CalendarEvent\ConfirmCalendarEvent;
use Modules\Calendar\Actions\CalendarEvent\DeleteCalendarEvent;
use Modules\Calendar\Actions\CalendarEvent\EditCalendarEvent;
use Modules\Calendar\Actions\CalendarEvent\StoreCalendarEvent;
use Modules\Calendar\Actions\Pages\CalendarsListPage;
use Modules\Calendar\Actions\Pages\ConfirmedCalendarEventsListPage;
use Modules\Calendar\Actions\Pages\MentorProgramEventBookingPage;
use Modules\Calendar\Actions\Pages\PendingCalendarEventsListPage;
use Modules\Calendar\Actions\Pages\ShowCalendarEventPage;

Route::prefix('calendar')->middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/', CalendarsListPage::class)
        ->name('pages.calendar.index');
    Route::get('calendar-event/{calendarEvent:id}', ShowCalendarEventPage::class)
        ->can('view', 'calendarEvent')
        ->name('pages.calendar.show');
    Route::get('pending-calendar-event/list/{mentorProgram:slug?}', PendingCalendarEventsListPage::class)
        ->name('pages.calendar.pending');
    Route::get('confirmed-calendar-event/list/{mentorProgram:slug?}', ConfirmedCalendarEventsListPage::class)
        ->name('pages.calendar.confirmed');
    Route::get('mentor-program/book/{mentorProgram:slug}', MentorProgramEventBookingPage::class)
        ->name('pages.mentor.program.book');
    Route::post('calendar-event/store', StoreCalendarEvent::class)
        ->name('pages.calendar.store');
    Route::patch('calendar-event/edit/{calendarEvent:id}', EditCalendarEvent::class)
        ->can('update', 'calendarEvent')
        ->name('pages.calendar.edit');
    Route::patch('mentor-programs/{mentorProgram:id}/calendar-events/{calendarEvent:id}/confirm',
        ConfirmCalendarEvent::class)
        ->can('confirm', 'calendarEvent')
        ->name('calendar.confirm.booking');
    Route::delete('calendar-event/delete/{calendarEvent}', DeleteCalendarEvent::class)
        ->can('delete', 'calendarEvent')
        ->name('pages.calendar.delete');

});
