# Стратегія тестування

## Огляд

Проект використовує сучасний підхід до тестування з фокусом на якість та покриття коду. Основа - **Pest PHP** як testing framework з підтримкою **мутаційного тестування** для перевірки якості тестів.

## Філософія тестування

### Пірамида тестування

```
    /\     E2E Tests (мінімум)
   /  \
  /    \   Integration Tests (помірно)
 /      \
/________\  Unit Tests (максимум)
```

### Принципи

1. **Швидкість** - тести мають виконуватись швидко
2. **Ізоляція** - кожен тест незалежний від інших
3. **Повторюваність** - тест дає однаковий результат при багаторазовому виконанні
4. **Читабельність** - тест як документація коду
5. **Підтримуваність** - легко змінювати при зміні коду

## Структура тестів

### Директорії

```
tests/
├── Feature/              # Інтеграційні тести
│   ├── Auth/            # Тести автентифікації
│   ├── MentorPrograms/  # Тести програм менторинг
│   └── Pages/           # Тести сторінок
├── Unit/                # Юніт-тести
│   ├── Actions/         # Тести Action класів
│   ├── Models/          # Тести моделей (обмежено)
│   ├── Observers/       # Тести Observer'ів
│   └── Support/         # Тести допоміжних класів
├── Pest.php             # Конфігурація Pest
└── TestCase.php         # Базовий клас для тестів
```

## Framework - Pest PHP

### Чому Pest?

- **BDD-синтаксис** - читабельні тести
- **Менше boilerplate коду**
- **Потужні datasets** для параметризованих тестів
- **Архітектурні тести** - контроль структури коду
- **Мутаційне тестування** вбудовано

### Базова структура тесту

```php
<?php

declare(strict_types=1);

// Для мутаційного тестування (опціонально)
mutates(YourClass::class);

describe('Feature Description', function (): void {
    beforeEach(function (): void {
        // Setup код
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    });

    it('describes what it tests', function (): void {
        $result = someFunction();

        expect($result)->toBe('expected_value');
    });
});
```

## Типи тестів

### 1. Unit Tests (Юніт-тести)

**Що тестуємо:**
- Action класи
- Observer'и
- Support класи
- Enum'и
- Складні методи моделей з бізнес-логікою

**Що НЕ тестуємо:**
- Стандартні Eloquent операції
- Прості relationships
- Базовий CRUD без логіки
- Laravel framework функціональність

```php
// tests/Unit/Actions/Auth/LoginTest.php
it('logs in user with valid credentials', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('password123')
    ]);

    $request = new LoginRequest([
        'email' => $user->email,
        'password' => 'password123'
    ]);

    $action = new Login();
    $result = $action->handle($request);

    expect($result)
        ->toBeInstanceOf(RedirectResponse::class)
        ->and(Auth::check())
        ->toBeTrue()
        ->and(Auth::user()->getKey())
        ->toBe($user->getKey());
});
```

### 2. Feature Tests (Інтеграційні тести)

**Що тестуємо:**
- HTTP endpoints
- Повні user workflows
- Інтеграцію між компонентами
- Middleware перевірки
- Авторизацію та аутентифікацію

```php
// tests/Feature/MentorPrograms/CreateMentorProgramTest.php
it('creates mentor program successfully', function (): void {
    $mentor = User::factory()->create();
    $mentor->assignRole('mentor');

    $programData = [
        'title' => 'Test Program',
        'description' => 'Test Description',
        'price' => 1000,
    ];

    $this->actingAs($mentor)
        ->post(route('mentor-program.store'), $programData)
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('mentor_programs', [
        'title' => 'Test Program',
        'mentor_id' => $mentor->getKey(),
    ]);
});
```

### 3. Filament Tests

**Тестування Filament Resources:**

```php
// tests/Feature/Filament/UserResourceTest.php
use App\Filament\Resources\UserResource\Pages\ListUsers;

it('can render user list page', function (): void {
    $users = User::factory()->count(5)->create();

    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->email)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1));
});

it('can create user', function (): void {
    livewire(CreateUser::class)
        ->fillForm([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'username' => 'testuser',
        'email' => 'test@example.com',
    ]);
});
```

### 4. Inertia.js Tests

**Тестування Page Actions:**

```php
// tests/Feature/Pages/DashboardTest.php
it('renders dashboard page with correct props', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('pages.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('user', fn (Assert $user) => $user
                ->where('id', $user->getKey())
                ->where('email', $user->email)
                ->etc()
            )
        );
});
```

## Мутаційне тестування

### Що це?

Мутаційне тестування змінює код (створює мутації) і перевіряє, чи тести виявляють ці зміни. Це тестування якості самих тестів.

### Налаштування

```bash
# Запуск мутаційного тестування
docker compose exec app ./vendor/bin/pest --mutate --covered-only --parallel --min=100

# Тільки для конкретного класу
docker compose exec app ./vendor/bin/pest --mutate --covered-only --class="App\Actions\Auth\Login"
```

### Вимоги

- **100% mutation score** для критичних компонентів
- **95%+ mutation score** для бізнес-логіки
- **90%+ mutation score** для допоміжних класів

### Приклад конфігурації мутанта

```php
// Вказуємо клас для мутаційного тестування
mutates(App\Actions\Auth\Login::class);

it('handles authentication correctly', function (): void {
    // Тест має виявити всі можливі мутації в Login класі
});
```

## Datasets (Параметризовані тести)

### Використання

```php
// tests/Unit/Validation/EmailValidationTest.php
it('validates emails correctly', function (string $email, bool $expected): void {
    $validator = Validator::make(['email' => $email], ['email' => 'email']);

    expect($validator->passes())->toBe($expected);
})->with([
    'valid email' => ['test@example.com', true],
    'invalid email' => ['not-an-email', false],
    'empty email' => ['', false],
]);

// З dataset функцією
it('validates user roles', function (string $role): void {
    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->hasRole($role))->toBeTrue();
})->with('valid_roles');

// tests/Datasets.php
dataset('valid_roles', [
    'mentor',
    'admin',
    'menti'
]);
```

## Архітектурні тести

### Контроль якості коду

```php
// tests/Unit/ArchTest.php
arch('no debugging functions in production')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('models extend eloquent')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('actions use proper traits')
    ->expect('App\Actions')
    ->toUse('Lorisleiva\Actions\Concerns\AsAction');

arch('page actions have correct suffix')
    ->expect('App\Actions\Pages')
    ->toHaveSuffix('Page');

arch('policies have correct suffix')
    ->expect('App\Policies')
    ->toHaveSuffix('Policy');
```

## Запуск тестів

### Docker (Рекомендовано)

```bash
# Всі тести
docker compose exec app ./vendor/bin/pest

# З покриттям коду
docker compose exec app ./vendor/bin/pest --coverage

# Мутаційне тестування
docker compose exec app ./vendor/bin/pest --mutate --covered-only --parallel --min=100

# Конкретний файл
docker compose exec app ./vendor/bin/pest tests/Unit/ExampleTest.php

# З фільтром
docker compose exec app ./vendor/bin/pest --filter="login"
```

### Локально

```bash
./vendor/bin/pest
./vendor/bin/pest --coverage --min=90
```

## Налаштування бази даних для тестів

### Конфігурація phpunit.xml

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="SESSION_DRIVER" value="array"/>
```

## Factories та Seeders

### Ефективні Factory

```php
// database/factories/MentorProgramFactory.php
class MentorProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(500, 5000),
            'mentor_id' => User::factory()->mentor(),
        ];
    }

    public function expensive(): static
    {
        return $this->state(fn () => [
            'price' => fake()->numberBetween(5000, 10000)
        ]);
    }

    public function free(): static
    {
        return $this->state(['price' => 0]);
    }
}
```

### Використання в тестах

```php
it('filters expensive programs', function (): void {
    MentorProgram::factory()->expensive()->count(3)->create();
    MentorProgram::factory()->count(2)->create(['price' => 100]);

    $expensivePrograms = MentorProgram::query()
        ->where('price', '>', 5000)
        ->get();

    expect($expensivePrograms)->toHaveCount(3);
});
```

## Моки та Stubs

### Коли використовувати

- **Зовнішні API** - завжди мокати
- **Email відправка** - мокати в тестах
- **File system** - використовувати fake()
- **Повільні операції** - мокати за потреби

```php
// Моки для зовнішніх сервісів
it('processes payment via stripe', function (): void {
    $stripeMock = mock(StripeService::class);
    $stripeMock->shouldReceive('createPayment')
        ->once()
        ->with(Mockery::type(User::class), 1000)
        ->andReturn(new Payment());

    $this->app->instance(StripeService::class, $stripeMock);

    $action = new ProcessPayment();
    $result = $action->handle($request);

    expect($result)->toBeInstanceOf(RedirectResponse::class);
});

// Fake для email
it('sends welcome email on registration', function (): void {
    Mail::fake();

    $action = new RegisterUser();
    $action->handle($request);

    Mail::assertSent(WelcomeEmail::class);
});
```

## Покриття коду

### Вимоги

- **95%+ line coverage** для бізнес-логіки
- **90%+ line coverage** загалом
- **100% mutation score** для критичних компонентів

### Виключення з покриття

```php
// phpunit.xml
<coverage includeUncoveredFiles="true">
    <include>
        <directory suffix=".php">./app</directory>
    </include>
    <exclude>
        <directory suffix=".php">./app/Models</directory>
        <file>./app/Http/Kernel.php</file>
    </exclude>
</coverage>
```

## Continuous Integration

### GitHub Actions

```yaml
- name: Run Tests
  run: docker compose exec -T app ./vendor/bin/pest --coverage --min=90

- name: Run Mutation Tests
  run: docker compose exec -T app ./vendor/bin/pest --mutate --covered-only --parallel --min=95

- name: Upload Coverage
  uses: codecov/codecov-action@v3
```

## Best Practices

### Іменування тестів

```php
// ❌ Погано
it('test login');
it('user login test');

// ✅ Добре
it('logs in user with valid credentials');
it('redirects to dashboard after successful login');
it('shows error for invalid credentials');
```

### Організація тестів

```php
// ❌ Один великий тест
it('handles complete user registration flow', function () {
    // 50 рядків коду
});

// ✅ Розбиті на логічні частини
describe('User Registration', function () {
    it('validates required fields');
    it('creates user with valid data');
    it('sends welcome email');
    it('redirects to dashboard');
});
```

### Налаштування даних

```php
// ❌ Дублювання
it('test A', function () {
    $user = User::factory()->create(['name' => 'Test']);
    // тест
});

it('test B', function () {
    $user = User::factory()->create(['name' => 'Test']);
    // тест
});

// ✅ Використання beforeEach
describe('User tests', function () {
    beforeEach(function () {
        $this->user = User::factory()->create(['name' => 'Test']);
    });

    it('test A', function () {
        // використовуємо $this->user
    });
});
```

## Інструменти та метрики

### Локальна розробка

```bash
# Швидкий запуск тестів під час розробки
./vendor/bin/pest --filter="login" --stop-on-failure

# Покриття для конкретного класу
./vendor/bin/pest --coverage --filter="LoginTest"
```

### Метрики якості

- **Code Coverage** - мінімум 90%
- **Mutation Score** - мінімум 95% для бізнес-логіки
- **Test Speed** - максимум 30 секунд для повного набору
- **Test Maintenance** - максимум 10% часу на підтримку тестів

## Troubleshooting

### Повільні тести

```php
// Використовуйте LazilyRefreshDatabase
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

// Мініміізуйте factory створення
beforeEach(function () {
    $this->user = User::factory()->make(); // make, не create
});
```

### Проблеми з базою даних

```bash
# Очистка тестової бази
docker compose exec app php artisan migrate:fresh --env=testing
```

### Debugging тестів

```php
it('debugs test issue', function () {
    ray($variable); // У development
    expect($result)->dd(); // Pest specific
});
```

Ця стратегія забезпечує високу якість коду та впевненість у рефакторингу, що критично важливо для підтримки та розвитку проекту.
