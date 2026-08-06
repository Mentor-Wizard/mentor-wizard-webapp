<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\UserProfile\Actions\DeleteUserProfile;
use Modules\UserProfile\Actions\Pages\GetProfilePage;
use Modules\UserProfile\Actions\UpdateUser;
use Modules\UserProfile\Actions\UpdateUserProfile;

Route::middleware('auth')->group(function (): void {
    Route::patch('user', UpdateUser::class)->name('user.update');
    Route::get('profile', GetProfilePage::class)->name('profile.edit');
    Route::patch('profile', UpdateUserProfile::class)->name('profile.update');
    Route::delete('profile', DeleteUserProfile::class)->name('profile.destroy');
});
