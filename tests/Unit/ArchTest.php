<?php

declare(strict_types=1);

use Database\Seeders\MentorTagSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

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
    ->not->toUse(['Modules\Calendar', 'Modules\ExternalCalendar', 'Modules\MentorProgram', 'Modules\MentorProfile', 'Modules\UserSchedule', 'Modules\Auth']);

// DDD-migration Phase 2 (Calendar extraction, docs/plans/migrate-calendar-domain-module).
it('keeps Modules\Calendar non-empty', function (): void {
    expect(File::allFiles(base_path('Modules/Calendar/app')))->not->toBeEmpty();
});

arch('calendar-models-are-eloquent')
    ->expect('Modules\Calendar\Models')
    ->toBeClasses()
    ->toExtend(Model::class);

// `Modules\MentorProgram` is deliberately absent: Calendar owns the FK
// calendar_events.mentor_program_id and reads the program's session_duration /
// need_confirmation in its booking services — a normal Customer/Supplier relationship,
// not a boundary violation (mirrors the ExternalCalendar → Calendar carve-out above).
arch('calendar-does-not-reach-into-other-modules')
    ->expect('Modules\Calendar')
    ->not->toUse(['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\MentorProfile', 'Modules\UserSchedule', 'Modules\Auth']);

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
    ->not->toUse(['Modules\Chat', 'Modules\MentorProgram', 'Modules\MentorProfile', 'Modules\UserSchedule', 'Modules\Auth']);

// DDD-migration Phase 4 (Auth extraction, docs/plans/identity-domain-migration).
it('keeps Modules\Auth non-empty', function (): void {
    expect(File::allFiles(base_path('Modules/Auth/app')))->not->toBeEmpty();
});

// A module with nameLower='auth' merges any Modules/Auth/config/*.php into the framework's
// `auth` config namespace via array_replace_recursive($existing, $moduleConfig) — the module
// wins on key conflicts — and publishes it to config_path('auth.php'), overwriting
// config/auth.php. This is a structural guard because a behavioural assertion on
// config('auth.*') would be vacuous here (see 02-development-plan-backend.md §1, decision D-A).
//
// The walk is recursive because ModuleServiceProvider::registerConfig() uses
// RecursiveIteratorIterator: a nested Modules/Auth/config/providers/users.php merges into the
// config key `auth.providers.users` and can silently repoint the authentication user provider.
// A non-recursive glob('config/*.php') would not see it.
it('keeps Modules\Auth free of module config files', function (): void {
    $configPath = base_path('Modules/Auth/config');

    $configFiles = File::isDirectory($configPath) ? File::allFiles($configPath) : [];

    $phpConfigFiles = array_filter(
        $configFiles,
        static fn (SplFileInfo $file): bool => $file->getExtension() === 'php',
    );

    expect($phpConfigFiles)->toBeEmpty();
});

arch('auth-does-not-reach-into-other-modules')
    ->expect('Modules\Auth')
    ->not->toUse(['Modules\Chat', 'Modules\Calendar', 'Modules\ExternalCalendar', 'Modules\MentorProgram', 'Modules\MentorProfile', 'Modules\UserSchedule']);

// DDD-migration Phase 5 (MentorProgram extraction, docs/plans/mentor-program-ddd-migration).
it('keeps Modules\MentorProgram non-empty', function (): void {
    expect(File::allFiles(base_path('Modules/MentorProgram/app')))->not->toBeEmpty();
});

arch('mentor-program-models-are-eloquent')
    ->expect('Modules\MentorProgram\Models')
    ->toBeClasses()
    ->toExtend(Model::class);

// `Modules\Calendar` is deliberately absent from this list. MentorProgram is the upstream
// supplier (Calendar holds the FK calendar_events.mentor_program_id and reads
// session_duration / need_confirmation), but ListMentorProgramPage counts a mentor's pending
// and confirmed bookings via withCount('calendarEvents') filtered by
// Modules\Calendar\Enums\CalendarEventStatusEnum — a real, live read edge in the opposite
// direction. Inverting it (a Calendar-owned booking-count read model consumed through a Core
// contract) is logged debt, not something to fake with an arch rule; see
// docs/plans/mentor-program-ddd-migration/02-development-plan-backend.md §1.2.
arch('mentor-program-does-not-reach-into-other-modules')
    ->expect('Modules\MentorProgram')
    ->not->toUse(['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\MentorProfile', 'Modules\UserSchedule', 'Modules\Auth']);

// Regression guard for the bidirectional Calendar <-> MentorProgram carve-out (docs/plans/
// mentor-program-ddd-migration/04-qa-backend.md): the two `not->toUse()` lists above are
// each missing the *other* module by design (a real Customer/Supplier edge), but that
// omission must stay scoped to exactly this pair. This test fails loudly if either module
// ever imports one of the modules it is NOT supposed to reach into, and pins down that the
// live cross-import only flows Calendar -> MentorProgram / MentorProgram -> Calendar, not to
// any other module.
it('only permits the Calendar <-> MentorProgram carve-out, not a wider cross-import allowance', function (): void {
    $forbiddenForCalendar = ['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\MentorProfile', 'Modules\UserSchedule', 'Modules\Auth'];
    $forbiddenForMentorProgram = ['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\MentorProfile', 'Modules\UserSchedule', 'Modules\Auth'];

    $calendarFiles = File::allFiles(base_path('Modules/Calendar/app'));
    $mentorProgramFiles = File::allFiles(base_path('Modules/MentorProgram/app'));

    foreach ($calendarFiles as $file) {
        $contents = File::get($file->getPathname());

        foreach ($forbiddenForCalendar as $forbiddenNamespace) {
            expect($contents)->not->toContain(sprintf('use %s\\', $forbiddenNamespace));
        }
    }

    foreach ($mentorProgramFiles as $file) {
        $contents = File::get($file->getPathname());

        foreach ($forbiddenForMentorProgram as $forbiddenNamespace) {
            expect($contents)->not->toContain(sprintf('use %s\\', $forbiddenNamespace));
        }
    }

    $mentorProgramReferencesCalendar = collect($mentorProgramFiles)
        ->contains(fn (SplFileInfo $file): bool => str_contains(File::get($file->getPathname()), 'use Modules\Calendar\\'));

    expect($mentorProgramReferencesCalendar)->toBeTrue();
});
