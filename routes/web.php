<?php

declare(strict_types=1);

use App\Actions\Pages\WelcomePage;
use App\Actions\Pages\DashboardPage;
use Illuminate\Support\Facades\Route;
use App\Actions\Pages\Profile\GetProfilePage;
use App\Actions\Pages\Profile\UpdateProfilePage;
use App\Actions\Pages\Profile\DestroyProfilePage;
use App\Actions\Pages\MentorProgram\EditMentorProgramAction;
use App\Actions\Pages\MentorProgram\StoreMentorProgramAction;
use App\Actions\Pages\MentorProgram\CreateMentorProgramAction;
use App\Actions\Pages\MentorProgram\UpdateMentorProgramAction;
use App\Actions\Pages\MentorProgram\DestroyMentorProgramAction;

Route::get('/', WelcomePage::class)->name('pages.welcome');

Route::get('dashboard', DashboardPage::class)
    ->middleware(['auth', 'verified'])
    ->name('pages.dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('profile', GetProfilePage::class)->name('profile.edit');
    Route::patch('profile', UpdateProfilePage::class)->name('profile.update');
    Route::delete('profile', DestroyProfilePage::class)->name('profile.destroy');
});

Route::middleware('auth')->group(function (): void {
    Route::get('mentor-program/create', CreateMentorProgramAction::class)
        ->name('mentor-program.create');
    Route::post('mentor-program', StoreMentorProgramAction::class)->name('mentor-program.store');

    Route::get('mentor-program/{mentorProgram:slug}/edit', EditMentorProgramAction::class)
        ->name('mentor-program.edit');
    Route::put('mentor-program/{mentorProgram:slug}', UpdateMentorProgramAction::class)
        ->name('mentor-program.update');
    Route::delete('mentor-program/{mentorProgram:slug}', DestroyMentorProgramAction::class)->name('mentor-program.destroy');
});

require __DIR__.'/auth.php';
