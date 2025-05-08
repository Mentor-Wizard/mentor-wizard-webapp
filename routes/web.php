<?php

declare(strict_types=1);

use App\Actions\Pages\DashboardPage;
use App\Actions\Pages\Profile\GetProfilePage;
use App\Actions\Pages\WelcomePage;
use app\Actions\Profile\DestroyProfile;
use app\Actions\Profile\UpdateProfile;
use app\Actions\User\UpdateUser;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomePage::class)->name('pages.welcome');

Route::get('dashboard', DashboardPage::class)
    ->middleware(['auth', 'verified'])
    ->name('pages.dashboard');

Route::middleware('auth')->group(function (): void {
    Route::patch('user', UpdateUser::class)->name('user.update');
    Route::get('profile', GetProfilePage::class)->name('profile.edit');
    Route::patch('profile', UpdateProfile::class)->name('profile.update');
    Route::delete('profile', DestroyProfile::class)->name('profile.destroy');
});

require __DIR__.'/auth.php';
