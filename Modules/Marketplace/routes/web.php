<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Actions\Pages\GetMentorProfilePage;
use Modules\Marketplace\Actions\Pages\GetMentorReviewPage;
use Modules\Marketplace\Actions\Pages\ListMentorProfilePage;

Route::get('profile-programs', ListMentorProfilePage::class)->name('page.profile-programs');

Route::get('mentor/{mentor:slug}', GetMentorProfilePage::class)->name('page.mentor');
Route::get('review/{mentor:slug}', GetMentorReviewPage::class)->name('page.mentor-review');
