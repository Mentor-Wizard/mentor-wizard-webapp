<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Actions\ConfirmPassword;
use Modules\Auth\Actions\GetConfirmPasswordPage;
use Modules\Auth\Actions\Login\GetLoginPage;
use Modules\Auth\Actions\Login\Login;
use Modules\Auth\Actions\Logout;
use Modules\Auth\Actions\Register\GetRegistrationPage;
use Modules\Auth\Actions\Register\Registration;
use Modules\Auth\Actions\Reset\CreatePassword;
use Modules\Auth\Actions\Reset\GetCreatePasswordPage;
use Modules\Auth\Actions\Reset\GetResetPasswordPage;
use Modules\Auth\Actions\Reset\ResetPassword;
use Modules\Auth\Actions\Socialite\SocialiteCallback;
use Modules\Auth\Actions\Socialite\SocialiteRedirect;
use Modules\Auth\Actions\UpdatePassword;
use Modules\Auth\Actions\VerificationEmailNotification;
use Modules\Auth\Actions\VerificationEmailPrompt;
use Modules\Auth\Actions\VerifyEmail;

Route::middleware('guest')->group(function (): void {

    Route::get('register', GetRegistrationPage::class)
        ->name('register');

    Route::post('register', Registration::class);

    Route::get('login', GetLoginPage::class)
        ->name('login');

    Route::post('login', Login::class)->name('login.attempt');

    Route::get('forgot-password', GetResetPasswordPage::class)
        ->name('password.request');

    Route::post('forgot-password', ResetPassword::class)
        ->name('password.email');

    Route::get('reset-password/{token}', GetCreatePasswordPage::class)
        ->name('password.reset');

    Route::post('reset-password', CreatePassword::class)
        ->name('password.store');

    Route::prefix('auth')->group(function (): void {
        Route::get('redirect/{driver}', SocialiteRedirect::class)->name('auth.socialite.redirect');
        Route::get('callback/{driver}', SocialiteCallback::class)->name('auth.socialite.callback');
    });
});

Route::middleware('auth')->group(function (): void {

    Route::get('verify-email', VerificationEmailPrompt::class)
        ->name('verification.notice');

    Route::post('email/verification-notification', VerificationEmailNotification::class)
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('verify-email/{id}/{hash}', VerifyEmail::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::get('confirm-password', GetConfirmPasswordPage::class)
        ->name('pages.password.confirm');

    Route::post('confirm-password', ConfirmPassword::class)->name('password.confirm');

    Route::put('password', UpdatePassword::class)
        ->name('password.update');

    Route::post('logout', Logout::class)
        ->name('logout');
});
