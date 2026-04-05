<?php

declare(strict_types=1);

use App\Actions\Calendar\ConfirmCalendarEvent;
use App\Actions\Calendar\DeleteCalendarEvent;
use App\Actions\Calendar\EditCalendarEvent;
use App\Actions\Calendar\ExternalCalendarConnectCallback;
use App\Actions\Calendar\ExternalCalendarConnectRedirect;
use App\Actions\Calendar\ExternalCalendarDisconnect;
use App\Actions\Calendar\ExternalCalendarSelectCalendar;
use App\Actions\Calendar\StoreCalendarEvent;
use App\Actions\MentorPrograms\DeleteMentorProgram;
use App\Actions\MentorPrograms\SetMainMentorProgram;
use App\Actions\MentorPrograms\StoreMentorProgramPage;
use App\Actions\MentorPrograms\UpdateMentorProgramPage;
use App\Actions\Pages\Calendar\CalendarsListPage;
use App\Actions\Pages\Calendar\ConfirmedCalendarEventsListPage;
use App\Actions\Pages\Calendar\MentorProgramEventBookingPage;
use App\Actions\Pages\Calendar\PendingCalendarEventsListPage;
use App\Actions\Pages\Calendar\ShowCalendarEventPage;
use App\Actions\Pages\Chat\GetChatPage;
use App\Actions\Pages\DashboardPage;
use App\Actions\Pages\MentorProgram\CreateMentorProgramPage;
use App\Actions\Pages\MentorProgram\EditMentorProgramPage;
use App\Actions\Pages\MentorProgram\ListMentorProgramPage;
use App\Actions\Pages\Profile\GetMentorProfilePage;
use App\Actions\Pages\Profile\GetMentorReviewPage;
use App\Actions\Pages\Profile\GetProfilePage;
use App\Actions\Pages\Profile\ListMentorProfilePage;
use App\Actions\Pages\Settings\ExternalCalendarSettingsPage;
use App\Actions\Pages\UserSchedule\UserSchedulePage;
use App\Actions\Pages\WelcomePage;
use App\Actions\Profile\DeleteUserProfile;
use App\Actions\Profile\UpdateUserProfile;
use App\Actions\User\UpdateUser;
use App\Actions\UserSchedule\StoreBatchUserSchedule;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomePage::class)->name('pages.welcome');

Route::get('profile-programs', ListMentorProfilePage::class)->name('page.profile-programs');

Route::get('mentor/{mentor:slug}', GetMentorProfilePage::class)->name('page.mentor');
Route::get('review/{mentor:slug}', GetMentorReviewPage::class)->name('page.mentor-review');

Route::get('dashboard', DashboardPage::class)
    ->middleware(['auth', 'verified'])
    ->name('pages.dashboard');

Route::middleware('auth')->group(function (): void {
    Route::patch('user', UpdateUser::class)->name('user.update');
    Route::get('profile', GetProfilePage::class)->name('profile.edit');
    Route::patch('profile', UpdateUserProfile::class)->name('profile.update');
    Route::delete('profile', DeleteUserProfile::class)->name('profile.destroy');
    Route::patch('profile', UpdateUserProfile::class)->name('profile.update');
    Route::delete('profile', DeleteUserProfile::class)->name('profile.destroy');
});

Route::prefix('mentor-program')->middleware(['auth', 'role:mentor'])->group(function (): void {
    Route::get('/create', CreateMentorProgramPage::class)
        ->name('mentor-program.create');
    Route::post('/', StoreMentorProgramPage::class)->name('mentor-program.store');
    Route::get('/{mentorProgram:slug}/edit', EditMentorProgramPage::class)
        ->name('mentor-program.edit');
    Route::patch('/{mentorProgram:slug}', UpdateMentorProgramPage::class)
        ->can('update', 'mentorProgram')
        ->name('mentor-program.update');
    Route::patch('/{mentorProgram:slug}/set-main', SetMainMentorProgram::class)
        ->can('update', 'mentorProgram')
        ->name('mentor-program.set-main');
    Route::delete('/{mentorProgram:slug}', DeleteMentorProgram::class)
        ->can('delete', 'mentorProgram')
        ->name('mentor-program.destroy');
    Route::get('/list', ListMentorProgramPage::class)
        ->name('mentor-program.list');
});

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
        ->can('update', 'calendarEvent')
        ->name('calendar.confirm.booking');
    Route::delete('calendar-event/delete/{calendarEvent}', DeleteCalendarEvent::class)
        ->can('delete', 'calendarEvent')
        ->name('pages.calendar.delete');

});

Route::middleware('auth')
    ->prefix('chat')
    ->group(function (): void {
        Route::get('list', GetChatPage::class)->name('page.chat.list');
    });

Route::middleware(['auth', 'verified', 'role:mentor'])->prefix('user-schedule')->group(function (): void {
    Route::get('/', UserSchedulePage::class)->name('user-schedule.index');
    Route::post('/batch', StoreBatchUserSchedule::class)->name('user-schedule.batch');
});

Route::middleware(['auth', 'verified'])->prefix('settings/external-calendar')->group(function (): void {
    Route::get('/', ExternalCalendarSettingsPage::class)
        ->name('pages.settings.external-calendar');
    Route::post('connect/{provider}', ExternalCalendarConnectRedirect::class)
        ->name('external-calendar.connect.redirect');
    Route::post('select/{provider}', ExternalCalendarSelectCalendar::class)
        ->name('external-calendar.select');
    Route::delete('disconnect/{provider}', ExternalCalendarDisconnect::class)
        ->name('external-calendar.disconnect');
});

// Callback is outside auth middleware — user is identified via encrypted state param
Route::get('settings/external-calendar/callback/{provider}', ExternalCalendarConnectCallback::class)
    ->name('external-calendar.connect.callback');

require __DIR__.'/auth.php';
