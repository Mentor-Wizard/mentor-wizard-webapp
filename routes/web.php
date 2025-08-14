<?php

declare(strict_types=1);

use App\Actions\Calendar\DeleteCalendarPage;
use App\Actions\Calendar\EditCalendarPage;
use App\Actions\Calendar\StoreCalendarPage;
use App\Actions\MentorPrograms\DeleteMentorProgram;
use App\Actions\MentorPrograms\StoreMentorProgramPage;
use App\Actions\MentorPrograms\UpdateMentorProgramPage;
use App\Actions\Pages\Calendar\CalendarsListPage;
use App\Actions\Pages\Calendar\CreateCalendarPage;
use App\Actions\Pages\Calendar\ShowCalendarEventPage;
use App\Actions\Pages\DashboardPage;
use App\Actions\Pages\MentorProgram\CreateMentorProgramPage;
use App\Actions\Pages\MentorProgram\EditMentorProgramPage;
use App\Actions\Pages\MentorProgram\ListMentorProgramPage;
use App\Actions\Pages\Profile\GetMentorProfilePage;
use App\Actions\Pages\Profile\GetProfilePage;
use App\Actions\Pages\WelcomePage;
use App\Actions\Profile\DeleteUserProfile;
use App\Actions\Profile\UpdateUserProfile;
use App\Actions\User\UpdateUser;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomePage::class)->name('pages.welcome');


Route::get('mentor/{user:slug}', GetMentorProfilePage::class)->name('page.mentor');

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

Route::middleware(['auth', 'role:mentor'])->group(function (): void {
    Route::get('mentor-program/create', CreateMentorProgramPage::class)
        ->name('mentor-program.create');
    Route::post('mentor-program', StoreMentorProgramPage::class)->name('mentor-program.store');
    Route::get('mentor-program/{mentorProgram:slug}/edit', EditMentorProgramPage::class)
        ->name('mentor-program.edit');
    Route::patch('mentor-program/{mentorProgram:slug}', UpdateMentorProgramPage::class)
        ->can('update', 'mentorProgram')
        ->name('mentor-program.update');
    Route::delete('mentor-program/{mentorProgram:slug}', DeleteMentorProgram::class)
        ->can('delete', 'mentorProgram')
        ->name('mentor-program.destroy');
    Route::get('mentor-program/list', ListMentorProgramPage::class)
        ->name('mentor-program.list');
});

Route::get('calendar', CalendarsListPage::class)
    ->middleware(['auth', 'verified'])
    ->name('pages.calendar');
Route::get('calendar/event/{id}', ShowCalendarEventPage::class)
    ->middleware(['auth', 'verified'])
    ->name('pages.calendar.show');
Route::middleware(['auth', 'role:mentor'])->group(function (): void {
    Route::post('calendar/event/store', StoreCalendarPage::class)
        ->name('pages.calendar.store');
    Route::patch('calendar/event/edit/{id}', EditCalendarPage::class)
        ->name('pages.calendar.edit');
    Route::delete('calendar/event/delete/{id}', DeleteCalendarPage::class)
        ->name('pages.calendar.delete');
});

require __DIR__ . '/auth.php';
