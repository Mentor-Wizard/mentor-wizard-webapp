<?php

declare(strict_types=1);

use App\Actions\MentorPrograms\DeleteMentorProgram;
use App\Actions\MentorPrograms\SetMainMentorProgram;
use App\Actions\MentorPrograms\StoreMentorProgramPage;
use App\Actions\MentorPrograms\UpdateMentorProgramPage;
use App\Actions\Notifications\ListNotifications;
use App\Actions\Notifications\MarkAllNotificationsAsRead;
use App\Actions\Notifications\MarkNotificationAsRead;
use App\Actions\Pages\DashboardPage;
use App\Actions\Pages\MentorProgram\CreateMentorProgramPage;
use App\Actions\Pages\MentorProgram\EditMentorProgramPage;
use App\Actions\Pages\MentorProgram\ListMentorProgramPage;
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

Route::middleware(['auth', 'verified', 'role:mentor'])->prefix('user-schedule')->group(function (): void {
    Route::get('/', UserSchedulePage::class)->name('user-schedule.index');
    Route::post('/batch', StoreBatchUserSchedule::class)->name('user-schedule.batch');
});

Route::middleware(['auth', 'verified'])->prefix('notifications')->group(function (): void {
    Route::get('/', ListNotifications::class)->name('notifications.index');
    Route::post('{id}/read', MarkNotificationAsRead::class)->name('notifications.read');
    Route::post('read-all', MarkAllNotificationsAsRead::class)->name('notifications.read-all');
});
