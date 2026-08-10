<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\MentorProgram\Actions\DeleteMentorProgram;
use Modules\MentorProgram\Actions\Pages\CreateMentorProgramPage;
use Modules\MentorProgram\Actions\Pages\EditMentorProgramPage;
use Modules\MentorProgram\Actions\Pages\ListMentorProgramPage;
use Modules\MentorProgram\Actions\SetMainMentorProgram;
use Modules\MentorProgram\Actions\StoreMentorProgramPage;
use Modules\MentorProgram\Actions\UpdateMentorProgramPage;
use Modules\MentorProgram\Models\MentorProgram;

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
