# 0001. `User` model stays in Core

## Status

Accepted, 2026-08-05.

## Context

`docs/temp/ddd-domain-analysis.md` §8.2 identifies the extraction of the
Identity & Access bounded context (A4) into `Modules/Auth` as a gate for the
modular-monolith migration, and flags an open question (`Q-A`): should
`App\Models\User` move into the new module along with the Actions, Form
Requests, routes, and observer that implement registration, login/logout,
password reset/change, email verification, and OAuth via Socialite?

`User` is not a single-domain entity. It is read and related to by every
extracted module and by domains still in `app/`:

- `Modules\Chat\Models\Chat` (chat participants)
- `Modules\Calendar\Traits\HasCalendarEvents` (host/participant relations)
- `Modules\ExternalCalendar\Traits\HasExternalCalendarIntegrations`
- `MentorProgram`, `UserSchedule`, `Filament` admin panel
- `users.slug` powers public Marketplace URLs (A6), unrelated to auth

`UserObserver::created()` also performs three responsibilities with three
different domain owners: assigning the default role (A4 Identity), creating the
`UserProfile` record (A7 UserProfile — not yet extracted), and generating a
unique `users.slug` (Core / A6 Marketplace).

## Decision

`App\Models\User` **remains in Core** (`app/Models/User.php`), together with:

- `database/factories/UserFactory.php`
- The `users` table migrations (`create_users_table`,
  `update_users_table_change_name_to_username`, `add_slug_field`)
- The vendor-published `create_permission_tables` migration
  (`spatie/laravel-permission`)
- `database/seeders/RoleSeeder.php`
- `App\Enums\RoleEnum`, `App\Enums\RoleGuardEnum`
- `App\Models\UserProfile` (A7 is not yet extracted)

Only the domain _behaviour_ of Identity & Access moves into `Modules\Auth`: 17
Actions, 7 Form Requests, `SocialiteDriverEnum`, `UserObserver`, and the
`routes/auth.php` route group (17 routes / 16 names).

`UserObserver` moves to `Modules\Auth\Observers\UserObserver` as a single unit —
its role-assignment, profile-creation, and slug-generation responsibilities are
not split apart in this PR (splitting risks introducing a behavioural change
into what must be a zero-behaviour-change refactor). The binding stays
declarative: `App\Models\User` keeps
`#[ObservedBy(Modules\Auth\Observers\UserObserver::class)]`, updated only to the
new FQCN.

## Consequences

- This introduces a **fourth** Core → Module edge on `User`
  (`#[ObservedBy(Modules\Auth\Observers\UserObserver::class)]`), following the
  established precedent of `Modules\Calendar\Traits\HasCalendarEvents`,
  `Modules\ExternalCalendar\Traits\HasExternalCalendarIntegrations`, and
  `Modules\Chat\Models\Chat` (all imported in `User.php`). The dependency stays
  greppable in the model. Arch rules only forbid Module → Module edges, so this
  is allowed by design.
- Migration ownership follows the existing project rule
  (`docs/MODULAR_ARCHITECTURE.md`, "Розташування історичних міграцій із
  міжмодульними FK"): a migration belongs to the module that owns the table it
  changes (`Schema::table`/`Schema::create`). All four `users`-touching
  migrations change the `users` table, so they stay in Core regardless of which
  module's behaviour reads or writes that table.
- `UserProfile` staying in Core means `Modules\Auth\Observers\UserObserver`
  synchronously writes to a Core model (`$user->profile()->create(...)`). This
  is a Module → Core edge (allowed) with a documented trigger for revisiting:
  **if/when A7 UserProfile is extracted into its own module**, this becomes a
  Module → Module edge (forbidden by arch tests), and a `UserRegistered` domain
  event + listener in the UserProfile module should replace the direct call.
- Slug generation (`UserObserver::generateUniqueSlug()`) is a second borrowed
  responsibility with its own trigger: **if/when A6 Marketplace is extracted**,
  slug generation should be reassessed (e.g. moved to a Core observer or a
  `User` trait), since `users.slug` exists to serve Marketplace public URLs, not
  authentication.
- `RoleSeeder` and the vendor `create_permission_tables` migration stay in Core
  because `RoleEnum`/`RoleGuardEnum` stay in Core and roles are read by every
  domain, not just Auth.
- **Naming caveat**: the module is named `Auth`, not `Identity` (overriding an
  earlier draft of this decision). `ModuleServiceProvider::registerConfig()`
  derives the framework config namespace from the module's lowercased name
  (`auth` for this module) and would merge/publish any
  `Modules/Auth/config/config.php` into `config/auth.php`, silently overwriting
  the framework's own auth config. The module therefore deliberately has **no**
  `config/config.php` (only a `.gitkeep` placeholder), and
  `tests/Unit/ArchTest.php` asserts `Modules/Auth/config` stays free of `.php`
  files as a structural regression guard.
- **The `Auth` module cannot be disabled.** `module:disable Auth` removes all 17
  auth routes (login, registration, password reset/change, email verification,
  OAuth) and makes the entire application inaccessible. This is documented in
  `Modules/Auth/module.json`'s `description` field.

## Related

- `docs/MODULAR_ARCHITECTURE.md`
- `docs/temp/ddd-domain-analysis.md` §A4, §8.2 (Q-A)
- `docs/plans/identity-domain-migration/01-business-analysis.md`
- `docs/plans/identity-domain-migration/02-development-plan-backend.md`
