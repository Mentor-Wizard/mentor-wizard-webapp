<?php

declare(strict_types=1);

use App\Actions\Auth\ConfirmPassword;
use App\Actions\Auth\GetConfirmPasswordPage;
use App\Actions\Auth\Login\GetLoginPage;
use App\Actions\Auth\Login\Login;
use App\Actions\Auth\Logout;
use App\Actions\Auth\Register\GetRegistrationPage;
use App\Actions\Auth\Register\Registration;
use App\Actions\Auth\Reset\CreatePassword;
use App\Actions\Auth\Reset\GetCreatePasswordPage;
use App\Actions\Auth\Reset\GetResetPasswordPage;
use App\Actions\Auth\Reset\ResetPassword;
use App\Actions\Auth\Socialite\SocialiteCallback;
use App\Actions\Auth\Socialite\SocialiteRedirect;
use App\Actions\Auth\UpdatePassword;
use App\Actions\Auth\VerificationEmailNotification;
use App\Actions\Auth\VerificationEmailPrompt;
use App\Actions\Auth\VerifyEmail;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {

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

    Route::prefix('auth')->group(function () {
        Route::get('redirect/{driver}', SocialiteRedirect::class)->name('auth.socialite.redirect');
        Route::get('callback/{driver}', SocialiteCallback::class)->name('auth.socialite.callback');
    });
});

Route::middleware('auth')->group(function () {

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
