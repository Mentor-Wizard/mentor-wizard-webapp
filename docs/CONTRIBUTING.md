# Гід для контрибуторів

## Ласкаво просимо до команди Mentor Wizard!

Дякуємо за інтерес до участі в розробці платформи менторингу. Цей документ
допоможе вам швидко інтегруватися в команду та почати ефективно працювати над
проектом.

## Перші кроки

### 1. Ознайомлення з проектом

**Обов'язкова література:**

- [README.md](../README.md) - загальний опис проекту
- [CLAUDE.md](../CLAUDE.md) - налаштування для AI-асистентів
- [docs/NAMING_CONVENTIONS.md](./NAMING_CONVENTIONS.md) - конвенції кодування
- [docs/ACTIONS_ARCHITECTURE.md](./ACTIONS_ARCHITECTURE.md) - архітектура
  Laravel Actions
- [docs/FRONTEND_ARCHITECTURE.md](./FRONTEND_ARCHITECTURE.md) - Vue.js +
  Inertia.js

**Додаткові ресурси:**

- [docs/TESTING_STRATEGY.md](./TESTING_STRATEGY.md) - підхід до тестування
- [docs/SECURITY_GUIDELINES.md](./SECURITY_GUIDELINES.md) - безпека проекту
- [docs/AUTHORIZATION_POLICIES.md](./AUTHORIZATION_POLICIES.md) - політики
  доступу

### 2. Налаштування середовища розробки

#### Вимоги

- **PHP 8.4+** (критично важливо!)
- **Node.js** з **Yarn 4.6.0**
- **Docker & Docker Compose**
- **PostgreSQL 17** (через Docker)
- **Redis 7.2+** (через Docker)

#### Швидкий старт

```bash
# 1. Клонування репозиторію
git clone https://github.com/your-org/mentor-wizard-webapp.git
cd mentor-wizard-webapp

# 2. Копіювання environment файлу
cp .env.example .env

# 3. Запуск Docker контейнерів
docker compose up -d

# 4. Встановлення залежностей
docker compose exec app composer install
docker compose exec app yarn install

# 5. Генерація ключа додатка
docker compose exec app php artisan key:generate

# 6. Запуск міграцій
docker compose exec app php artisan migrate

# 7. Створення symlink для storage
docker compose exec app php artisan storage:link

# 8. Запуск frontend збірки
docker compose exec app yarn dev

# 9. Налаштування Git hooks
./setup-git-hooks.sh
```

#### Налаштування Git Hooks

Проект використовує автоматизовані Git hooks для забезпечення якості коду:

```bash
# Запустіть скрипт налаштування
./setup-git-hooks.sh
```

Це налаштує наступні hooks:

**pre-commit:**

- Автоматично запускає Rector для модернізації коду
- Виконує Laravel Pint для форматування коду
- Виправляє стиль коду перед commit'ом

**commit-msg:**

- Валідує повідомлення комітів згідно
  [Conventional Commits](https://www.conventionalcommits.org/)
- Перевіряє формат: `type(scope): description`
- Приклади валідних повідомлень:
    - `feat(auth): add user avatar upload`
    - `fix(mentor): resolve program validation`
    - `docs(api): update authentication endpoints`

**pre-push:**

- Валідує назви гілок перед push'ем
- Перевіряє відповідність конвенціям найменування
- Дозволені префікси: `feature/`, `bugfix/`, `hotfix/`, `release/`

Якщо hook блокує ваш commit або push, перевірте:

1. Формат вашого commit message
2. Назву вашої гілки
3. Чи код відповідає стандартам проекту

#### Перевірка налаштування

```bash
# Перевірка роботи додатка
curl https://localhost

# Перевірка тестів
docker compose exec app ./vendor/bin/pest

# Перевірка якості коду
docker compose exec app ./vendor/bin/phpstan analyse
docker compose exec app ./vendor/bin/pint --test
```

## Workflow розробки

### Git Flow

Проект використовує **Git Flow** з наступними гілками:

```
main                 # Production-ready код
└── develop          # Integration гілка для features
    ├── feature/*    # Нові функції
    ├── bugfix/*     # Виправлення багів
    ├── hotfix/*     # Критичні виправлення
    └── release/*    # Підготовка до релізу
```

### Створення нової функції

#### 1. Створення feature гілки

```bash
# Переключення на develop
git checkout develop
git pull origin develop

# Створення feature гілки
git checkout -b feature/user-avatar-upload

# Або для bug fix
git checkout -b bugfix/login-validation-error
```

#### 2. Розробка

**Обов'язкові кроки:**

1. Прочитайте вимоги в issue/ticket
2. Створіть/оновіть тести перед написанням коду (TDD)
3. Впроваджуйте функцію відповідно до архітектури проекту
4. Перевірте код статичним аналізом

```bash
# Перевірка перед commit'ом
docker compose exec app composer pint
docker compose exec app composer phpstan
docker compose exec app composer rector
docker compose exec app yarn run eslint
docker compose exec app yarn run prettier
```

#### 3. Commit'и

Використовуємо **Conventional Commits**:

```bash
# Формат: type(scope): description
git commit -m "feat(auth): add user avatar upload functionality"
git commit -m "fix(mentor): resolve program creation validation"
git commit -m "test(auth): add avatar upload test cases"
git commit -m "docs(api): update authentication endpoints"
```

**Типи commit'ів:**

- `feat` - нова функція
- `fix` - виправлення бага
- `docs` - документація
- `style` - форматування коду
- `refactor` - рефакторинг
- `test` - тести
- `chore` - технічні зміни

### Code Review Process

#### 1. Створення Pull Request

```bash
# Push гілки
git push origin feature/user-avatar-upload

# Створення PR через GitHub CLI
gh pr create --title "feat(auth): Add user avatar upload" --body "
```

```markdown
## Summary

Додає можливість завантаження аватару користувача

## Changes

- [x] Додано Action для завантаження аватару
- [x] Створено Vue компонент для завантаження
- [x] Додано валідацію файлів
- [x] Написано тести

## Testing

- [x] Unit тести для Action
- [x] Feature тести для HTTP endpoint
- [x] Frontend тести для компонента

## Screenshots

[Прикріпити скріншоти UI змін]
```

#### 2. PR Template

Кожен PR повинен містити:

```markdown
## Summary

Короткий опис змін (1-2 речення)

## Changes

- [ ] Список основних змін
- [ ] Використовуйте чекбокси для tracking

## Testing

- [ ] Unit тести написані/оновлені
- [ ] Feature тести покривають новий функціонал
- [ ] Мануальне тестування виконано

## Documentation

- [ ] README оновлено (якщо потрібно)
- [ ] API документація оновлена
- [ ] Інлайн документація додана

## Breaking Changes

Опишіть будь-які breaking changes

## Screenshots

Додайте скріншоти для UI змін
```

#### 3. Review Guidelines

**Для авторів PR:**

- Переконайтеся, що всі тести проходять
- Перевірте, що код відповідає стандартам проекту
- Додайте опис змін та контекст
- Зробіть самостійний огляд коду перед створенням PR

**Для reviewer'ів:**

- Перевірте логіку та архітектуру
- Переконайтеся в наявності тестів
- Перевірте дотримання конвенцій
- Будьте конструктивними в коментарях

#### 4. Автоматизовані перевірки

GitHub Actions автоматично перевіряє:

- ✅ Код стиль (Laravel Pint)
- ✅ Статичний аналіз (PHPStan)
- ✅ Тести (Pest PHP)
- ✅ Мутаційне тестування (критичні компоненти)
- ✅ Security сканування

## Стандарти якості коду

### PHP код

#### Обов'язкові вимоги

```php
<?php

// ✅ Завжди declare strict types
declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

// ✅ Повна типізація
class Login
{
    use AsController;

    // ✅ Типи параметрів та return type
    public function handle(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('pages.dashboard'));
    }
}
```

#### Заборонено

```php
// ❌ Без declare strict types
<?php
namespace App\Actions;

// ❌ Без типізації
class BadExample
{
    public function handle($request)
    {
        // ❌ Використання dd(), dump(), var_dump() в production коді
        dd($request);

        // ❌ Прямі SQL запити без параметрів
        DB::select("SELECT * FROM users WHERE id = {$id}");

        return $something; // ❌ Без типу
    }
}
```

### Vue.js код

#### Composition API

```vue
<script setup>
// ✅ Використовуйте Composition API
import { ref, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';

// ✅ Типізація props
const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    canEdit: {
        type: Boolean,
        default: false,
    },
});

// ✅ Reactive refs
const isEditing = ref(false);

// ✅ Computed properties
const displayName = computed(
    () => props.user.profile?.display_name || props.user.username,
);

// ✅ Form handling
const form = useForm({
    username: props.user.username,
    email: props.user.email,
});
</script>

<template>
    <!-- ✅ Semantic HTML -->
    <article class="user-profile">
        <header>
            <h1>{{ displayName }}</h1>
        </header>

        <!-- ✅ Conditional rendering -->
        <form
            v-if="canEdit && isEditing"
            @submit.prevent="form.put(`/users/${user.id}`)"
        >
            <!-- Form fields -->
        </form>

        <!-- ✅ List rendering with keys -->
        <ul>
            <li v-for="program in user.mentor_programs" :key="program.id">
                {{ program.title }}
            </li>
        </ul>
    </article>
</template>
```

### Тестування

Проект використовує **Pest PHP** для тестування з обов'язковим **mutation
testing** для критичних компонентів.

#### Налаштування тестового середовища

Перед початком тестування скопіюйте `.env.example` в `.env.testing` та
налаштуйте підключення до тестової БД:

```bash
# Скопіюйте environment для тестів
cp .env.example .env.testing
```

Замініть в `.env.testing` блок з підключенням до БД:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=mw-db-test
DB_DATABASE=test_mw_db
DB_USERNAME=test_mw_user
DB_PASSWORD=test_mw_user_password
```

#### Запуск тестів

```bash
# Запуск всіх тестів
docker compose exec app php artisan test

# Запуск конкретного файлу
docker compose exec app php artisan test tests/Feature/Auth/LoginTest.php

# Запуск з фільтром
docker compose exec app php artisan test --filter=testName

# Корисні опції Pest
docker compose exec app ./vendor/bin/pest --bail      # Зупинка на першій помилці
docker compose exec app ./vendor/bin/pest --dirty     # Тести тільки змінених файлів
docker compose exec app ./vendor/bin/pest --retry     # Повтор невдалих тестів
```

#### Тести з покриттям

```bash
# Coverage звіт
docker compose exec app php artisan test --coverage
docker compose exec app ./vendor/bin/pest --coverage

# З мінімальним порогом
docker compose exec app ./vendor/bin/pest --coverage --min=80
```

#### Мутаційні тести

**Всі Unit тести мають бути покриті мутаційними тестами.** Обов'язково додавайте
метод `covers()` або `mutates()` до ваших тестів:

```php
<?php

declare(strict_types=1);

// Вказуємо який клас покривається цим тестом
covers(StoreMentorProgram::class);
// Або використовуйте mutates() для більш строгої перевірки
// mutates(StoreMentorProgram::class);

it('creates mentor program successfully', function (): void {
    $mentor = User::factory()->create();
    $mentor->assignRole('mentor');

    $programData = [
        'title' => 'Test Program',
        'description' => 'Test Description',
        'price' => 1000,
    ];

    $action = new StoreMentorProgram();
    $result = $action->handle(
        new StoreMentorProgramRequest($programData),
        $mentor
    );

    expect($result)
        ->toBeInstanceOf(MentorProgram::class)
        ->and($result->title)
        ->toBe('Test Program')
        ->and($result->mentor_id)
        ->toBe($mentor->getKey());
});
```

**Запуск мутаційних тестів:**

```bash
# З мінімальним mutation score 100%
docker compose exec app php artisan test --mutate --covered-only --min=100

# В паралельному режимі (швидше)
docker compose exec app php artisan test --mutate --covered-only --min=100 --parallel
docker compose exec app ./vendor/bin/pest --mutate --covered-only --parallel --min=100
```

Детальніше про мутаційне тестування:
[Pest Mutation Testing](https://pestphp.com/docs/mutation-testing)

#### Статичний аналіз коду

```bash
# PHPStan аналіз
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=2G

# Перевірка стилю коду
docker compose exec app ./vendor/bin/pint --test

# Виправлення стилю коду
docker compose exec app ./vendor/bin/pint
```

#### Приклади тестів

**Unit тест для Action:**

```php
covers(StoreMentorProgram::class);

it('creates mentor program successfully', function (): void {
    $mentor = User::factory()->create();
    $mentor->assignRole('mentor');

    $programData = [
        'title' => 'Test Program',
        'description' => 'Test Description',
        'price' => 1000,
    ];

    $action = new StoreMentorProgram();
    $result = $action->handle(
        new StoreMentorProgramRequest($programData),
        $mentor
    );

    expect($result)
        ->toBeInstanceOf(MentorProgram::class)
        ->and($result->title)->toBe('Test Program')
        ->and($result->mentor_id)->toBe($mentor->getKey());
});
```

**Feature тест:**

```php
it('allows mentor to create program via HTTP', function (): void {
    $mentor = User::factory()->create();
    $mentor->assignRole('mentor');

    $this->actingAs($mentor)
        ->post('/mentor-program', [
            'title' => 'Test Program',
            'description' => 'Test Description',
            'price' => 1000,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('mentor_programs', [
        'title' => 'Test Program',
        'mentor_id' => $mentor->getKey(),
    ]);
});
```

### Документація коду

```php
/**
 * Processes payment for mentor session
 *
 * @param PaymentRequest $request Validated payment data
 * @param MentorSession $session The session to pay for
 *
 * @return RedirectResponse Redirect to success/failure page
 *
 * @throws PaymentException When payment processing fails
 * @throws InsufficientFundsException When user has insufficient funds
 */
public function handle(
    PaymentRequest $request,
    MentorSession $session
): RedirectResponse {
    // Implementation
}
```

## Debugging та Troubleshooting

### Локальна розробка

#### Налагодження PHP

```bash
# Xdebug налаштований в Docker
# IDE підключення: host.docker.internal:9003

# Логи додатка
docker compose exec app tail -f storage/logs/laravel.log

# Тестування з покриттям
docker compose exec app ./vendor/bin/pest --coverage
```

#### Налагодження frontend

```javascript
// Vue DevTools доступні в development
console.log('Debug data:', props, state);

// Laravel Echo debugging
window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('WebSocket connected');
});
```

### Поширені проблеми

#### 1. Права доступу файлів

```bash
# Виправлення прав доступу
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

#### 2. Пакети не встановлюються

```bash
# Очищення кешу
docker compose exec app composer clear-cache
docker compose exec app yarn cache clean

# Переустановлення
docker compose exec app composer install --no-cache
docker compose exec app yarn install --frozen-lockfile
```

#### 3. Тести не проходять

```bash
# Перевірка environment для тестів
docker compose exec app cat .env.testing

# Очищення тестової БД
docker compose exec app php artisan migrate:fresh --env=testing

# Запуск конкретного тесту з деталізацією
docker compose exec app ./vendor/bin/pest tests/Feature/Auth/LoginTest.php -v
```

## Структура проекту для нових учасників

### Backend (Laravel)

```
app/
├── Actions/          # Бізнес-логіка (замість Controllers)
├── Models/           # Eloquent моделі
├── Http/
│   ├── Requests/     # Form Request класи
│   └── Middleware/   # Custom middleware
├── Policies/         # Авторизаційні політики
├── Enums/           # Перечислення
├── Observers/       # Model observers
└── Support/         # Допоміжні класи
```

### Frontend (Vue.js)

```
resources/js/
├── Components/       # Повторно використовувані компоненти
│   └── UI/          # Базові UI компоненти
├── Pages/           # Сторінки Inertia.js
├── Layouts/         # Лейаути додатка
└── Stores/          # Pinia stores
```

### Тести

```
tests/
├── Feature/         # Інтеграційні тести (HTTP endpoints)
├── Unit/           # Юніт тести (Actions, Models)
└── Datasets/       # Тестові дані для Pest
```

## Performance Guidelines

### Backend оптимізації

```php
// ✅ N+1 запитів - використовуйте eager loading
$mentors = User::with(['mentorProfile', 'mentorPrograms.tags'])->get();

// ✅ Кешування дорогих операцій
$stats = Cache::remember("dashboard.stats.{$user->id}", 300, function () use ($user) {
    return [
        'total_sessions' => $user->mentorSessions()->count(),
        'rating' => $user->mentorReviews()->avg('rating'),
    ];
});

// ✅ Query оптимізації
MentorProgram::query()
    ->select(['id', 'title', 'price', 'mentor_id']) // Тільки потрібні поля
    ->published()
    ->whereHas('mentor', function ($query) {
        $query->active();
    })
    ->limit(10)
    ->get();
```

### Frontend оптимізації

```vue
<script setup>
// ✅ Lazy loading компонентів
const HeavyComponent = defineAsyncComponent(
    () => import('./HeavyComponent.vue'),
);

// ✅ Обчислювані властивості для дорогих операцій
const filteredPrograms = computed(() =>
    props.programs.filter((p) => p.is_active && p.price <= maxPrice.value),
);
</script>

<template>
    <!-- ✅ v-show для часто перемикаємих елементів -->
    <div v-show="isVisible">Content</div>

    <!-- ✅ v-if для рідко змінюваних умов -->
    <div v-if="user.hasRole('admin')">Admin panel</div>

    <!-- ✅ Key для оптимізації списків -->
    <div v-for="item in items" :key="item.id">{{ item.name }}</div>
</template>
```

## Security Guidelines для розробників

### Завжди пам'ятайте

1. **Ніколи не довіряйте вхідним даним** - валідуйте все
2. **Використовуйте Eloquent** замість raw SQL
3. **Перевіряйте авторизацію** на кожному endpoint
4. **Логуйте security події**

```php
// ✅ Безпечний код
class UpdateMentorProgram
{
    public function handle(
        UpdateMentorProgramRequest $request, // Валідовані дані
        MentorProgram $mentorProgram
    ): RedirectResponse {
        // Перевірка авторизації
        $this->authorize('update', $mentorProgram);

        // Безпечне оновлення
        $mentorProgram->update($request->validated());

        return redirect()->back()->with('success', 'Program updated');
    }
}
```

## Релізний цикл

### Versioning

Проект використовує **Semantic Versioning**:

- `MAJOR.MINOR.PATCH` (наприклад, 1.2.3)
- Breaking changes → MAJOR
- Нові features → MINOR
- Bug fixes → PATCH

### Release Process

1. **Feature freeze** - зупинка нових features
2. **Testing phase** - інтенсивне тестування
3. **Release candidate** - RC версія для тестування
4. **Production release** - фінальний реліз
5. **Post-release monitoring** - моніторинг після релізу

## Спільнота та підтримка

### Канали зв'язку

- **GitHub Issues** - баг репорти та feature requests
- **GitHub Discussions** - загальні питання та обговорення
- **Slack/Discord** - швидка комунікація з командою

### Етикет

- **Будьте поважними** до всіх учасників
- **Питайте конкретно** - надайте контекст та деталі
- **Допомагайте іншим** - відповідайте на питання коли можете
- **Дотримуйтесь Code of Conduct**

## Релізний цикл

### Versioning

Проект використовує **Semantic Versioning**:

- `MAJOR.MINOR.PATCH` (наприклад, 1.2.3)
- Breaking changes → MAJOR
- Нові features → MINOR
- Bug fixes → PATCH

### Release Process

1. **Feature freeze** - зупинка нових features
2. **Testing phase** - інтенсивне тестування
3. **Release candidate** - RC версія для тестування
4. **Production release** - фінальний реліз
5. **Post-release monitoring** - моніторинг після релізу

## Дякую за ваш внесок!

Кожен внесок, великий чи малий, робить Mentor Wizard кращим для всієї спільноти
менторів та учнів. Ваша робота допомагає людям розвиватися професійно та
особисто.

**Happy coding!** 🚀
