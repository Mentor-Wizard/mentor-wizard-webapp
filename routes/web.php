<?php

declare(strict_types=1);

use App\Actions\Notifications\ListNotifications;
use App\Actions\Notifications\MarkAllNotificationsAsRead;
use App\Actions\Notifications\MarkNotificationAsRead;
use App\Actions\Pages\DashboardPage;
use App\Actions\Pages\UserSchedule\UserSchedulePage;
use App\Actions\Pages\WelcomePage;
use App\Actions\UserSchedule\StoreBatchUserSchedule;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomePage::class)->name('pages.welcome');

Route::get('dashboard', DashboardPage::class)
    ->middleware(['auth', 'verified'])
    ->name('pages.dashboard');

Route::middleware(['auth', 'verified', 'role:mentor'])->prefix('user-schedule')->group(function (): void {
    Route::get('/', UserSchedulePage::class)->name('user-schedule.index');
    Route::post('/batch', StoreBatchUserSchedule::class)->name('user-schedule.batch');
});

Route::middleware(['auth', 'verified'])->prefix('notifications')->group(function (): void {
    Route::get('/', ListNotifications::class)->name('notifications.index');
    Route::post('{id}/read', MarkNotificationAsRead::class)->name('notifications.read');
    Route::post('read-all', MarkAllNotificationsAsRead::class)->name('notifications.read-all');
});
