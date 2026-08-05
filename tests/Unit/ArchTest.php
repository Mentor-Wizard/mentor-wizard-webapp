<?php

declare(strict_types=1);

use Database\Seeders\MentorTagSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

arch()->preset()->php()->ignoring(
    MentorTagSeeder::class, // Include suspicious characters.
);
arch()->preset()->security()->ignoring(['md5', 'sha1']);

arch()
    ->expect('App')
    ->not->toUse(['die', 'dd', 'dump', 'var_dump']);

arch('globals')
    ->expect(['dd', 'dump', 'var_dump'])
    ->not->toBeUsed();

arch()
    ->expect('App\Models')
    ->toBeClasses()
    ->toExtend(Model::class);

arch('app')
    ->expect('App\Enums')
    ->toBeEnums()
    ->and('App\Actions\Pages')
    ->toHaveSuffix('Page')
    ->and('App\Actions\Pages\Profile')
    ->toHaveSuffix('Page');

// Module tooling guard (DDD-migration Phase 0, docs/plans/ddd-migration-laravel-modules).
arch('modules-strict-types')
    ->expect('Modules')
    ->toUseStrictTypes();

// DDD-migration Phase 1 (Chat pilot): closes the AC-19 gap the vacuous Phase-0 guard left
// open — an arch expression on an empty namespace stays green, so a module that silently
// lost all its classes (e.g. a botched `git mv`) would not fail this suite without an
// explicit non-emptiness assertion. `arch()->toHaveCount()` is not available for a class
// set in this Pest/PHPStan-arch version, so the non-emptiness guard uses `File::allFiles()`
// directly (not `glob()` — PHP's built-in `glob()` does not support recursive `**` and
// would silently under-count).
it('keeps Modules\Chat non-empty', function (): void {
    expect(File::allFiles(base_path('Modules/Chat/app')))->not->toBeEmpty();
});

arch('modules-models-are-eloquent')
    ->expect('Modules\Chat\Models')
    ->toBeClasses()
    ->toExtend(Model::class);

arch('chat-does-not-reach-into-other-modules')
    ->expect('Modules\Chat')
    ->not->toUse(['Modules\Calendar', 'Modules\ExternalCalendar', 'Modules\MentorProgram', 'Modules\MentorProfile', 'Modules\UserSchedule']);

// DDD-migration Phase 2 (Calendar extraction, docs/plans/migrate-calendar-domain-module).
it('keeps Modules\Calendar non-empty', function (): void {
    expect(File::allFiles(base_path('Modules/Calendar/app')))->not->toBeEmpty();
});

arch('calendar-models-are-eloquent')
    ->expect('Modules\Calendar\Models')
    ->toBeClasses()
    ->toExtend(Model::class);

arch('calendar-does-not-reach-into-other-modules')
    ->expect('Modules\Calendar')
    ->not->toUse(['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\MentorProgram', 'Modules\MentorProfile', 'Modules\UserSchedule']);

// DDD-migration Phase 3 (ExternalCalendar extraction, docs/plans/external-calendar-module-migration).
it('keeps Modules\ExternalCalendar non-empty', function (): void {
    expect(File::allFiles(base_path('Modules/ExternalCalendar/app')))->not->toBeEmpty();
});

arch('external-calendar-models-are-eloquent')
    ->expect('Modules\ExternalCalendar\Models')
    ->toBeClasses()
    ->toExtend(Model::class);

// `Modules\Calendar` is deliberately absent from this list: ExternalCalendar owns 2 FKs
// (`calendar_event_id`, both `cascadeOnDelete`) into CalendarEvent plus direct imports in
// its Jobs/Services/Policies — a normal Customer/Supplier relationship, not a boundary
// violation (see docs/plans/external-calendar-module-migration/02-development-plan-backend.md §4.3).
arch('external-calendar-does-not-reach-into-other-modules')
    ->expect('Modules\ExternalCalendar')
    ->not->toUse(['Modules\Chat', 'Modules\MentorProgram', 'Modules\MentorProfile', 'Modules\UserSchedule']);
