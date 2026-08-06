<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Modules\Marketplace\Database\Seeders\MentorTagSeeder;
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

// `Modules\Marketplace` is deliberately absent: Modules/Chat/app/Actions/ChatListUser.php:8
// imports Modules\Marketplace\Enums\TagEnum and reads $companion->mentorProfile->mentorTags
// to render a chat companion's stack tags — a materialized version of the "hidden A1->A6
// dependency" documented in docs/temp/ddd-domain-analysis.md § 2.4. This is pre-existing
// debt made visible by the Marketplace extraction (docs/plans/ddd-migration-marketplace), not
// new debt created by it; fixing the underlying coupling (an ACL / a CompanionTags read
// model) is out of scope for a structural move.
arch('chat-does-not-reach-into-other-modules')
    ->expect('Modules\Chat')
    ->not->toUse(['Modules\Calendar', 'Modules\ExternalCalendar', 'Modules\MentorProgram', 'Modules\UserSchedule', 'Modules\Auth']);

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
    ->not->toUse(['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\Marketplace', 'Modules\UserSchedule', 'Modules\Auth']);

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
    ->not->toUse(['Modules\Chat', 'Modules\MentorProgram', 'Modules\Marketplace', 'Modules\UserSchedule', 'Modules\Auth']);

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
    ->not->toUse(['Modules\Chat', 'Modules\Calendar', 'Modules\ExternalCalendar', 'Modules\MentorProgram', 'Modules\Marketplace', 'Modules\UserSchedule']);

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
//
// `Modules\Marketplace` is ALSO deliberately absent (deviation from the Marketplace extraction
// plan's D-4b, which expected this edge to be one-directional Marketplace -> MentorProgram
// only): Modules/MentorProgram/database/seeders/MentorProgramSeeder.php directly creates a
// Modules\Marketplace\Models\MentorProfile for its demo mentors — a pre-existing dependency
// that was invisible before the extraction because MentorProfile lived in App\Models\* (Core),
// not a `Modules\*` namespace. Confirmed by grep: Modules/MentorProgram/app has zero references
// to Marketplace; the only edge is this one seeder. See
// docs/plans/ddd-migration-marketplace/02-development-backend.md for the full note.
arch('mentor-program-does-not-reach-into-other-modules')
    ->expect('Modules\MentorProgram')
    ->not->toUse(['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\UserSchedule', 'Modules\Auth']);

// DDD-migration Phase 6 (Marketplace extraction, docs/plans/ddd-migration-marketplace).
it('keeps Modules\Marketplace non-empty', function (): void {
    expect(File::allFiles(base_path('Modules/Marketplace/app')))->not->toBeEmpty();
});

arch('marketplace-models-are-eloquent')
    ->expect('Modules\Marketplace\Models')
    ->toBeClasses()
    ->toExtend(Model::class);

// `Modules\MentorProgram` is deliberately absent: Marketplace is the downstream consumer
// (MentorProfile::mentorPrograms() belongsToMany, read in ProgramCostFilter/TagStacksFilter/
// TagLanguagesFilter via whereHas) — a one-directional Customer/Supplier edge, not a
// boundary violation (docs/temp/ddd-domain-analysis.md § A6 "Жива залежність → A5").
// `Modules\Calendar` is ALSO deliberately absent: GetMentorProfilePage instantiates
// Modules\Calendar\Services\BookingCalendarEventsService to render the public profile's
// booking-slot block — a second, one-directional Customer/Supplier edge (see D-4b in the
// plan). Calendar's own forbidden list keeps 'Modules\Marketplace' since this edge does
// not run in reverse.
arch('marketplace-does-not-reach-into-other-modules')
    ->expect('Modules\Marketplace')
    ->not->toUse(['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\UserSchedule', 'Modules\Auth']);

// Regression guard for the bidirectional Calendar <-> MentorProgram carve-out (docs/plans/
// mentor-program-ddd-migration/04-qa-backend.md): the two `not->toUse()` lists above are
// each missing the *other* module by design (a real Customer/Supplier edge), but that
// omission must stay scoped to exactly this pair. This test fails loudly if either module
// ever imports one of the modules it is NOT supposed to reach into, and pins down that the
// live cross-import only flows Calendar -> MentorProgram / MentorProgram -> Calendar, not to
// any other module.
it('only permits the Calendar <-> MentorProgram carve-out, not a wider cross-import allowance', function (): void {
    $forbiddenForCalendar = ['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\Marketplace', 'Modules\UserSchedule', 'Modules\Auth'];
    // `Modules\Marketplace` stays forbidden here (unlike the `arch()` rule above it, which must
    // carve it out for the seeder edge — see the comment on that rule): this regression test only
    // scans `Modules/MentorProgram/app` (confirmed by grep: zero references there), so it correctly
    // stays strict and will fail loudly if a *production* MentorProgram class ever imports
    // Marketplace, keeping the carve-out scoped to exactly the one known seeder edge.
    $forbiddenForMentorProgram = ['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\Marketplace', 'Modules\UserSchedule', 'Modules\Auth'];

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
