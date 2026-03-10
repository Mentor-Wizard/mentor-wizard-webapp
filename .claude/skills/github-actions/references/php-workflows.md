# PHP Workflow Patterns

## Laravel CI

### Setup PHP with Extensions

```yaml
- uses: shivammathur/setup-php@v2
  with:
    php-version: '8.4'
    extensions: pdo_pgsql, redis, mbstring, xml, bcmath
    coverage: xdebug
    tools: composer:v2
```

### Recommended Job Pipeline (fail fast)

1. **Pint** — code style (~10s)
2. **PHPStan** — static analysis (~30s)
3. **Pest** — tests (slowest, depends on above)

### Pint Job

```yaml
pint:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
    - run: composer install --no-interaction --prefer-dist
    - run: vendor/bin/pint --test
```

### PHPStan Job

```yaml
phpstan:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v4
    - uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
    - run: composer install --no-interaction --prefer-dist
    - run: vendor/bin/phpstan analyse --memory-limit=512M
```

### Pest Job with Services

```yaml
test:
  runs-on: ubuntu-latest
  needs: [pint, phpstan]
  services:
    postgres:
      image: postgres:17
      env:
        POSTGRES_DB: testing
        POSTGRES_USER: postgres
        POSTGRES_PASSWORD: password
      ports: ['5432:5432']
      options: >-
        --health-cmd="pg_isready" --health-interval=10s
        --health-timeout=5s --health-retries=3
    redis:
      image: redis:7
      ports: ['6379:6379']
      options: >-
        --health-cmd="redis-cli ping" --health-interval=10s
        --health-timeout=5s --health-retries=3
  steps:
    - uses: actions/checkout@v4
    - uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        extensions: pdo_pgsql, redis
        coverage: xdebug
    - run: composer install --no-interaction --prefer-dist
    - name: Prepare environment
      run: |
        cp .env.ci .env
        php artisan key:generate
        php artisan migrate --force
      env:
        DB_CONNECTION: pgsql
        DB_HOST: 127.0.0.1
        DB_PORT: 5432
        DB_DATABASE: testing
        DB_USERNAME: postgres
        DB_PASSWORD: password
    - run: php artisan test --compact
      env:
        DB_CONNECTION: pgsql
        DB_HOST: 127.0.0.1
        DB_PORT: 5432
        DB_DATABASE: testing
        DB_USERNAME: postgres
        DB_PASSWORD: password
        REDIS_HOST: 127.0.0.1
        REDIS_PORT: 6379
```

### Coverage Upload

```yaml
    - run: php artisan test --compact --coverage-clover=coverage.xml
    - uses: codecov/codecov-action@v4
      with:
        files: coverage.xml
        token: ${{ secrets.CODECOV_TOKEN }}
      if: always()
```

## Symfony CI

### Symfony-Specific Steps

```yaml
    - run: composer install --no-interaction --prefer-dist
    - run: php bin/console lint:yaml config/
    - run: php bin/console lint:twig templates/
    - run: php bin/console doctrine:schema:validate --skip-sync
    - run: vendor/bin/phpunit
```

### Symfony with Database

```yaml
    - name: Run migrations
      run: php bin/console doctrine:migrations:migrate --no-interaction
      env:
        DATABASE_URL: postgresql://postgres:password@127.0.0.1:5432/testing
```

## NativePHP

### Build NativePHP Desktop App

```yaml
    - uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
    - run: composer install --no-interaction --prefer-dist
    - run: npm ci
    - run: npm run build
    - run: php artisan native:build
```

### Multi-Platform NativePHP

```yaml
strategy:
  matrix:
    os: [ubuntu-latest, macos-latest, windows-latest]
runs-on: ${{ matrix.os }}
```

## PHP Matrix Testing

```yaml
strategy:
  fail-fast: true
  matrix:
    php: ['8.3', '8.4']
    stability: [prefer-lowest, prefer-stable]
steps:
  - uses: shivammathur/setup-php@v2
    with:
      php-version: ${{ matrix.php }}
  - run: composer update --${{ matrix.stability }} --no-interaction --prefer-dist
  - run: vendor/bin/pest
```