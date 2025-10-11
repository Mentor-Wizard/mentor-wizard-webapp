# Архітектура Laravel Actions

## Огляд

Проект використовує **Laravel Actions** як основну архітектурну парадигму для
організації бізнес-логіки. Це альтернатива традиційним Controller'ам, яка
забезпечує кращу організацію коду та повторне використання логіки.

## Чому Laravel Actions?

### Переваги над традиційними Controller'ами

1. **Одна відповідальність** - кожен Action виконує одну конкретну дію
2. **Повторне використання** - Action можна викликати з різних контекстів
3. **Тестування** - простіше тестувати окремі дії
4. **Організація** - краща структура для складних додатків
5. **Типізація** - повна підтримка типів PHP

### Пакет

Використовується **lorisleiva/laravel-actions** - найпопулярніше рішення для
Laravel.

```json
"lorisleiva/laravel-actions": "^2.9"
```

## Структура Actions

### Директорії

```
app/Actions/
├── Auth/                    # Автентифікація та авторизація
│   ├── Login/              # Логіка входу
│   │   ├── Login.php       # Бізнес-дія входу
│   │   └── GetLoginPage.php # Відображення сторінки входу
│   ├── Register/           # Логіка реєстрації
│   ├── Reset/              # Скидання пароля
│   └── Socialite/          # Соціальна автентифікація
├── MentorPrograms/         # Програми менторингу
│   ├── StoreMentorProgramPage.php
│   ├── UpdateMentorProgramPage.php
│   └── DeleteMentorProgram.php
├── Pages/                  # Page Actions (відображення)
│   ├── DashboardPage.php
│   ├── Profile/
│   └── MentorProgram/
├── Profile/                # Профілі користувачів
├── User/                   # Користувачі
└── MentorTag/             # Теги менторів
```

## Типи Actions

### 1. Business Actions (Бізнес-дії)

**Призначення:** Виконання конкретної бізнес-логіки

```php
<?php

declare(strict_types=1);

namespace App\Actions\Auth\Login;

use App\Http\Requests\Auth\Login\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class Login
{
    use AsController;

    public function handle(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('pages.dashboard'));
    }
}
```

**Характеристики:**

- Назва: дієслово (Login, Register, UpdateUser)
- Один трейт: `AsController`
- Фокус на бізнес-логіці
- Повертає результат дії

### 2. Page Actions (Сторінки)

**Призначення:** Відображення сторінок через Inertia.js

```php
<?php

declare(strict_types=1);

namespace App\Actions\Pages;

use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class DashboardPage
{
    use AsController;

    public function handle(): Response
    {
        return Inertia::render('Dashboard');
    }
}
```

**Характеристики:**

- Назва: закінчується на `Page`
- Повертає `Inertia\Response`
- Відповідає за підготовку даних для frontend'у
- Мінімальна бізнес-логіка

### 3. Hybrid Actions (Гібридні)

**Призначення:** Комбінують бізнес-логіку та відображення

```php
<?php

declare(strict_types=1);

namespace App\Actions\MentorPrograms;

use App\Http\Requests\MentorProgram\StoreMentorProgramRequest;
use App\Models\MentorProgram;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class StoreMentorProgramPage
{
    use AsController;

    public function handle(StoreMentorProgramRequest $request): Response
    {
        // Бізнес-логіка
        MentorProgram::query()->create([
            ...$request->validated(),
            'mentor_id' => Auth::id(),
        ]);

        // Редирект
        return Inertia::location(route('mentor-program.create'));
    }
}
```

**Коли використовувати:**

- Прості CRUD операції
- Коли після дії потрібен редирект
- Коли логіка не потребує повторного використання

## Трейти Laravel Actions

### AsController

**Використання:** Для Action'ів, що викликаються через HTTP

```php
use Lorisleiva\Actions\Concerns\AsController;

class MyAction
{
    use AsController;

    public function handle(Request $request): Response
    {
        // Логіка
    }
}
```

### AsCommand

**Використання:** Для Action'ів, що викликаються через Artisan

```php
use Lorisleiva\Actions\Concerns\AsCommand;

class MyAction
{
    use AsCommand;

    public string $commandSignature = 'my:action {user}';
    public string $commandDescription = 'Description of my action';

    public function handle(User $user): void
    {
        // Логіка
    }
}
```

### AsListener

**Використання:** Для Action'ів, що слухають події

```php
use Lorisleiva\Actions\Concerns\AsListener;

class MyAction
{
    use AsListener;

    public function handle(UserRegistered $event): void
    {
        // Логіка
    }
}
```

### AsJob

**Використання:** Для Action'ів в черзі

```php
use Lorisleiva\Actions\Concerns\AsJob;

class MyAction
{
    use AsJob;

    public function handle(User $user): void
    {
        // Логіка
    }
}
```

### Комбінування трейтів

```php
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsJob;
use Lorisleiva\Actions\Concerns\AsCommand;

class ProcessPayment
{
    use AsController;
    use AsJob;
    use AsCommand;

    public string $commandSignature = 'payment:process {payment}';

    public function handle(Payment $payment): void
    {
        // Одна логіка для всіх контекстів
    }
}
```

## Конвенції іменування

### Business Actions

```php
// ✅ Правильно - дієслово + іменник
Login              // входити
Register           // реєструватися
UpdateUser         // оновити користувача
DeleteMentorProgram // видалити програму ментора
ProcessPayment     // обробити платіж

// ❌ Неправильно
UserLogin          // іменник + дієслово
LoginAction        // суфікс Action
HandleLogin        // префікс Handle
```

### Page Actions

```php
// ✅ Правильно - описова назва + Page
DashboardPage               // сторінка дашбоарту
GetLoginPage               // отримати сторінку входу
CreateMentorProgramPage    // створити програму ментора
ListMentorProfilePage      // список профілів менторів

// ❌ Неправильно
Dashboard          // без суфіксу Page
Login             // конфлікт з бізнес-діями
ShowDashboard     // непослідовність
```

## Організація за доменами

### Принцип групування

Actions групуються за **бізнес-доменами** з винятком Page Actions, які живуть в
окремому неймспейсі:

```
✅ Правильно (поточна структура проекту):
Auth/
├── Login/
├── Register/
└── Reset/

MentorPrograms/
├── Create/
├── Update/
└── Delete/

Pages/                    # Виняток - Page Actions в окремому неймспейсі
├── DashboardPage.php
├── Profile/
└── MentorProgram/

❌ Неправильно (змішування типів в доменах):
BusinessActions/
├── Login/
├── CreateProgram/
└── UpdateUser/
```

### Вкладеність

Для складних доменів використовується додаткова вкладеність в бізнес-логіці:

```
Auth/
├── Login/
│   └── Login.php              # Бізнес-дія
├── Register/
│   └── Registration.php       # Бізнес-дія
└── Socialite/
    ├── SocialiteRedirect.php  # Редирект на провайдера
    └── SocialiteCallback.php  # Обробка callback'а

Pages/                         # Page Actions окремо
├── Auth/
│   ├── GetLoginPage.php       # Сторінка входу
│   └── GetRegistrationPage.php # Сторінка реєстрації
├── Profile/
│   └── GetProfilePage.php
└── MentorProgram/
    ├── CreateMentorProgramPage.php
    └── ListMentorProgramPage.php
```

## Dependency Injection

### Конструктор

```php
class ProcessPayment
{
    use AsController;

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(PaymentRequest $request): Response
    {
        $payment = $this->paymentService->process(
            $request->user(),
            $request->validated('amount')
        );

        $this->notificationService->send(
            $payment->user,
            new PaymentProcessed($payment)
        );

        return redirect()->route('payments.success');
    }
}
```

### Method Injection

```php
class UpdateUser
{
    use AsController;

    public function handle(
        UpdateUserRequest $request,
        UserService $userService, // Method injection
    ): RedirectResponse {
        $userService->update(
            $request->user(),
            $request->validated()
        );

        return redirect()->route('profile.edit');
    }
}
```

## Валідація та Request класи

### Використання Form Requests

```php
class StoreMentorProgramPage
{
    use AsController;

    public function handle(StoreMentorProgramRequest $request): Response
    {
        // Валідація автоматично відбувається
        $validatedData = $request->validated();

        MentorProgram::query()->create([
            ...$validatedData,
            'mentor_id' => Auth::id(),
        ]);

        return Inertia::location(route('mentor-program.list'));
    }
}
```

### Form Request структура

```php
// app/Http/Requests/MentorProgram/StoreMentorProgramRequest.php
class StoreMentorProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('mentor');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Назва програми є обов\'язковою',
            'price.min' => 'Ціна не може бути від\'ємною',
        ];
    }
}
```

## Авторизація в Actions

### Через middleware в роутах

```php
// routes/web.php
Route::middleware(['auth', 'role:mentor'])
    ->patch('mentor-program/{mentorProgram:slug}', UpdateMentorProgramPage::class)
    ->can('update', 'mentorProgram')
    ->name('mentor-program.update');
```

### Через authorize в Action

```php
class DeleteMentorProgram
{
    use AsController;

    public function handle(MentorProgram $mentorProgram): RedirectResponse
    {
        $this->authorize('delete', $mentorProgram);

        $mentorProgram->delete();

        return redirect()->route('mentor-program.list')
            ->with('success', 'Програму успішно видалено');
    }
}
```

## Тестування Actions

### Unit тести

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

### Feature тести (HTTP)

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
        ->assertRedirect(route('mentor-program.create'));

    $this->assertDatabaseHas('mentor_programs', [
        'title' => 'Test Program',
        'mentor_id' => $mentor->getKey(),
    ]);
});
```

## Обробка помилок

### Стандартна обробка

```php
class ProcessPayment
{
    use AsController;

    public function handle(PaymentRequest $request): Response
    {
        try {
            $payment = $this->paymentService->process($request->validated());

            return redirect()->route('payments.success')
                ->with('success', 'Платіж успішно оброблено');
        } catch (InsufficientFundsException $e) {
            return back()
                ->withErrors(['payment' => 'Недостатньо коштів'])
                ->withInput();
        } catch (PaymentException $e) {
            return back()
                ->withErrors(['payment' => 'Помилка обробки платежу'])
                ->withInput();
        }
    }
}
```

### Глобальна обробка

```php
// bootstrap/app.php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (PaymentException $e, Request $request) {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Payment processing failed',
                'message' => $e->getMessage()
            ], 422);
        }

        return back()->withErrors(['payment' => $e->getMessage()]);
    });
})
```

## Performance considerations

### Lazy Loading

```php
class ListMentorProfilePage
{
    use AsController;

    public function handle(Request $request): Response
    {
        $profiles = MentorProfile::query()
            ->with(['user', 'tags']) // Eager loading
            ->when($request->get('search'), function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%");
                });
            })
            ->paginate(User::DEFAULT_MENTOR_PAGE_PAGINATION);

        return Inertia::render('MentorProfiles/Index', [
            'profiles' => $profiles,
            'filters' => $request->only('search'),
        ]);
    }
}
```

### Кешування

```php
class DashboardPage
{
    use AsController;

    public function handle(): Response
    {
        $stats = Cache::remember(
            "dashboard.stats.{auth()->id()}",
            now()->addMinutes(5),
            fn() => $this->generateStats()
        );

        return Inertia::render('Dashboard', compact('stats'));
    }

    private function generateStats(): array
    {
        return [
            'total_programs' => auth()->user()->mentorPrograms()->count(),
            'active_sessions' => auth()->user()->mentorSessions()->active()->count(),
        ];
    }
}
```

## Інтеграція з Inertia.js

### Передача даних

```php
class GetMentorProfilePage
{
    use AsController;

    public function handle(MentorProfile $mentor): Response
    {
        return Inertia::render('MentorProfile/Show', [
            'mentor' => new MentorProfilePageResource($mentor->load([
                'user',
                'tags',
                'reviews' => fn($q) => $q->latest()->limit(5)
            ])),
            'can' => [
                'edit' => auth()->user()?->can('update', $mentor),
                'delete' => auth()->user()?->can('delete', $mentor),
            ]
        ]);
    }
}
```

### Часткові оновлення

```php
class UpdateMentorProfilePage
{
    use AsController;

    public function handle(
        UpdateMentorProfileRequest $request,
        MentorProfile $mentorProfile
    ): Response {
        $mentorProfile->update($request->validated());

        // Часткове оновлення сторінки
        return back()->with('success', 'Профіль оновлено');
    }
}
```

## Best Practices

### 1. Одна відповідальність

```php
// ✅ Добре - одна чітка відповідальність
class Login
{
    public function handle(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        return redirect()->intended(route('pages.dashboard'));
    }
}

// ❌ Погано - кілька відповідальностей
class UserManagement
{
    public function handle($action, $data)
    {
        if ($action === 'login') { /* ... */ }
        if ($action === 'register') { /* ... */ }
        if ($action === 'update') { /* ... */ }
    }
}
```

### 2. Типізація

```php
// ✅ Добре - повна типізація
class UpdateUser
{
    public function handle(
        UpdateUserRequest $request,
        User $user
    ): RedirectResponse {
        // ...
    }
}

// ❌ Погано - відсутність типів
class UpdateUser
{
    public function handle($request, $user)
    {
        // ...
    }
}
```

### 3. Іммутабельність

```php
// ✅ Добре - повертаємо новий стан
class CalculateTotal
{
    public function handle(array $items): array
    {
        return [
            'items' => $items,
            'total' => array_sum(array_column($items, 'price')),
            'tax' => $this->calculateTax($total),
        ];
    }
}

// ❌ Погано - мутуємо вхідні дані
class CalculateTotal
{
    public function handle(array &$items): void
    {
        $items['total'] = array_sum(array_column($items, 'price'));
    }
}
```

### 4. Композиція

```php
class ProcessOrder
{
    use AsController;

    public function handle(OrderRequest $request): Response
    {
        // Композиція з інших Actions
        $payment = (new ProcessPayment())->handle($request->payment());
        $notification = (new SendOrderNotification())->handle($payment->order);

        return redirect()->route('orders.success');
    }
}
```

## Troubleshooting

### Поширені проблеми

1. **Action не знайдений в роутах**

    ```php
    // Перевірте неймспейс та назву класу
    Route::post('login', \App\Actions\Auth\Login\Login::class);
    ```

2. **Dependency Injection не працює**

    ```php
    // Перевірте, що сервіс зареєстрований в Service Container
    $this->app->bind(PaymentService::class, StripePaymentService::class);
    ```

3. **Валідація не спрацьовує**
    ```php
    // Перевірте, що використовується правильний Form Request
    public function handle(CorrectRequest $request) // не Request
    ```

Архітектура Laravel Actions забезпечує чистий, тестований та масштабований код
для Mentor Wizard проекту.
