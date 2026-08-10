<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\UserSchedule\Actions\Pages\UserSchedulePage;
use Modules\UserSchedule\Actions\StoreBatchUserSchedule;

Route::middleware(['auth', 'verified', 'role:mentor'])->prefix('user-schedule')->group(function (): void {
    Route::get('/', UserSchedulePage::class)->name('user-schedule.index');
    Route::post('/batch', StoreBatchUserSchedule::class)->name('user-schedule.batch');
});
