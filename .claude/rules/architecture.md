# Architecture Patterns

## Modular Monolith (DDD)

The application is migrating from a single `app/` tree to a **modular monolith**:
one deployable, one database, but business logic partitioned into self-contained
modules by domain (bounded context), enforced by `nwidart/laravel-modules` (^13.0)
and `tests/Unit/ArchTest.php`.

- **Why a modular monolith, not microservices**: DDD boundaries without the
  operational cost of a distributed system (no network calls between domains, one
  deploy, one transaction manager). Boundaries are enforced by Arch tests, not by
  process isolation.
- **Why `nwidart/laravel-modules`**: mature (Laravel 13-compatible), generates the
  same building blocks the app already uses (Actions, Enums, Policies, migrations,
  factories, tests) via `php artisan module:*` commands, and integrates with
  Composer autoloading, PHPStan, Rector, and the CI matrix without custom tooling.
- **Boundary model**: each module is a *bounded context* (e.g. `Modules/Chat`).
  Cross-cutting concerns that don't own business rules (Notifications, Media,
  Presence, Admin/Filament) stay supporting capabilities in `app/` (Core), not
  modules. Full boundary catalogue and per-domain migration readiness:
  `docs/temp/ddd-domain-analysis.md`.
- **Full decision record, conventions, and artisan command reference**:
  `docs/MODULAR_ARCHITECTURE.md`.
- **Status**: `Chat` is the extracted pilot module (`Modules/Chat/`); all other
  domains still live in `app/` pending the extraction sequence in the doc above.

## Business Logic

- **Laravel Actions** (`lorisleiva/laravel-actions`) — all business logic in Action classes
- **Service Layer**: implemented via Action classes (no separate service classes)
- **Repository Pattern**: not used — rely on Eloquent models directly
- Inside a module, Actions follow the same conventions as `app/Actions` — see
  `docs/MODULAR_ARCHITECTURE.md` for the module-specific directory layout.

## Frontend

- **Inertia.js** with Vue.js — frontend built as SPA via server-driven routing
- **Domain Organization**: features organized by domain (Auth, MentorPrograms, Calendar, etc.)

## Database

- Every DB structure change → new migration
- Every DB data change → update seeder + factory
- Prefer Eloquent models over raw queries (`DB::` facade)
- Prefer Eloquent relationships over manual joins
- Prefer Eloquent eager loading over lazy loading (N+1 prevention)
- Prefer Eloquent pagination, scopes, soft deletes over raw alternatives

## Performance

- **Laravel Octane** with FrankenPHP — high-performance application server
- **Redis** — caching, sessions, queue management
- **PostgreSQL** with proper indexing
- Image optimization tools included in Docker setup

## Development Tools

- **Telescope** — debugging assistant (enabled in testing)
- **Log Viewer** — web-based log viewing
- **IDE Helpers** — auto-generated (`php artisan ide-helper:generate`)
- **Xdebug** — available in Docker development environment
