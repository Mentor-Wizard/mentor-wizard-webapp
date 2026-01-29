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
composer install
yarn install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database and run migrations
php artisan migrate

# Start development servers
composer run dev  # Starts Laravel Octane, queue worker, logs, and Vite
```

### Development Scripts

All commands should run from a Docker container. The project includes several
useful Composer scripts:

- `composer run dev` - Start all development services (Octane, queue, logs,
  Vite)
- `composer run ide-helper` - Generate IDE helper files
- `composer run phpstan` - Run static analysis
- `composer run pint` - Check code style
- `composer run pint:fix` - Fix code style issues
- `composer run rector` - Check for code modernization opportunities
- `composer run rector:fix` - Apply code modernization

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for
this application. These guidelines should be followed closely to enhance the
user's satisfaction building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems
package & versions are below. You are an expert with them all. Ensure you abide
by these specific packages & versions.

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

## Conventions

- You must follow all existing code conventions used in this application. When
  creating or editing a file, check sibling files for the correct structure,
  approach, naming.
- Use descriptive names for variables and methods. For example,
  `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Execution Context

- **All** commands (php, composer, yarn, npm, pnpm, etc.) MUST be executed inside the `app` Docker container using `docker compose exec app <command>` or via the provided composer scripts (which run in the container).
- Do not run commands directly on the host machine unless explicitly instructed for host-specific tasks (like git).

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that
  functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure - don\'t create new base folders without
  approval.
- Do not change the application\'s dependencies without approval.

## Frontend Bundling

- If the user doesn\'t see a frontend change reflected in the UI, it could mean
  they need to run `yarn run build`, `yarn run dev`, or `composer run dev`. Ask
  them.

## Replies

- Be concise in your explanations - focus on what\'s important rather than
  explaining obvious details.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== project/architecture ===

## Architecture Patterns

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

=== project/testing ===

## Testing Information

### Models Testing Policy

- DO NOT create unit tests for Laravel Eloquent models.
- Rationale:
    - Laravel\'s Eloquent ORM is extensively tested by the Laravel team
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

### Unit Testing Policy

- All unit tests must be mutation tests within Pest.

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
docker compose exec app ./vendor/bin/pest

# Run with coverage
docker compose exec app ./vendor/bin/pest --coverage

# Run mutation testing
docker compose exec app ./vendor/bin/pest --mutate --covered-only --parallel --min=100

# Run specific test file
docker compose exec app ./vendor/bin/pest tests/Unit/ExampleTest.php
```

#### Local Environment

```bash
# Ensure test database is configured in .env.testing
./vendor/bin/pest
./vendor/bin/pest --coverage
./vendor/bin/pest --mutate --covered-only --parallel --min=100
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

### Test Enforcement

- Every change must be programmatically tested. Write a new test or update an
  existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use
  `php artisan test` with a specific filename or filter.

=== project/coding-standards ===

## Code Style & Development Practices

### Eloquent ID Access

- **Never** access `$model->id` directly. Use `$model->getKey()` (or
  `$model->getKeyName()` when you need the column name) to respect custom
  primary keys and keep code forward-compatible.

### Code Quality Tools

1. **Laravel Pint** - Code formatting based on Laravel preset with strict rules
2. **PHPStan (Level 5)** - Static analysis with Larastan for Laravel-specific
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
- **HTTP Status Codes**: Use `Symfony\Component\HttpFoundation\Response` constants (e.g. `Response::HTTP_OK`) instead of hardcoded integers.

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

### Environment-Specific Notes

- **Local Development**: Use Docker Compose for consistent environment
- **Testing**: Separate PostgreSQL instance for tests
- **Production**: Optimized for Laravel Octane with FrankenPHP
- **Debugging**: Xdebug available in development, Telescope for application
  debugging

=== boost rules ===

## Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed
  specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command
  to double check the available parameters.

## URLs

- Whenever you share a project URL with the user you should use the
  `get-absolute-url` tool to ensure you\'re using the correct scheme, domain /
  IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or
  query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs`
  tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before any other
  approaches. This tool automatically passes a list of installed packages and
  their versions to the remote Boost API, so it returns only version-specific
  documentation specific for the user\'s circumstance. You should pass an array
  of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including
  Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before
  falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking
  the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example:
  `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared.
  For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

- You can and should pass multiple queries at once. The most relevant results
  will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds
   'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing
   both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be
   adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase
   "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these
   terms

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) {
      }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments

- Prefer PHPDoc blocks over comments. Never use comments within the code itself
  unless there is something _very_ complex going on.

## PHPDoc Blocks

- Add useful array shape type definitions for arrays when appropriate.

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`,
  `BestLake`, `Monthly`.

=== filament/core rules ===

## Filament

- Filament is used by this application, check how and where to follow existing
  application conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows
  developers to define user interfaces in PHP using structured configuration
  objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- You can use the `search-docs` tool to get information from the official
  Filament documentation when needed. This is very useful for Artisan command
  arguments, specific code examples, testing functionality, relationship
  management, and ensuring you\'re following idiomatic practices.
- Utilize static `make()` methods for consistent component initialization.

### Artisan

- You must use the Filament specific Artisan commands to create new files or
  components for Filament. You can find these with the `list-artisan-commands`
  tool, or with `php artisan` and the `--help` option.
- Inspect the required options, always pass `--no-interaction`, and valid
  arguments for other options when applicable.

### Filament\'s Core Features

- Actions: Handle doing something within the application, often with a button or
  link. Actions encapsulate the UI, the interactive modal window, and the logic
  that should be executed when the modal window is submitted. They can be used
  anywhere in the UI and are commonly used to perform one-time actions like
  deleting a record, sending an email, or updating data in the database based on
  modal form input.
- Forms: Dynamic forms rendered within other features, such as resources, action
  modals, table filters, and more.
- Infolists: Read-only lists of data.
- Notifications: Flash notifications displayed to users within the application.
- Panels: The top-level container in Filament that can include all other
  features like pages, resources, forms, tables, notifications, actions,
  infolists, and widgets.
- Resources: Static classes that are used to build CRUD interfaces for Eloquent
  models. Typically live in `app/Filament/Resources`.
- Schemas: Represent components that define the structure and behavior of the
  UI, such as forms, tables, or lists.
- Tables: Interactive tables with filtering, sorting, pagination, and more.
- Widgets: Small component included within dashboards, often used for displaying
  data in charts, tables, or as a stat.

### Relationships

- Determine if you can use the `relationship()` method on form components when
  you need `options` for a select, checkbox, repeater, or when building a
  `Fieldset`:

<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required();
</code-snippet>

## Testing

- It\'s important to test Filament functionality for user satisfaction.
- Ensure that you are authenticated to access the application within the test.
- Filament uses Livewire, so start assertions with `livewire()` or
  `Livewire::test()`.

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

=== filament/v4 rules ===

## Filament 4

### Important Version 4 Changes

- File visibility is now `private` by default.
- The `deferFilters` method from Filament v3 is now the default behavior in
  Filament v4, so users must click a button before the filters are applied to
  the table. To disable this behavior, you can use the `deferFilters(false)`
  method.
- The `Grid`, `Section`, and `Fieldset` layout components no longer span all
  columns by default.
- The `all` pagination page method is not available for tables by default.
- All action classes extend `Filament\Actions\Action`. No action classes exist
  in `Filament\Tables\Actions`.
- The `Form` & `Infolist` layout components have been moved to
  `Filament\Schemas\Components`, for example `Grid`, `Section`, `Fieldset`,
  `Tabs`, `Wizard`, etc.
- A new `Repeater` component for Forms has been added.
- Icons now use the `Filament\Support\Icons\Heroicon` Enum by default. Other
  options are available and documented.

### Organize Component Classes Structure

- Schema components: `Schemas/Components/`
- Table columns: `Tables/Columns/`
- Table filters: `Tables/Filters/`
- Actions: `Actions/`

=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory
  unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade
  views. 
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>

=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before
  making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features

- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States

- When using deferred props on the frontend, you should add a nice empty state
  with pulsing / animated skeleton.

### Inertia Form General Guidance

- Build forms using the `useForm` helper. Use the code examples and
  `search-docs` tool with a query of `useForm helper` for guidance.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations,
  controllers, models, etc.). You can list available Artisan commands using the
  `list-artisan-commands` tool.
- If you\'re creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without
  user input. You should also pass the correct `--options` to ensure correct
  behavior.

### Database

- Always use proper Eloquent relationship methods with return type hints. Prefer
  relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel\'s
  ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel\'s query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too.
  Ask the user if they need any other things, using `list-artisan-commands` to
  check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless
  existing API routes do not, then you should follow existing application
  convention.

### Controllers & Validation

- Always create Form Request classes for validation rather than inline
  validation in controllers. Include both validation rules and custom error
  messages.
- Check sibling Form Requests to see if the application uses array or string
  based validation rules.

### Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue`
  interface.

### Authentication & Authorization

- Use Laravel\'s built-in authentication and authorization features (gates,
  policies, Sanctum, etc.).

### URL Generation

- When generating links to other pages, prefer named routes and the `route()`
  function.

### Configuration

- Use environment variables only in configuration files - never use the `env()`
  function directly outside of config files. Always use `config('app.name')`,
  not `env('APP_NAME')`.

### HTTP Status Codes

- Never use hardcoded integers for HTTP status codes (e.g. `200`, `404`).
- Always use constants from `Symfony\Component\HttpFoundation\Response` (e.g. `Response::HTTP_OK`, `Response::HTTP_NOT_FOUND`).

### Testing

- When creating models for tests, use the factories for the models. Check if the
  factory has custom states that can be used before manually setting up the
  model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`.
  Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to
  create a feature test, and pass `--unit` to create a unit test. Most tests
  should be feature tests.

### Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file
  in Vite manifest" error, you can run `yarn run build` or ask the user to run
  `yarn run dev` or `composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this
  project uses.

### Laravel 12 Structure

- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and
  routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or
  `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are
  automatically available and do not require manual registration.

### Database

- When modifying a column, the migration must include all of the attributes that
  were previously defined on the column. Otherwise, they will be dropped and
  lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external
  packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather
  than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

## Livewire Core

- Use the `search-docs` tool to find exact version specific documentation for
  how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\\CreatePost]` artisan command to
  create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they\'re like regular HTTP
  requests. Always validate form data, and run authorization checks in Livewire
  actions.

## Livewire Best Practices

- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and
  reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>

## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>

    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>

=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2

- These things changed in Livewire 2, but may not have been updated in this
  application. Verify this application\'s setup to ensure you conform with
  application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred
      by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or
      `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not
      `layouts.app`).

### New Directives

- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target`
  are available for use. Use the documentation to find usage examples.

### Alpine

- Alpine is now included with Livewire, don\'t manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks

- You can listen for `livewire:init` to hook into Livewire initialization, and
  `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });

}); </code-snippet>

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure
  your code matches the project\'s expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any
  formatting issues.

=== pest/core rules ===

## Pest

### Testing

- If you need to verify a feature is working, write or update a Unit / Feature
  test.

### Pest Tests

- All tests must be written using Pest. Use
  `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without
  approval. These are not temporary or helper files - these are core to the
  application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
  <code-snippet name="Basic Pest Test Example" lang="php"> it('is true',
  function () { expect(true)->toBeTrue(); }); </code-snippet>

### Running Tests

- Run the minimal number of tests using an appropriate filter before finalizing
  code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName`
  (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they
  would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions

- When asserting status codes on a response, use the specific method like
  `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or
  similar, e.g.:
  <code-snippet name="Pest Example Asserting postJson Response" lang="php">
  it('returns all', function () { $response = $this->postJson('/api/docs', []);

  $response->assertSuccessful(); }); </code-snippet>

### Mocking

- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always
  import it via `use function Pest\Laravel\mock;` before using it.
  Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets

- Use datasets in Pest to simplify tests which have a lot of duplicated data.
  This is often the case when testing validation rules, so consider going with
  this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>

=== pest/v4 rules ===

## Pest 4

- Pest v4 is a huge upgrade to Pest and offers: browser testing, smoke testing,
  visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing

- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`,
  and model factories within Pest v4 browser tests, as well as `RefreshDatabase`
  (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop,
  touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro,
  tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);

}); </code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs(); </code-snippet>

</laravel-boost-guidelines>