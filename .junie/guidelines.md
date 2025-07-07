# Mentor Wizard Development Guidelines

This document provides essential information for developers working on the Mentor Wizard project.

## Build/Configuration Instructions

### Environment Setup

1. **Clone the repository and set up environment variables**:
   ```bash
   cp .env.example .env
   # Edit .env file with your specific configuration
   ```

2. **Start the Docker environment**:
   ```bash
   docker compose up -d
   ```

3. **Install dependencies**:
   ```bash
   docker compose exec app composer install
   docker compose exec app yarn install
   ```

4. **Generate application key**:
   ```bash
   docker compose exec app php artisan key:generate
   ```

5. **Run migrations**:
   ```bash
   docker compose exec app php artisan migrate
   ```

6. **Build frontend assets**:
   ```bash
   docker compose exec app yarn build
   ```

### Docker Environment

The project uses Docker with the following services:
- **app**: Main application container running FrankenPHP (PHP 8.4 with built-in web server)
- **db**: PostgreSQL 17 database
- **db-test**: PostgreSQL database for testing
- **redis**: Redis 7.2.4 for caching, queues, and broadcasting
- **workers**: Queue worker container
- **schedule**: Scheduler container using supercronic
- **websockets**: WebSockets server using Laravel Reverb
- **mailpit**: Email testing service

## Testing Information

### Running Tests

1. **Run all tests**:
   ```bash
   docker compose exec app ./vendor/bin/pest --parallel
   ```

2. **Run specific test**:
   ```bash
   docker compose exec app ./vendor/bin/pest --filter=TestName
   ```

3. **Run tests with coverage**:
   ```bash
   docker compose exec app ./vendor/bin/pest --coverage
   ```

### Creating Tests

1. **Test Directory Structure**:
   - Feature tests: `tests/Feature/`
     - Console: `tests/Feature/Console/`
     - Controllers: `tests/Feature/Http/`
   - Unit tests: `tests/Unit/`
     - Actions: `tests/Unit/Actions/`
     - Models: `tests/Unit/Models/`
     - Jobs: `tests/Unit/Jobs/`
     - DTO: `tests/Unit/DTO/`

2. **Creating a new test**:
   - Use Pest PHP syntax with describe/it blocks
   - Follow BDD style with descriptive test names
   - Use the `mutates()` function for mutation testing

3. **Example Test**:

```php
<?php

declare(strict_types=1);

use App\Support\StringHelper;

describe('StringHelper', function (): void {
    it('returns the original string if it is shorter than the maximum length', function (): void {
        $string = 'Hello, World!';
        $result = StringHelper::truncate($string, 20);
        
        expect($result)->toBe($string);
    });
    
    it('truncates the string and appends an ellipsis if it is longer than the maximum length', function (): void {
        $string = 'Hello, World!';
        $result = StringHelper::truncate($string, 5);
        
        expect($result)->toBe('Hello...');
    });
});
```

### Test Quality

1. **After making changes, run**:
   ```bash
   docker compose exec app composer pint:fix
   docker compose exec app composer rector:fix
   ```

2. **Ensure 100% mutation test coverage**:
   ```bash
   docker compose exec app ./vendor/bin/pest --coverage
   ```

## Additional Development Information

### Code Style and Quality

1. **PHP Version**: The project uses PHP 8.4 features.

2. **Code Formatting**:
   - Run Pint to fix code style:
     ```bash
     docker compose exec app composer pint:fix
     ```
   - Run Rector to fix code quality:
     ```bash
     docker compose exec app composer rector:fix
     ```

3. **Static Analysis**:
   - The project uses PHPStan for static analysis with strict types and array shapes.

### Project Architecture

1. **Actions Pattern**:
   - Use Laravel Actions package for business logic
   - Create new actions with:
     ```bash
     docker compose exec app php artisan make:action {actionName}
     ```
   - Use `AsController` trait as default for Actions

2. **Data Transfer Objects (DTOs)**:
   - Use DTOs for passing request data through Actions

3. **Database Access**:
   - Avoid using `DB::` facade directly
   - Use `Model::query()` instead

4. **Frontend Development**:
   - The project uses Inertia.js with Vue.js and Tailwind CSS
   - After making frontend changes, recompile assets:
     ```bash
     docker compose exec app yarn build
     ```

### Debugging

1. **Xdebug**:
   - Xdebug is enabled in the development environment
   - Configure your IDE to connect to Xdebug on the Docker container

2. **Logs**:
   - Application logs are stored in `storage/logs/`
   - Access Laravel Telescope at `/telescope` for request/response debugging

3. **Email Testing**:
   - Access Mailpit at port 8025 to view sent emails in the development environment
