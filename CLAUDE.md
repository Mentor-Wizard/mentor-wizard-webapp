# Claude Instructions (Index)

# Claude-specific behavior:
- Use available Skills for:
- Laravel code style
- Testing practices
- Architecture decisions
- Inertia using
- DevOps practices

If a Skill applies, prefer it over repeating rules here.

## **IMPORTANT**

1. Before writing any code, describe your approach and wait for approval.

2. If the requirements I give you are ambiguous, ask clarifying questions before writing any code.

3. After you finish writing any code, list the edge cases and suggest test cases to cover them.

4. If a task requires changes to more than 3 files, stop and break it into smaller tasks first.

5. When there’s a bug, start by writing a test that reproduces it, then fix it until the test passes.

6. Every time I correct you, reflect on what you did wrong and come up with a plan to never make the same mistake again.


## Workflow Orchestration

### 1. Plan Node Default
- Enter plan mode for ANY non-trivial task (3+ steps or architectural decisions)
- If something goes sideways, STOP and re-plan immediately – don't keep pushing
- Use plan mode for verification steps, not just building
- Write detailed specs upfront to reduce ambiguity

### 2. Subagent Strategy
- Use subagents liberally to keep main context window clean
- Offload research, exploration, and parallel analysis to subagents
- For complex problems, throw more compute at it via subagents
- One tack per subagent for focused execution

### 3. Self-Improvement Loop
- After ANY correction from the user: update `docs/lessons.md` with the pattern
- Write rules for yourself that prevent the same mistake
- Ruthlessly iterate on these lessons until mistake rate drops
- Review lessons at session start for relevant project

### 4. Verification Before Done
- Never mark a task complete without proving it works
- Diff behavior between main and your changes when relevant
- Ask yourself: "Would a staff engineer approve this?"
- Run tests, check logs, demonstrate correctness

### 5. Demand Elegance (Balanced)
- For non-trivial changes: pause and ask "is there a more elegant way?"
- If a fix feels hacky: "Knowing everything I know now, implement the elegant solution"
- Skip this for simple, obvious fixes – don't over-engineer
- Challenge your own work before presenting it

### 6. Autonomous Bug Fixing
- When given a bug report: just fix it. Don't ask for hand-holding
- Point at logs, errors, failing tests – then resolve them
- Zero context switching required from the user
- Go fix failing CI tests without being told how

## Task Management

1. **Plan First**: Write plan to `docs/todo.md` with checkable items
2. **Verify Plan**: Check in before starting implementation
3. **Track Progress**: Mark items complete as you go
4. **Explain Changes**: High-level summary at each step
5. **Document Results**: Add review section to `docs/todo.md`
6. **Capture Lessons**: Update `docs/lessons.md` after corrections

## Core Principles

- **Simplicity First**: Make every change as simple as possible. Impact minimal code.
- **No Laziness**: Find root causes. No temporary fixes. Senior developer standards.
- **Minimal Impact**: Changes should only touch what's necessary. Avoid introducing bugs.

# AI Agent Guidelines

This file contains canonical development guidelines for ALL AI coding assistants
used in this repository (Copilot, Codex, Gemini, Claude, others).

If you are an AI agent:

- Read this file before suggesting code
- Follow these rules unless explicitly instructed otherwise

## Build/Configuration Instructions

### System Requirements

- **PHP 8.4+** (Critical: The project requires PHP 8.4.0 or higher)
- **Node.js** with Yarn 4.6.0
- **PostgreSQL 17**
- **Redis 7.2+**
- **Docker & Docker Compose** (Recommended for development)

### Environment Setup

#### Option 1: Docker Development (Recommended)

```bash
# Copy environment file
cp .env.example .env

# Start all services
docker compose up -d

# Install PHP dependencies
docker compose exec app composer install

# Install Node dependencies
docker compose exec app yarn install

# Generate application key
docker compose exec app php artisan key:generate

# Run migrations
docker compose exec app php artisan migrate

# Build frontend assets
docker compose exec app yarn dev
```

#### Option 2: Local Development

Ensure PHP 8.4+ is installed, then:

```bash
# Install dependencies
docker compose exec app composer install
docker compose exec app yarn install

# Setup environment
docker compose exec app cp .env.example .env
docker compose exec app php artisan key:generate

# Configure database and run migrations
docker compose exec app php artisan migrate

# Start development servers
docker compose exec app composer run dev  # Starts Laravel Octane, queue worker, logs, and Vite
```

### Development Scripts

All commands should run from a Docker container. The project includes several
useful Composer scripts:

- `docker compose exec app composer run dev` - Start all development services (Octane, queue, logs,
  Vite)
- `docker compose exec app composer run ide-helper` - Generate IDE helper files
- `docker compose exec app composer run phpstan` - Run static analysis
- `docker compose exec app composer run pint` - Check code style
- `docker compose exec app composer run pint:fix` - Fix code style issues
- `docker compose exec app composer run rector` - Check for code modernization opportunities
- `docker compose exec app composer run rector:fix` - Apply code modernization

## Testing Information

### Models Testing Policy

- DO NOT create unit tests for Laravel Eloquent models.
- Rationale:
    - Laravel's Eloquent ORM is extensively tested by the Laravel team
    - Testing basic CRUD operations, relationships, and standard functionality
      provides no value
    - Models are excluded from code coverage metrics (see phpunit.xml)
- What NOT to test:
    - Basic relationships (hasOne, hasMany, belongsTo, etc.)
    - Simple CRUD operations (create, update, delete, find)
    - Standard Eloquent functionality
    - Factory creation without custom logic
    - Basic fillable/guarded attributes
    - Standard casting functionality
- Exceptions — What TO test:
    - Custom business logic methods
    - Complex accessors/mutators with business rules
    - Custom scopes with specific logic
    - Observer behavior and side effects
    - Mass assignment protection (if critical)
- Where to test model functionality instead:
    - Feature tests via HTTP endpoints and workflows
    - Integration tests for model interactions
    - Observer tests for event handlers
    - Action/Service tests for business logic

### Framework

- **Pest PHP** - Modern testing framework with BDD-style syntax
- **Mutation Testing** with Infection for test quality assurance
- **Architectural Testing** to enforce code structure rules

### Test Structure

```
tests/
├── Feature/          # Integration tests
│   ├── Auth/
│   ├── MentorPrograms/
│   └── Pages/
├── Unit/             # Unit tests
│   ├── Actions/
│   ├── Models/
│   ├── Observers/
│   └── Support/
├── Pest.php         # Pest configuration
└── TestCase.php     # Base test case
```

### Running Tests

All tests must be run in a Docker container. Feature tests do not need to
mutate.

#### With Docker

```bash
# Run all tests
docker compose exec app php artisan test

# Run with coverage
docker compose exec app php artisan test --coverage

# Run mutation testing
docker compose exec app php artisan test --mutate --covered-only --parallel --min=100

# Run specific test file
docker compose exec app php artisan test tests/Unit/ExampleTest.php
```

### Test Configuration

- **Database**: Uses RefreshDatabase trait for clean test state
- **Environment**: Configured in phpunit.xml with testing-specific settings
- **Coverage**: Reports generated in `coverage/` directory
- **Memory Limit**: 512M for test execution

### Writing Tests

#### Basic Test Structure

```php
<?php

declare(strict_types=1);

// For mutation testing (optional)
mutates(YourClass::class);

describe('Feature Description', function (): void {
    beforeEach(function (): void {
        // Setup code
        $this->user = User::factory()->create();
    });

    it('describes what it tests', function (): void {
        $result = someFunction();

        expect($result)->toBe('expected_value');
    });
});
```

#### Testing Actions (Laravel Actions Pattern)

```php
it('handles the action correctly', function (): void {
    $action = new YourAction();
    $result = $action->handle($request, $parameters);

    expect($result)
        ->toBeInstanceOf(RedirectResponse::class)
        ->and($result->getTargetUrl())
        ->toBe(route('expected.route'));
});
```

### Architectural Testing

The project enforces architectural rules via `tests/Unit/ArchTest.php`:

- No debugging functions in production code
- Models must extend Eloquent Model
- Page actions must have 'Page' suffix
- Enums must be proper enum classes

## Code Style & Development Practices

### Eloquent ID Access

- **Never** access `$model->id` directly. Use `$model->getKey()` (or
  `$model->getKeyName()` when you need the column name) to respect custom
  primary keys and keep code forward-compatible.

### Code Quality Tools

1. **Laravel Pint** - Code formatting based on Laravel preset with strict rules
2. **PHPStan (Level 7)** - Static analysis with Larastan for Laravel-specific
   checks
3. **Rector** - Automated code modernization for PHP 8.4 and Laravel 12.0
4. **Cognitive Complexity** - Limits complexity (class: 85, function: 8)

### Code Style Rules

- **Strict Types**: All PHP files must declare `declare(strict_types=1)`
- **Type Declarations**: Full type hints required
- **Strict Comparisons**: Use `===` instead of `==`
- **Modern PHP**: Use PHP 8.4 features and modern type casting
- **Class Organization**: Specific order for class elements (constants,
  properties, methods)
- **Array Formatting**: Trailing commas in multiline arrays and parameters
- **Eloquent Models**: Use `getKey()` method in models instead of `id`
- **Eloquent Models**: Use `query()` method in models queries
- **Eloquent Relationships**: Use `with()` method for eager loading
- **Eloquent Relationships**: Use `withCount()` method for eager loading counts
- **Eloquent Relationships**: Use `withTrashed()` method for eager loading
  trashed models

### Architecture Patterns

- **Laravel Actions**: Business logic organized in Action classes
  (`lorisleiva/laravel-actions`)
- **Inertia.js**: Frontend built with Vue.js via Inertia.js
- **Domain Organization**: Features organized by domain (Auth, MentorPrograms,
  etc.)
- **Repository Pattern**: Not explicitly used, relies on Eloquent models
- **Service Layer**: Implemented via Action classes
- **Database Migrations**: Every change in the DB structure should be reflected
  in a new migration
- **Database Seeders**: Every change in DB data should be reflected in a seeder
- **Database Factories**: Every change in DB data should be reflected in a
  factory
- **Database Queries**: Prefer Eloquent models over raw queries
- **Database Relationships**: Prefer Eloquent relationships to raw queries
- **Database Eager Loading**: Prefer Eloquent eager loading over raw queries
- **Database Pagination**: Prefer Eloquent pagination over raw queries
- **Database Scopes**: Prefer Eloquent scopes over raw queries
- **Database Soft Deletes**: Prefer Eloquent soft deletes over raw queries

### Performance Considerations

- **Laravel Octane**: Uses FrankenPHP for high-performance application server
- **Redis**: Used for caching, sessions, and queue management
- **Database**: PostgreSQL with proper indexing
- **Asset Optimization**: Image optimization tools included in Docker setup

### Development Tools

- **Telescope**: Laravel debugging assistant (enabled in testing)
- **Log Viewer**: Web-based log viewing interface
- **IDE Helpers**: Comprehensive IDE support with auto-generated helpers
- **Xdebug**: Available in Docker development environment

### CI/CD Pipeline

The project uses GitHub Actions with:

- **Linting**: PHPStan, Laravel Pint, Rector
- **Testing**: Pest with coverage and mutation testing
- **Code Coverage**: Codecov integration
- **Parallel Execution**: Tests run in parallel for faster feedback

### Git Operations

- **NEVER create commits automatically** - only commit when explicitly requested
  by the user
- **NEVER push to remote** without explicit user request
- **NEVER force push** or run destructive git commands without explicit approval
- When changes are ready, inform the user and wait for their instruction to
  commit/push
- Always show `git diff` or `git status` to let the user review changes before
  committing

### Pull Request Descriptions

- **NEVER mention AI tools** (Claude, Copilot, Gemini, etc.) in PR title or body
- **NEVER include change statistics** (file count, lines added/removed)
- **NEVER add test plan checklists** — there is no QA team to execute them
- Keep PR descriptions focused on **what** changed and **why**

### Environment-Specific Notes

- **Local Development**: Use Docker Compose for consistent environment
- **Testing**: Separate PostgreSQL instance for tests
- **Production**: Optimized for Laravel Octane with FrankenPHP
- **Debugging**: Xdebug available in development, Telescope for application
  debugging

### Common Commands

```bash
# Code quality checks
docker compose exec app ./vendor/bin/phpstan analyse
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/rector process --dry-run

# Fix code issues
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/rector process

# Generate IDE helpers
docker compose exec app php artisan ide-helper:generate
docker compose exec app php artisan ide-helper:models

# Clear caches
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan view:clear
```

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.15
- filament/filament (FILAMENT) - v4
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/framework (LARAVEL) - v12
- laravel/octane (OCTANE) - v2
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/reverb (REVERB) - v1
- laravel/socialite (SOCIALITE) - v5
- livewire/livewire (LIVEWIRE) - v3
- tightenco/ziggy (ZIGGY) - v2
- larastan/larastan (LARASTAN) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/telescope (TELESCOPE) - v5
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- rector/rector (RECTOR) - v2

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `yarn run build`, `yarn run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.

=== inertia-laravel/v2 rules ===

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scrolling (merging props + `WhenVisible`), lazy loading on scroll, polling, prefetching.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `yarn run build` or ask the user to run `yarn run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== filament/filament rules ===

## Filament

- Filament is used by this application, check how and where to follow existing application conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- You can use the `search-docs` tool to get information from the official Filament documentation when needed. This is very useful for Artisan command arguments, specific code examples, testing functionality, relationship management, and ensuring you're following idiomatic practices.
- Utilize static `make()` methods for consistent component initialization.

### Artisan

- You must use the Filament specific Artisan commands to create new files or components for Filament. You can find these with the `list-artisan-commands` tool, or with `php artisan` and the `--help` option.
- Inspect the required options, always pass `--no-interaction`, and valid arguments for other options when applicable.

### Filament's Core Features

- Actions: Handle doing something within the application, often with a button or link. Actions encapsulate the UI, the interactive modal window, and the logic that should be executed when the modal window is submitted. They can be used anywhere in the UI and are commonly used to perform one-time actions like deleting a record, sending an email, or updating data in the database based on modal form input.
- Forms: Dynamic forms rendered within other features, such as resources, action modals, table filters, and more.
- Infolists: Read-only lists of data.
- Notifications: Flash notifications displayed to users within the application.
- Panels: The top-level container in Filament that can include all other features like pages, resources, forms, tables, notifications, actions, infolists, and widgets.
- Resources: Static classes that are used to build CRUD interfaces for Eloquent models. Typically live in `app/Filament/Resources`.
- Schemas: Represent components that define the structure and behavior of the UI, such as forms, tables, or lists.
- Tables: Interactive tables with filtering, sorting, pagination, and more.
- Widgets: Small component included within dashboards, often used for displaying data in charts, tables, or as a stat.

### Relationships

- Determine if you can use the `relationship()` method on form components when you need `options` for a select, checkbox, repeater, or when building a `Fieldset`:

<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required(),
</code-snippet>

## Testing

- It's important to test Filament functionality for user satisfaction.
- Ensure that you are authenticated to access the application within the test.
- Filament uses Livewire, so start assertions with `livewire()` or `Livewire::test()`.

### Example Tests

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($users->last()->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Howdy',
            'email' => 'howdy@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Howdy',
        'email' => 'howdy@example.com',
    ]);
</code-snippet>

<code-snippet name="Testing Multiple Panels (setup())" lang="php">
    use Filament\Facades\Filament;

    Filament::setCurrentPanel('app');
</code-snippet>

<code-snippet name="Calling an Action in a Test" lang="php">
    livewire(EditInvoice::class, [
        'invoice' => $invoice,
    ])->callAction('send');

    expect($invoice->refresh())->isSent()->toBeTrue();
</code-snippet>

### Important Version 4 Changes

- File visibility is now `private` by default.
- The `deferFilters` method from Filament v3 is now the default behavior in Filament v4, so users must click a button before the filters are applied to the table. To disable this behavior, you can use the `deferFilters(false)` method.
- The `Grid`, `Section`, and `Fieldset` layout components no longer span all columns by default.
- The `all` pagination page method is not available for tables by default.
- All action classes extend `Filament\Actions\Action`. No action classes exist in `Filament\Tables\Actions`.
- The `Form` & `Infolist` layout components have been moved to `Filament\Schemas\Components`, for example `Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.
- A new `Repeater` component for Forms has been added.
- Icons now use the `Filament\Support\Icons\Heroicon` Enum by default. Other options are available and documented.

### Organize Component Classes Structure

- Schema components: `Schemas/Components/`
- Table columns: `Tables/Columns/`
- Table filters: `Tables/Filters/`
- Actions: `Actions/`
</laravel-boost-guidelines>
