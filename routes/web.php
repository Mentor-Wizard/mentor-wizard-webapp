<?php

declare(strict_types=1);

use App\Actions\Pages\DashboardPage;
use App\Actions\Pages\Profile\DestroyProfilePage;
use App\Actions\Pages\Profile\GetProfilePage;
use App\Actions\Pages\Profile\UpdateProfileInfoPage;
use App\Actions\Pages\Profile\UpdateProfileMainPage;
use App\Actions\Pages\WelcomePage;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomePage::class)->name('pages.welcome');

Route::get('dashboard', DashboardPage::class)
    ->middleware(['auth', 'verified'])
    ->name('pages.dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('profile', GetProfilePage::class)->name('profile.edit');
    Route::patch('profile-main', UpdateProfileMainPage::class)->name('profile.update-main');
    Route::patch('profile-info', UpdateProfileInfoPage::class)->name('profile.update-info');
    Route::delete('profile', DestroyProfilePage::class)->name('profile.destroy');
});

require __DIR__.'/auth.php';
