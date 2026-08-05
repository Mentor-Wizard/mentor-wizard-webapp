# Конвенції іменування та структури коду

## Загальні принципи

### Неймспейси (Namespaces)

Всі класи проєкту використовують базовий неймспейс `App` і слідують структурі
директорій:

```
App\                          # Базовий неймспейс застосунку
├── Actions\                  # Дії (бізнес-логіка)
├── Enums\                    # Переліки (enum класи)
├── Filament\                 # Компоненти Filament адміністрування
├── Filters\                  # Фільтри для пошуку і сортування
├── Http\                     # HTTP компоненти
├── Models\                   # Eloquent моделі
├── Observers\                # Спостерігачі моделей
├── Policies\                 # Політики авторизації
├── Providers\                # Постачальники послуг Laravel
├── Services\                 # Сервісні класи (за потреби)
├── Support\                  # Допоміжні класи
└── Traits\                   # Трейти для повторного використання коду
```

### Строге типування

Всі PHP файли **обов'язково** повинні містити декларацію строгих типів:

```php
<?php

declare(strict_types=1);
```

## Структура Actions (Дії)

### Конвенції іменування Actions

Actions організовані за доменами та функціональністю:

```
App\Actions\
├── Auth\                     # Автентифікація
│   ├── Login\               # Групування операцій входу
│   ├── Register\            # Групування операцій реєстрації
│   ├── Reset\               # Скидання пароля
│   └── Socialite\           # Соціальна автентифікація
├── MentorPrograms\          # Програми менторинг
├── MentorTag\               # Теги менторів
├── Pages\                   # Page Actions (відображення сторінок)
├── Profile\                 # Профілі користувачів
└── User\                    # Операції з користувачами
```

### Іменування Action класів

- **Page Actions** (відображення сторінок): закінчуються на `Page`
  - `GetLoginPage` - отримання сторінки входу
  - `DashboardPage` - сторінка дашборду
  - `CreateMentorProgramPage` - сторінка створення програми

- **Business Actions** (бізнес операції): дієслово + іменник
  - `Login` - операція входу
  - `Registration` - операція реєстрації
  - `UpdateUser` - оновлення користувача
  - `DeleteMentorProgram` - видалення програми ментора

### Трейти для Actions

Базові Action класи використовують трейт `AsController` від
`lorisleiva/laravel-actions`:

```php
use Lorisleiva\Actions\Concerns\AsController;

class Login
{
    use AsController;

    public function handle(LoginRequest $request): RedirectResponse
    {
        // Бізнес логіка
    }
}
```

Але, якщо Action клас не являється контроллером, слід використовувати
відповідний трейт від `lorisleiva/laravel-actions`.

## Сервісні класи

### Коли використовувати Services замість Actions

В даному проєкті **пріоритет віддається Actions** за патерном Laravel Actions.
Сервісні класи використовуються тільки у специфічних випадках:

1. **Інтеграція з зовнішніми API** (платіжні системи, email сервіси)
2. **Складна бізнес-логіка**, що не підходить для Actions
3. **Utility класи** для роботи з файлами, датами, форматуванням

### Структура Services (за потреби)

```
App\Services\
├── Payment\                  # Платіжні сервіси
│   ├── StripeService
│   └── PayPalService
├── Notification\            # Сервіси сповіщень
│   ├── EmailService
│   └── SmsService
├── External\                # Зовнішні API
│   ├── GoogleMapsService
│   └── SlackService
└── Utility\                 # Допоміжні сервіси
    ├── FileUploadService
    └── ImageProcessingService
```

### Конвенції іменування Services

- **Назва**: `{Призначення}Service`
  - `PaymentService` - робота з платежами
  - `NotificationService` - сповіщення
  - `FileUploadService` - завантаження файлів

### Приклад структури Service класу

```php
<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\User;
use App\Models\Payment;

class StripeService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $webhookSecret,
    ) {}

    public function createPayment(User $user, int $amount): Payment
    {
        // Логіка створення платежу
    }

    public function processWebhook(string $payload, string $signature): bool
    {
        // Обробка webhook
    }
}
```

### Реєстрація Services в Service Container

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->singleton(StripeService::class, function (Application $app): StripeService {
        return new StripeService(
            apiKey: config('services.stripe.secret'),
            webhookSecret: config('services.stripe.webhook_secret'),
        );
    });
}
```

### Використання Services в Actions

```php
class ProcessPaymentAction
{
    use AsController;

    public function handle(
        PaymentRequest $request,
        StripeService $stripeService,
    ): RedirectResponse {
        $payment = $stripeService->createPayment(
            user: $request->user(),
            amount: $request->validated('amount'),
        );

        return redirect()->route('payment.success');
    }
}
```

## Структура моделей

### Конвенції іменування моделей

Моделі використовують однину в назві та PascalCase:

- `User` - користувач
- `MentorProgram` - програма ментора
- `MentorProfile` - профіль ментора
- `ChatMessage` - повідомлення чату

### Attributes і константи в моделях

```php
class User extends Authenticatable
{
    // Константи в SCREAMING_SNAKE_CASE
    public const int MIN_PASSWORD_LENGTH = 8;
    public const int DEFAULT_MENTOR_PAGE_PAGINATION = 10;

    // Використання методу casts() замість властивості $casts
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
```

### Observers і Factory

Моделі використовують PHP 8 атрибути для конфігурації:

```php
#[ObservedBy(UserObserver::class)]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
```

## HTTP компоненти

### Request класи

Request класи організовані за доменами і операціями:

```
App\Http\Requests\
├── Auth\
│   ├── Login\
│   │   └── LoginRequest
│   ├── Register\
│   │   └── RegistrationRequest
│   └── Reset\
│       └── ResetPasswordRequest
├── MentorProgram\
│   ├── StoreMentorProgramRequest
│   └── UpdateMentorProgramRequest
└── UserProfile\
    ├── UpdateUserProfileRequest
    └── DeleteUserProfileRequest
```

### API Resources

API Resources мають суфікс `Resource`:

- `MentorProfilePageResource`
- `MentorProgramsResource`
- `UserProfileResource`

## Filament компоненти

### Структура Filament Resources

```
App\Filament\Resources\
└── User\                     # Ресурс по домену
    ├── Pages\               # Сторінки ресурсу
    │   ├── CreateUser
    │   ├── EditUser
    │   └── ListUsers
    ├── Schemas\             # Схеми форм
    │   └── UserForm
    ├── Tables\              # Таблиці
    │   └── UsersTable
    └── UserResource         # Головний клас ресурсу
```

### Конвенції Filament

- Ресурси: `{Model}Resource` - `UserResource`
- Сторінки: `{Action}{Model}` - `CreateUser`, `EditUser`, `ListUsers`
- Схеми: `{Model}Form` - `UserForm`
- Таблиці: `{Model}sTable` - `UsersTable`

## Енуми (Enums)

### Іменування Enums

Всі енуми мають суфікс `Enum`:

- `CurrencyEnum`
- `RoleEnum`
- `RoleGuardEnum`
- `SocialiteDriverEnum`
- `TagEnum`

### Структура Enum

```php
enum RoleEnum: string
{
    case ADMIN = 'admin';
    case MENTOR = 'mentor';
    case MENTI = 'menti';
}
```

## Фільтри і допоміжні класи

### Фільтри

Фільтри іменуються за призначенням + `Filter`:

- `ProfileRateFilter` - фільтр за рейтингом профілю
- `ProgramCostFilter` - фільтр за вартістю програми
- `TagLanguagesFilter` - фільтр за мовами в тегах

### Трейти

Трейти описують поведінку і іменуються дієсловом:

- `ParsesNumericRange` - парсить числовий діапазон

### Support класи

Допоміжні класи в `App\Support\` організовані за призначенням:

- `App\Support\MediaLibrary\PathGenerator`

## Providers (Постачальники послуг)

### Структура Providers

```
App\Providers\
├── AppServiceProvider       # Основний постачальник
├── TelescopeServiceProvider # Telescope (debug)
└── Filament\               # Filament провайдери
    └── AdminPanelProvider
```

### Конвенції Providers

- Всі провайдери закінчуються на `ServiceProvider` або `Provider`
- Filament провайдери мають суфікс `PanelProvider`

## Маршрутизація (Routes)

### Структура файлів маршрутів

```
routes/
├── web.php          # Основні веб-маршрути
├── auth.php         # Маршрути автентифікації
├── channels.php     # Broadcasting канали
└── console.php      # Консольні команди
```

### Конвенції іменування маршрутів

#### Публічні сторінки

```php
Route::get('/', WelcomePage::class)->name('pages.welcome');
Route::get('mentor/{mentor:slug}', GetMentorProfilePage::class)->name('page.mentor');
```

#### Автентифікація (без префіксу в назві)

```php
Route::get('login', GetLoginPage::class)->name('login');
Route::post('login', Login::class)->name('login.attempt');
Route::get('register', GetRegistrationPage::class)->name('register');
```

#### Ресурсні маршрути

```php
Route::get('mentor-program/create', CreateMentorProgramPage::class)
    ->name('mentor-program.create');
Route::post('mentor-program', StoreMentorProgramPage::class)
    ->name('mentor-program.store');
Route::patch('mentor-program/{mentorProgram:slug}', UpdateMentorProgramPage::class)
    ->name('mentor-program.update');
```

### Групування маршрутів

#### За middleware

```php
Route::middleware('auth')->group(function (): void {
    // Маршрути для автентифікованих користувачів
});

Route::middleware(['auth', 'role:mentor'])->group(function (): void {
    // Маршрути тільки для менторів
});
```

#### За префіксом

```php
Route::prefix('auth')->group(function (): void {
    Route::get('redirect/{driver}', SocialiteRedirect::class)
        ->name('auth.socialite.redirect');
});
```

### Model Binding

Використовується Route Model Binding з slug:

```php
Route::get('mentor/{mentor:slug}', GetMentorProfilePage::class);
Route::patch('mentor-program/{mentorProgram:slug}', UpdateMentorProgramPage::class);
```

## Конфігураційні файли

### Організація конфігурацій

Конфігураційні файли в `config/` дотримуються стандартної структури Laravel:

```
config/
├── app.php              # Основні налаштування застосунку
├── auth.php             # Автентифікація
├── broadcasting.php     # Broadcasting налаштування
├── cache.php            # Кешування
├── database.php         # База даних
├── filesystems.php      # Файлові системи
├── logging.php          # Логування
├── mail.php             # Пошта
├── queue.php            # Черги
├── session.php          # Сесії
├── services.php         # Зовнішні сервіси
├── telescope.php        # Laravel Telescope
├── octane.php           # Laravel Octane
├── pulse.php            # Laravel Pulse
├── reverb.php           # Laravel Reverb
├── media-library.php    # Spatie Media Library
├── permission.php       # Spatie Permission
├── query-builder.php    # Spatie Query Builder
├── log-viewer.php       # Log Viewer
└── ide-helper.php       # IDE Helper
```

### Використання environment змінних

```php
// В конфігураційних файлах
'name' => env('APP_NAME', 'Laravel'),
'debug' => (bool) env('APP_DEBUG', false),

// В коді використовуємо config() функцію
config('app.name')        // НЕ env('APP_NAME')
config('app.debug')       // НЕ env('APP_DEBUG')
```

## Vue.js компоненти (Frontend)

### Структура компонентів

```
resources/js/
├── Components/
│   ├── UI/                  # UI компоненти
│   │   ├── Button/         # Кнопки
│   │   ├── Forms/          # Форми
│   │   ├── Logo/           # Логотипи
│   │   └── Table/          # Таблиці
│   ├── Navigation/         # Навігація
│   │   ├── Navbar/
│   │   └── Footer.vue
│   └── Modal.vue
├── Pages/                  # Сторінки Inertia.js
│   ├── Auth/
│   ├── Profile/
│   └── MentorProgram/
└── Layouts/               # Лейаути
    ├── AuthenticatedLayout.vue
    ├── GuestLayout.vue
    └── LandingLayout.vue
```

> Ця структура — для доменів рівня застосунку. Домен, винесений у
> `Modules/{Name}/` (наприклад `Chat`, `Calendar`), тримає власні
> `Pages/`/`Components/` у `Modules/{Name}/resources/js/` — модуль завжди
> включає і бекенд, і Inertia-фронтенд домену (`docs/MODULAR_ARCHITECTURE.md`).

### Іменування Vue компонентів

- **PascalCase** для назв файлів: `SecondaryButton.vue`, `TextInput.vue`
- **Групування** за функціональністю: `UI/Button/`, `UI/Forms/`
- **Лейаути**: закінчуються на `Layout` - `AuthenticatedLayout.vue`

### Сторінки Inertia.js

Сторінки організовані за доменами:

- `Pages/Auth/Login.vue` - сторінка входу
- `Pages/Profile/Edit.vue` - редагування профілю
- `Pages/MentorProgram/List.vue` - список програм

## Observers і Policies

### Observers

Спостерігачі іменуються за моделлю + `Observer`:

- `UserObserver`
- `MentorProgramObserver`
- `ChatObserver`

### Policies

Політики іменуються за моделлю + `Policy`:

- `MentorProgramPolicy`

## Загальні правила коду

### Властивості та методи класу

1. **Константи** - в SCREAMING_SNAKE_CASE
2. **Властивості** - в camelCase
3. **Методи** - в camelCase
4. **Класи** - в PascalCase

### Документація

Використовуємо PHPDoc для складних випадків:

```php
/**
 * @property-read UserProfile $profile
 * @property-read MentorProfile|null $mentorProfile
 * @property-read float $rating
 * @property string $username
 *
 * @mixin IdeHelperUser
 */
class User extends Authenticatable
```

### Return типи

Всі методи мають явні return типи:

```php
public function handle(LoginRequest $request): RedirectResponse
public function profile(): HasOne
public function getFilamentName(): string
```

## Рекомендації по архітектурі

### Actions vs Services vs Controllers

1. **Actions** (пріоритет) - для всієї бізнес-логіки та відображення сторінок
2. **Services** - тільки для зовнішніх інтеграцій та складних utility функцій
3. **Controllers** - не використовуються, замість них Actions з трейтом
   `AsController`

### Dependency Injection

Використовуємо конструктор або метод injection:

```php
// Конструктор injection для Services
public function __construct(
    private readonly PaymentService $paymentService,
) {}

// Метод injection для Actions
public function handle(LoginRequest $request, UserService $userService): RedirectResponse
```

Ця документація описує основні конвенції кодування проєкту Mentor Wizard. При
розробці нових функцій слідуйте цим правилам для підтримки консистентності коду.
