# Mentor Wizard Development Guidelines

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
docker-compose up -d

# Install PHP dependencies
docker-compose exec app composer install

# Install Node dependencies
docker-compose exec app yarn install

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Build frontend assets
docker-compose exec app yarn dev
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
All commands should run from a Docker container.
The project includes several useful Composer scripts:
- `composer run dev` - Start all development services (Octane, queue, logs, Vite)
- `composer run ide-helper` - Generate IDE helper files
- `composer run phpstan` - Run static analysis
- `composer run pint` - Check code style
- `composer run pint:fix` - Fix code style issues
- `composer run rector` - Check for code modernization opportunities
- `composer run rector:fix` - Apply code modernization

## Testing Information

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
All tests must be run in a Docker container.
Feature tests do not need to mutate.

#### With Docker
```bash
# Run all tests
docker-compose exec app ./vendor/bin/pest

# Run with coverage
docker-compose exec app ./vendor/bin/pest --coverage

# Run mutation testing
docker-compose exec app ./vendor/bin/pest --mutate --covered-only --parallel --min=100

# Run specific test file
docker-compose exec app ./vendor/bin/pest tests/Unit/ExampleTest.php
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

## Code Style & Development Practices

### Code Quality Tools
1. **Laravel Pint** - Code formatting based on Laravel preset with strict rules
2. **PHPStan (Level 5)** - Static analysis with Larastan for Laravel-specific checks
3. **Rector** - Automated code modernization for PHP 8.4 and Laravel 12.0
4. **Cognitive Complexity** - Limits complexity (class: 85, function: 8)

### Code Style Rules
- **Strict Types**: All PHP files must declare `declare(strict_types=1)`
- **Type Declarations**: Full type hints required
- **Strict Comparisons**: Use `===` instead of `==`
- **Modern PHP**: Use PHP 8.4 features and modern type casting
- **Class Organization**: Specific order for class elements (constants, properties, methods)
- **Array Formatting**: Trailing commas in multiline arrays and parameters

### Architecture Patterns
- **Laravel Actions**: Business logic organized in Action classes (`lorisleiva/laravel-actions`)
- **Inertia.js**: Frontend built with Vue.js via Inertia.js
- **Domain Organization**: Features organized by domain (Auth, MentorPrograms, etc.)
- **Repository Pattern**: Not explicitly used, relies on Eloquent models
- **Service Layer**: Implemented via Action classes

### Performance Considerations
- **Laravel Octane**: Uses Swoole for high-performance application server
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

### Environment-Specific Notes
- **Local Development**: Use Docker Compose for consistent environment
- **Testing**: Separate PostgreSQL instance for tests
- **Production**: Optimized for Laravel Octane with Swoole
- **Debugging**: Xdebug available in development, Telescope for application debugging

### Common Commands
```bash
# Code quality checks
./vendor/bin/phpstan analyse
./vendor/bin/pint --test
./vendor/bin/rector process --dry-run

# Fix code issues
./vendor/bin/pint
./vendor/bin/rector process

# Generate IDE helpers
php artisan ide-helper:generate
php artisan ide-helper:models

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```
