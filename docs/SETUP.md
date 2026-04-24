# Development Setup

## System Requirements

- **PHP 8.4+** (Critical: The project requires PHP 8.4.0 or higher)
- **Node.js** with Yarn 4.6.0
- **PostgreSQL 17**
- **Redis 7.2+**
- **Docker & Docker Compose** (Recommended for development)

## Environment Setup

### Docker Development (Recommended)

```bash
cp .env.example .env
docker compose up -d
docker compose exec app composer install
docker compose exec app yarn install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app yarn dev
```

### Local Development

```bash
composer install && yarn install
cp .env.example .env
php artisan key:generate
php artisan migrate
composer run dev  # Starts Laravel Octane, queue worker, logs, and Vite
```

## Development Scripts

All commands run inside the Docker container:

| Command                   | Purpose                                        |
| ------------------------- | ---------------------------------------------- |
| `composer run dev`        | Start all services (Octane, queue, logs, Vite) |
| `composer run ide-helper` | Generate IDE helper files                      |
| `composer run phpstan`    | Static analysis                                |
| `composer run pint`       | Check code style                               |
| `composer run pint:fix`   | Fix code style                                 |
| `composer run rector`     | Check modernization opportunities              |
| `composer run rector:fix` | Apply modernization                            |

## Code Quality Commands

```bash
# Check
docker compose exec app ./vendor/bin/phpstan analyse
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/rector process --dry-run

# Fix
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/rector process

# IDE helpers
docker compose exec app php artisan ide-helper:generate
docker compose exec app php artisan ide-helper:models

# Clear caches
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan view:clear
```

## CI/CD Pipeline

GitHub Actions runs: PHPStan, Pint, Rector, Pest (with coverage + mutation),
Codecov.

## Environment Notes

- **Local**: Docker Compose for consistent environment
- **Testing**: Separate PostgreSQL instance
- **Production**: Laravel Octane with FrankenPHP
- **Debugging**: Xdebug + Telescope
