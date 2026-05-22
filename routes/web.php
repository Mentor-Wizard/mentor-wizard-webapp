<?php

declare(strict_types=1);

use App\Actions\Calendar\CalendarEvent\ConfirmCalendarEvent;
use App\Actions\Calendar\CalendarEvent\DeleteCalendarEvent;
use App\Actions\Calendar\CalendarEvent\EditCalendarEvent;
use App\Actions\Calendar\CalendarEvent\StoreCalendarEvent;
use App\Actions\Calendar\CalendarEvent\SyncCalendarEventToIntegration;
use App\Actions\Calendar\ExternalCalendar\ExternalCalendarConnectCallback;
use App\Actions\Calendar\ExternalCalendar\ExternalCalendarConnectDirect;
use App\Actions\Calendar\ExternalCalendar\ExternalCalendarConnectRedirect;
use App\Actions\Calendar\ExternalCalendar\ExternalCalendarDisconnect;
use App\Actions\Calendar\ExternalCalendar\ExternalCalendarRetrySync;
use App\Actions\Calendar\ExternalCalendar\ExternalCalendarSelectCalendar;
use App\Actions\Calendar\ExternalCalendar\ExternalCalendarSyncSingleEvent;
use App\Actions\Calendar\ExternalCalendar\RerunExternalCalendarEventSync;
use App\Actions\Calendar\ExternalCalendarLog\AcknowledgeExternalCalendarEventLog;
use App\Actions\Chat\ChatListUser;
use App\Actions\Chat\ChatMessages;
use App\Actions\Chat\CreateChat;
use App\Actions\Chat\DownloadChatFile;
use App\Actions\Chat\GetMessage;
use App\Actions\Chat\SendMessage;
use App\Actions\Chat\SetArchive;
use App\Actions\Chat\SetBan;
use App\Actions\Chat\SetMute;
use App\Actions\MentorPrograms\DeleteMentorProgram;
use App\Actions\MentorPrograms\SetMainMentorProgram;
use App\Actions\MentorPrograms\StoreMentorProgramPage;
use App\Actions\MentorPrograms\UpdateMentorProgramPage;
use App\Actions\Notifications\ListNotifications;
use App\Actions\Notifications\MarkAllNotificationsAsRead;
use App\Actions\Notifications\MarkNotificationAsRead;
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
use App\Actions\Pages\Profile\ExternalCalendarSettingsPage;
use App\Actions\Pages\Profile\GetMentorProfilePage;
use App\Actions\Pages\Profile\GetMentorReviewPage;
use App\Actions\Pages\Profile\GetProfilePage;
use App\Actions\Pages\Profile\ListMentorProfilePage;
use App\Actions\Pages\UserSchedule\UserSchedulePage;
use App\Actions\Pages\WelcomePage;
use App\Actions\Profile\DeleteUserProfile;
use App\Actions\Profile\UpdateUserProfile;
use App\Actions\User\UpdateUser;
use App\Actions\UserSchedule\StoreBatchUserSchedule;
use App\Models\MentorProgram;
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
        ->can('create', MentorProgram::class)
        ->name('mentor-program.create');
    Route::post('/', StoreMentorProgramPage::class)
        ->can('create', MentorProgram::class)
        ->name('mentor-program.store');
    Route::get('/{mentorProgram:slug}/edit', EditMentorProgramPage::class)
        ->can('update', 'mentorProgram')
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
        ->can('confirm', 'calendarEvent')
        ->name('calendar.confirm.booking');
    Route::delete('calendar-event/delete/{calendarEvent}', DeleteCalendarEvent::class)
        ->can('delete', 'calendarEvent')
        ->name('pages.calendar.delete');

});

Route::middleware('auth')
    ->prefix('chat')
    ->group(function (): void {
        Route::get('list', GetChatPage::class)->name('page.chat');
        Route::get('users', ChatListUser::class)->name('chat.users');
        Route::get('messages/{chat}', ChatMessages::class)->name('chat.messages')->can('view,chat');
        Route::post('message/{chat}', SendMessage::class)->middleware('throttle:chat-send')->name('chat.send-message')->can('update,chat');
        Route::get('message/{message}', GetMessage::class)->name('chat.get-message')->can('view,message');
        Route::get('message/{message}/download/{media}', DownloadChatFile::class)->can('view,message')->name('chat.message.download');
        Route::post('mute/{chat}', SetMute::class)->middleware('throttle:chat-send')->name('chat.set-mute')->can('update,chat');
        Route::post('create/{user}', CreateChat::class)->middleware('throttle:chat-create')->name('chat.create');
        Route::post('archive/{chat}', SetArchive::class)->middleware('throttle:chat-send')->name('chat.set-archive')->can('update,chat');
        Route::post('ban/{chat}', SetBan::class)->middleware('throttle:chat-send')->name('chat.set-ban')->can('update,chat');
    });

Route::middleware(['auth', 'verified', 'role:mentor'])->prefix('user-schedule')->group(function (): void {
    Route::get('/', UserSchedulePage::class)->name('user-schedule.index');
    Route::post('/batch', StoreBatchUserSchedule::class)->name('user-schedule.batch');
});

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
        ->withoutScopedBindings();
    Route::post('sync-integration/{calendarEvent:id}/{integration:id}', SyncCalendarEventToIntegration::class)
        ->middleware('throttle:calendar-sync')
        ->name('external-calendar.sync-integration')
        ->can('sync', 'integration')
        ->withoutScopedBindings();
    Route::patch('log/{log:id}/acknowledge', AcknowledgeExternalCalendarEventLog::class)
        ->name('external-calendar.log.acknowledge')
        ->middleware('can:acknowledge,log');
});

Route::get('settings/external-calendar/callback/{provider}', ExternalCalendarConnectCallback::class)
    ->name('external-calendar.connect.callback');

Route::middleware(['auth', 'verified'])->prefix('notifications')->group(function (): void {
    Route::get('/', ListNotifications::class)->name('notifications.index');
    Route::post('{id}/read', MarkNotificationAsRead::class)->name('notifications.read');
    Route::post('read-all', MarkAllNotificationsAsRead::class)->name('notifications.read-all');
});

require __DIR__.'/auth.php';
