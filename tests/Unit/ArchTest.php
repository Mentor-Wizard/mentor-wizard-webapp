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
    ->not->toUse(['Modules\Calendar', 'Modules\ExternalCalendar', 'Modules\MentorProgram', 'Modules\UserSchedule', 'Modules\Auth', 'Modules\UserProfile']);

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
// `Modules\UserSchedule` is ALSO deliberately absent (DDD-migration Phase 8,
// docs/plans/user-schedule-module-migration/02-development-plan-backend.md, D-1):
// ExcludeUserScheduleSchemeService consumes `User::activeScheduleRecords()`
// (`Modules\UserSchedule\Traits\HasUserSchedules`), which returns
// `HasMany<UserSchedule, $this>` — a live, typed dependency on the UserSchedule model
// that cannot be expressed without naming the class. This is a one-directional
// Customer/Supplier edge, not a boundary violation; it is closed off from widening by
// the positive assertion below (`only permits the Calendar carve-outs…`), which pins
// it to exactly one file.
arch('calendar-does-not-reach-into-other-modules')
    ->expect('Modules\Calendar')
    ->not->toUse(['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\Marketplace', 'Modules\Auth', 'Modules\UserProfile']);

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
    ->not->toUse(['Modules\Chat', 'Modules\MentorProgram', 'Modules\Marketplace', 'Modules\UserSchedule', 'Modules\Auth', 'Modules\UserProfile']);

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
    ->not->toUse(['Modules\Chat', 'Modules\Calendar', 'Modules\ExternalCalendar', 'Modules\MentorProgram', 'Modules\Marketplace', 'Modules\UserSchedule', 'Modules\UserProfile']);

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
    ->not->toUse(['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\UserSchedule', 'Modules\Auth', 'Modules\UserProfile']);

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
// `Modules\UserProfile` is ALSO deliberately absent (DDD-migration Phase 7, OQ-3):
// MentorReviewResource / MentorProfilePageResource read the constant
// UserProfile::DEFAULT_AVATAR_URL — a one-directional Customer/Supplier edge, not a
// boundary violation. UserProfile's own forbidden list keeps 'Modules\Marketplace' since
// this edge does not run in reverse. Note (V-9): Modules/Marketplace/tests/** are plain
// Pest scripts without a `namespace` declaration, so `expect('Modules\Marketplace')` does
// not scan the 2 Marketplace test files that import UserProfile — no phantom failure to
// chase here.
arch('marketplace-does-not-reach-into-other-modules')
    ->expect('Modules\Marketplace')
    ->not->toUse(['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\UserSchedule', 'Modules\Auth']);

// DDD-migration Phase 7 (UserProfile extraction, docs/plans/userprofile-module-migration).
it('keeps Modules\UserProfile non-empty', function (): void {
    expect(File::allFiles(base_path('Modules/UserProfile/app')))->not->toBeEmpty();
});

arch('userprofile-models-are-eloquent')
    ->expect('Modules\UserProfile\Models')
    ->toBeClasses()
    ->toExtend(Model::class);

arch('userprofile-actions-pages-suffix')
    ->expect('Modules\UserProfile\Actions\Pages')
    ->toHaveSuffix('Page');

// `Modules\ExternalCalendar` is deliberately absent: GetProfilePage assembles the
// `calendarIntegrations` prop from Modules\ExternalCalendar\{Enums,Models}
// (CalendarProviderEnum, CalendarSyncStatusEnum, UserCalendarIntegration) — a
// one-directional Customer/Supplier edge (UserProfile is the consumer), documented as
// tech debt (D-3, OQ-2) rather than fixed by an inversion in this structural-move PR.
arch('userprofile-does-not-reach-into-other-modules')
    ->expect('Modules\UserProfile')
    ->not->toUse(['Modules\Chat', 'Modules\Calendar', 'Modules\MentorProgram', 'Modules\Marketplace', 'Modules\UserSchedule', 'Modules\Auth']);

// DDD-migration Phase 8 (UserSchedule extraction, docs/plans/user-schedule-module-migration).
it('keeps Modules\UserSchedule non-empty', function (): void {
    expect(File::allFiles(base_path('Modules/UserSchedule/app')))->not->toBeEmpty();
});

arch('userschedule-models-are-eloquent')
    ->expect('Modules\UserSchedule\Models')
    ->toBeClasses()
    ->toExtend(Model::class);

arch('userschedule-actions-pages-suffix')
    ->expect('Modules\UserSchedule\Actions\Pages')
    ->toHaveSuffix('Page');

// UserSchedule has zero outbound cross-module dependencies: its only dependency is the
// Core `App\Models\User` (via `belongsTo`), which is not a module. The single inbound edge
// (Calendar -> UserSchedule) is documented and pinned on the Calendar side above (D-1).
arch('userschedule-does-not-reach-into-other-modules')
    ->expect('Modules\UserSchedule')
    ->not->toUse(['Modules\Chat', 'Modules\Calendar', 'Modules\ExternalCalendar', 'Modules\MentorProgram', 'Modules\Marketplace', 'Modules\Auth', 'Modules\UserProfile']);

// Regression guard for the bidirectional Calendar <-> MentorProgram carve-out (docs/plans/
// mentor-program-ddd-migration/04-qa-backend.md): the two `not->toUse()` lists above are
// each missing the *other* module by design (a real Customer/Supplier edge), but that
// omission must stay scoped to exactly this pair. This test fails loudly if either module
// ever imports one of the modules it is NOT supposed to reach into, and pins down that the
// live cross-import only flows Calendar -> MentorProgram / MentorProgram -> Calendar, not to
// any other module.
it('only permits the Calendar carve-outs, not a wider cross-import allowance', function (): void {
    $forbiddenForCalendar = ['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\Marketplace', 'Modules\Auth', 'Modules\UserProfile'];
    // `Modules\Marketplace` stays forbidden here (unlike the `arch()` rule above it, which must
    // carve it out for the seeder edge — see the comment on that rule): this regression test only
    // scans `Modules/MentorProgram/app` (confirmed by grep: zero references there), so it correctly
    // stays strict and will fail loudly if a *production* MentorProgram class ever imports
    // Marketplace, keeping the carve-out scoped to exactly the one known seeder edge.
    $forbiddenForMentorProgram = ['Modules\Chat', 'Modules\ExternalCalendar', 'Modules\Marketplace', 'Modules\UserSchedule', 'Modules\Auth', 'Modules\UserProfile'];

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

// Regression guard for the Calendar -> UserSchedule carve-out (DDD-migration Phase 8,
// docs/plans/user-schedule-module-migration/02-development-plan-backend.md, D-1): the
// carve-out above is deliberately narrow — it removes `Modules\UserSchedule` from
// Calendar's forbidden list entirely, rather than expressing the dependency via an
// unresolvable FQCN-in-PHPDoc workaround. This test pins the edge to exactly one file
// (`ExcludeUserScheduleSchemeService`), so the carve-out fails loudly the moment a
// second Calendar class imports UserSchedule.
it('keeps the Calendar -> UserSchedule carve-out scoped to exactly one file', function (): void {
    $calendarFiles = File::allFiles(base_path('Modules/Calendar/app'));

    $filesReferencingUserSchedule = collect($calendarFiles)
        ->filter(fn (SplFileInfo $file): bool => str_contains(File::get($file->getPathname()), 'use Modules\UserSchedule\\'))
        ->map(fn (SplFileInfo $file): string => $file->getFilename())
        ->values();

    expect($filesReferencingUserSchedule->all())->toBe(['ExcludeUserScheduleSchemeService.php']);
});
