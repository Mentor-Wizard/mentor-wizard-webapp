<?php

declare(strict_types=1);

use App\Actions\Pages\DashboardPage;
use App\Actions\Pages\MentorProgram\CreateMentorProgramPage;
use App\Actions\MentorPrograms\DeleteMentorProgramPage;
use App\Actions\Pages\MentorProgram\EditMentorProgramPage;
use App\Actions\MentorPrograms\StoreMentorProgramPage;
use App\Actions\MentorPrograms\UpdateMentorProgramPage;
use App\Actions\Pages\Profile\GetProfilePage;
use App\Actions\Pages\WelcomePage;
use App\Actions\Profile\DeleteUserProfile;
use App\Actions\Profile\UpdateUserProfile;
use App\Actions\User\UpdateUser;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomePage::class)->name('pages.welcome');

Route::get('dashboard', DashboardPage::class)
    ->middleware(['auth', 'verified'])
    ->name('pages.dashboard');

Route::middleware('auth')->group(function (): void {
    Route::patch('user', UpdateUser::class)->name('user.update');
    Route::get('profile', GetProfilePage::class)->name('profile.edit');
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
        ->name('mentor-program.update');
    Route::delete('mentor-program/{mentorProgram:slug}', DeleteMentorProgramPage::class)->name('mentor-program.destroy');
});

require __DIR__.'/auth.php';
