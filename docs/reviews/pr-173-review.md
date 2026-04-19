# PR #173 — Calendars Integrations — Code Review

**Гілка:** `feature/163-calendars-integrations` → `develop` **Автор:** Asafailo
(vadym) **CI статус:** зелений (PHPStan L7, Pint, Rector, Pest unit / feature /
mutation / coverage) **Обсяг:** інтеграція зовнішніх календарів (Google спільний
OAuth, Google OAuth для окремого користувача, Outlook OAuth, Apple CalDAV),
шифрування облікових даних, sync-jobs, підключення через observer,
artisan-команда для розшифровки тестових токенів.

---

## Загальний висновок

PR додає суттєвий та добре змодельований шар інтеграцій: чиста абстракція
`OAuthCalendarServiceInterface`, сервіси для кожного провайдера, окремий
`CalendarCredentialEncrypter` з ротацією ключів, cast
`EncryptedCalendarCredential`, fan-out через observer у dedicated process-jobs
та batch-job для повторного шифрування. Тестове покриття значне, CI зелений.

Проте кілька проблем блокують злиття:

- Широкий патерн `catch (Throwable)` у sync-jobs руйнує контракт черги —
  `$tries` та `$backoff` є мертвою конфігурацією.
- Artisan-команда у production-коді розшифровує та виводить OAuth-облікові дані
  в stdout без перевірки середовища.
- Конвенції проєкту для `getKey()` / `getKeyName()` / `query()` порушені в
  кількох місцях.
- Авторизація у `ConfirmCalendarEvent` прив'язана до route model binding та
  `auth()->id()`, а не до Form Request / Policy — будь-який автентифікований
  користувач може підтвердити будь-яку подію.
- Nullable integration без перевірки передається до конструктора job, що очікує
  non-nullable модель.
- `disconnect()` лишає orphaned рядки `ExternalCalendarEvent`, які блокують
  подальшу ресинхронізацію.
- Відсутній throttle на ендпоінтах, що викликають зовнішні API.

### Кількість за серйозністю

| Серйозність | Кількість |
| ----------- | --------- |
| Critical    | 4         |
| Important   | 9         |
| Suggestion  | 5         |

### Вердикт

**Request Changes.** Критичні проблеми мають бути вирішені до злиття. Важливі
проблеми слід виправити в цьому PR або зафіксувати як негайні follow-up задачі з
призначеними виконавцями.

---

## Знахідки

### Critical

---

#### C1. Queue jobs ковтають винятки — retry та `failed()` ніколи не спрацьовують

**Файли:**

- `app/Jobs/CreateExternalCalendarEvent.php`
- `app/Jobs/UpdateExternalCalendarEvent.php`
- `app/Jobs/DeleteExternalCalendarEvent.php`

Всі три jobs оголошують конфігурацію retry, а потім обгортають весь `handle()` у
`try / catch (Throwable)`, логують + зберігають помилку і повертаються
нормально.

```php
public int $tries = 3;
public int $backoff = 60;

public function handle(): void
{
    try {
        // ... виклик зовнішнього API ...
    } catch (Throwable $throwable) {
        // log + ExternalCalendarEventLog create
        // NO rethrow
    }
}
```

Черга вважає кожну спробу успішною; worker ніколи не повторює спробу, `failed()`
не запускається, і Horizon / таблиця `failed_jobs` ніколи не бачать помилку.
`$tries = 3` та `$backoff = 60` — це оманлива конфігурація.

**Виправлення:** оберіть один контракт і дотримуйтесь його.

- Якщо retry потрібні (наприклад, для тимчасових Google 5xx / 429): перекиньте
  виняток після збереження log-рядка, та перемістіть запис log-рядка до callback
  `failed(Throwable $e)`, щоб він спрацьовував лише при фінальній спробі:

  ```php
  public function handle(): void
  {
      $service = resolve($this->integration->provider->getService());
      $externalEventId = $service->createEvent($this->calendarEvent, $this->integration);
      // ... success path ...
  }

  public function failed(Throwable $e): void
  {
      // mark ExternalCalendarEvent sync_status = Error, insert log row
  }
  ```

- Якщо single-attempt-and-log є навмисним: встановіть `public int $tries = 1;`
  та видаліть `$backoff`.

Поточний код — найгірше з обох варіантів: retry налаштовані, але ніколи не
відбуваються.

---

#### C2. `DecryptTestTokensCommand` потрапляє у production без перевірки середовища

**Файл:** `app/Console/Commands/Calendar/DecryptTestTokensCommand.php`

Команда розшифровує облікові дані `TEST_OUTLOOK_*`, `TEST_GOOGLE_*`,
`TEST_APPLE_*` з конфігурації і виводить їх у вигляді таблиці в stdout:

```php
protected $signature = 'calendar:decrypt-test-tokens';

public function handle(CalendarCredentialEncrypter $encrypter): int
{
    // ... loops over self::TOKENS, decrypts each, appends to $rows ...
    $this->table(['Key', 'Value'], $rows);
    return self::SUCCESS;
}
```

Будь-хто з доступом до shell у production-контейнері (оператори,
deploy-пайплайни, скомпрометований CI, зловмисник, що використав інший RCE) може
запустити `php artisan calendar:decrypt-test-tokens` і прочитати OAuth access +
refresh токени, client id та Apple app passwords у відкритому вигляді. Навіть
якщо спільний тестовий акаунт зараз не є production-акаунтом, це заряджена зброя
в репозиторії.

**Виправлення:** або видаліть команду з production-коду (перемістіть до
test-helper, tinker-скрипту або dev-only пакету, що не завантажується у prod),
або додайте явну перевірку:

```php
public function handle(CalendarCredentialEncrypter $encrypter): int
{
    if (! app()->environment('local', 'testing')) {
        $this->error('This command is only available in local/testing environments.');
        return self::FAILURE;
    }
    // ...
}
```

Також розгляньте умовну реєстрацію команди в `Kernel::commands()` залежно від
середовища, щоб вона не відображалась у `php artisan list` у production.

---

#### C3. `ConfirmCalendarEvent` — відсутня авторизація

**Файл:** `app/Actions/Calendar/CalendarEvent/ConfirmCalendarEvent.php`

Маршрут (у `routes/web.php`) має `->can('update', 'calendarEvent')` — це добре,
але сама action припускає, що автентифікований викликач авторизований для
_підтвердження від імені ментора_:

```php
$calendarEvent->calendarEventUsers()
    ->updateExistingPivot(auth()->id(), [
        'confirmed_at' => now(),
    ]);
```

Якщо policy дозволяє будь-якому pivot-учаснику (participant, co-host, host)
`update` подію, поточний flow охоче запише `confirmed_at` для PARTICIPANT, а
потім перевірить:

```php
if (! $calendarEvent->calendarEventUsers()
    ->wherePivotIn('role', [HOST, COHOST])
    ->wherePivotNull('confirmed_at')
    ->exists()) {
    $calendarEvent->update(['status' => CONFIRMED]);
}
```

— тобто клік учасника може підтвердити свій рядок, але логіка воротаря повністю
залежить від жорсткості policy. Всередині action немає явної перевірки ролі. В
поєднанні з файлом policy (не показаним у цьому review), це ненадійна
конструкція.

**Виправлення:** або

- перемістіть перевірку ролі до `authorize()` Form Request
  `ConfirmCalendarEvent` (учасник без ролі HOST/COHOST → 403), або
- посиліть `CalendarEventPolicy::update()`, щоб вимагати ролі HOST/COHOST перед
  допуском до маршруту, та перевіряйте роль викликача всередині action.

Додатково використовуйте `$request->user()` всередині action замість
`auth()->id()` / `auth()->user()` — дивіться I4.

---

#### C4. `ExternalCalendarSyncSingleEvent` — null integration потрапляє у non-nullable конструктор job

**Файл:**
`app/Actions/Calendar/ExternalCalendar/ExternalCalendarSyncSingleEvent.php`

```php
/** @var UserCalendarIntegration $integration */
$integration = UserCalendarIntegration::query()
    ->where('user_id', $user->getKey())
    ->where('provider', $calendarProvider)
    ->where('sync_status', CalendarSyncStatusEnum::Active)
    ->first();

dispatch(new CreateExternalCalendarEvent($calendarEvent, $integration));
```

`first()` повертає `UserCalendarIntegration|null`. PHPDoc `@var` замовчує
PHPStan, але не змінює поведінку в runtime.
`CreateExternalCalendarEvent::__construct` типізує параметр як
`UserCalendarIntegration`, тому передача `null` спричинить `TypeError` при
dispatch.

`ExternalCalendarSyncSingleEventRequest::withValidator` перевіряє
`integrationExists` — але `integrationExists` використовує `exists()`, а
`->first()` виконується окремим запитом, тому можливе TOCTOU-вікно між
валідацією та action (запис може бути soft-deleted або sync_status змінено іншим
процесом). Крім того, якщо валідатор запиту буде оминутий або правила зміняться,
action не має власного захисного рубежу.

**Виправлення:** використовуйте `firstOrFail()` та видаліть оманливий `@var`:

```php
$integration = UserCalendarIntegration::query()
    ->where('user_id', $user->getKey())
    ->where('provider', $calendarProvider)
    ->where('sync_status', CalendarSyncStatusEnum::Active)
    ->firstOrFail();
```

Обробіть `ModelNotFoundException` на рівні Form Request або зловіть і
перенаправте з дружнім flash-повідомленням.

---

### Important

---

#### I1. `$calendarEvent->id` використовується замість `$calendarEvent->getKey()`

**Файл:** `app/Actions/Calendar/CalendarEvent/ConfirmCalendarEvent.php:28`

```php
->whereNotIn('calendar_event_id', [$calendarEvent->id])   // ← порушення
    ->where('status', CalendarEventStatusEnum::CONFIRMED->value)
```

Пізніше в тому ж класі (`checkEventsForCancellation`) код коректно використовує
`$calendarEvent->getKey()`. Непослідовність всередині одного файлу.

**Виправлення:** замініть на `$calendarEvent->getKey()`. Також
`pluck('calendar_events.id')` далі в `checkEventsForCancellation` — перевагу
надайте `pluck($calendarEvent->getKeyName())` або відповідному key-helper, якщо
хочете суворого дотримання конвенцій.

---

#### I2. `CalendarEventObserver` викликає action через `new` замість контейнера

**Файл:** `app/Observers/CalendarEventObserver.php:32`

```php
(new CreateMentorSessionForCalendarEvent)->handle($event);
```

Це обходить service container — будь-яка залежність у конструкторі зламається
мовчки (Laravel Actions часто резолвляться через `app()`), і виклик неможливо
замокати в ізоляції. Те ж порушення є в
`app/Actions/Calendar/CalendarEvent/StoreCalendarEvent.php:46`:

```php
(new CreateMentorSessionForCalendarEvent)->handle($calendarEvent);
```

**Виправлення:** використовуйте статичний helper Laravel Actions:

```php
CreateMentorSessionForCalendarEvent::run($event);
```

`::run()` — це вбудований механізм `lorisleiva/laravel-actions`, що резолвить
клас через контейнер. Використання `app()` або `resolve()` напряму є service
locator і не відповідає конвенціям проєкту.

---

#### I3. `ExternalCalendarSynchronizationService::disconnect()` лишає orphaned рядки `ExternalCalendarEvent`

**Файл:**
`app/Services/ExternalCalendar/ExternalCalendarSynchronizationService.php:63-69`

```php
public function disconnect(User $user, CalendarProviderEnum $provider): void
{
    UserCalendarIntegration::query()
        ->where('user_id', $user->getKey())
        ->where('provider', $provider)
        ->delete();
}
```

Рядки `ExternalCalendarEvent` та `ExternalCalendarEventLog` для цієї пари (user,
provider) не видаляються. Наслідки:

- `ExternalCalendarRetrySync::buildSyncJobs()` використовує
  `whereNotIn('id', $syncedSubquery)` — будь-яке повторне підключення
  пропускатиме події, що виглядають "вже синхронізованими" через застарілі
  рядки, які більше не вказують на реальні зовнішні події.
- Логи видаленої інтеграції залишаються прив'язаними до користувача через
  `user_id`, але без живого FK, забруднюючи UI, що відображає sync-historyію.
- Черга завдань може мати в польоті `ProcessDeleteExternalCalendarEvent` та
  споріднені jobs у момент відключення — ці jobs тепер виконуються проти
  неіснуючої інтеграції.

**Виправлення:** виконайте очищення в одній транзакційній action. Спочатку
визначте policy:

- Якщо повторне підключення має відновлюватись чисто: видаліть рядки
  `ExternalCalendarEvent` (sync log записи можна каскадно видалити або зберегти
  для аудиту).
- Якщо користувач повинен бачити historичні логи: збережіть
  `ExternalCalendarEventLog`, видаліть рядки `ExternalCalendarEvent`, додайте
  info-лог "Integration disconnected — external tracking removed."

Також розгляньте додавання DB-level cascade на
`external_calendar_events.user_calendar_integration_id` (якщо такий FK існує;
якщо ні — він, мабуть, повинен), щоб очищення було атомарним навіть якщо код
програми забуде про нього.

---

#### I4. Actions використовують `auth()` / `auth()->user()` замість `$request->user()`

**Файли:**

- `app/Actions/Calendar/CalendarEvent/ConfirmCalendarEvent.php:58, 65`
- `app/Actions/Calendar/CalendarEvent/StoreCalendarEvent.php:38, 39`

```php
$calendarEvent->calendarEventUsers()
    ->updateExistingPivot(auth()->id(), ['confirmed_at' => now()]);
```

```php
if ($data->mentorProgram->mentor_id !== auth()->user()->getKey()) {
    $calendarEvent->calendarEventUsers()->attach(auth()->user()->getKey(), [ ... ]);
}
```

Actions, що використовують `AsController`, вже отримують `Request`.
Автентифікований користувач повинен передаватись через об'єкт запиту
(`$request->user()`), щоб action була детермінованою, unit-тестованою без
`actingAs` та узгодженою з рештою кодової бази.

**Виправлення:** ін'єктуйте запит у `handle()` (або приймайте `User` напряму),
читайте `$request->user()`, передавайте явно:

```php
public function handle(ConfirmCalendarEventRequest $request, MentorProgram $mentorProgram, CalendarEvent $calendarEvent): Response
{
    $user = $request->user();
    // ...
    $calendarEvent->calendarEventUsers()
        ->updateExistingPivot($user->getKey(), ['confirmed_at' => now()]);
}
```

Також зверніть увагу, що `ConfirmCalendarEvent::handle()` взагалі не має Form
Request — route model binding не покриває авторизацію-як-учасника (дивіться C3).

---

#### I5. `AbstractGoogleExternalCalendarService::handleCallback()` використовує `firstOrFail()` на публічному callback-шляху

**Файл:**
`app/Services/ExternalCalendar/AbstractGoogleExternalCalendarService.php:58-61`

```php
$integration = UserCalendarIntegration::query()
    ->where('user_id', $user->getKey())
    ->where('provider', $this->provider())
    ->firstOrFail();
```

Це досягається через OAuth redirect callback. Якщо попередній крок
`saveCredentials()` не виконувався (наприклад, користувач вручну сформував
callback URL або стан сесії оминув flow `saveCredentialsAndBuildOAuthUrl`),
кидається `ModelNotFoundException`, що рендерить стандартну 404 Laravel для
кінцевого користувача — розкриває спосіб відмови та не дає шляху назад до UI.

**Виправлення:** поверніть доменно-специфічний результат:

```php
$integration = UserCalendarIntegration::query()
    ->where('user_id', $user->getKey())
    ->where('provider', $this->provider())
    ->first();

if (! $integration instanceof UserCalendarIntegration) {
    throw new IntegrationNotInitialisedException(...);
}
```

…та зловіть типізований виняток у `ExternalCalendarConnectCallback::handle()`
поряд з наявною обробкою `Exception`, щоб користувач отримав дружній редирект.

Також зверніть увагу, що відповідь `Http::post(self::TOKEN_URL, ...)` не
перевіряється перед читанням `$data['access_token'] ?? null` — якщо Google
поверне помилку, інтеграція мовчки оновлюється з `access_token: null` та
`sync_status: Pending`, і користувач бачить непрозору помилку пізніше. Додайте
`if (! $response->successful()) throw new RuntimeException(...)` перед
використанням `$data`.

---

#### I6. Відсутній throttle на ендпоінтах, що тригерять синхронізацію

**Файл:** `routes/web.php` (група `settings/external-calendar`, рядки ~123-137)

```php
Route::post('retry/{provider}', ExternalCalendarRetrySync::class)
    ->name('external-calendar.retry');
Route::post('sync-event/{calendarEvent}/{provider}', ExternalCalendarSyncSingleEvent::class)
    ...
```

Кожний запит тригерує зовнішні виклики до Google / Outlook / Apple. Без
throttling зловмисний або баговий клієнт може вичерпати OAuth-квоту Google,
спричинити тимчасове блокування спільного client id проєкту або збільшити
витрати на користувача.

**Виправлення:** додайте іменовані throttle:

```php
Route::post('retry/{provider}', ExternalCalendarRetrySync::class)
    ->middleware('throttle:calendar-retry') // наприклад, 6/хв на користувача
    ->name('external-calendar.retry');

Route::post('sync-event/{calendarEvent}/{provider}', ExternalCalendarSyncSingleEvent::class)
    ->middleware('throttle:calendar-sync')  // наприклад, 30/хв на користувача
    ->name('external-calendar.sync-event');
```

Зареєструйте limiter'и в `AppServiceProvider` або окремому
`RouteServiceProvider::configureRateLimiting()`:

```php
RateLimiter::for('calendar-retry', fn (Request $r) => Limit::perMinute(6)->by($r->user()->getKey()));
RateLimiter::for('calendar-sync',  fn (Request $r) => Limit::perMinute(30)->by($r->user()->getKey()));
```

---

#### I7. `resolve()` використовується як service locator замість DI

**Файли:**

- `app/Jobs/CreateExternalCalendarEvent.php:53`
- `app/Jobs/UpdateExternalCalendarEvent.php:57`
- `app/Jobs/DeleteExternalCalendarEvent.php:65`
- `app/Casts/EncryptedCalendarCredential.php:17, 24`
- `app/Services/ExternalCalendar/ExternalCalendarSynchronizationService.php:111`

```php
// У Jobs:
$service = resolve($this->integration->provider->getService());

// У Cast:
return resolve(CalendarCredentialEncrypter::class)->decrypt((string) $value);

// У Service:
return resolve($provider->getService());
```

`resolve()` — це service locator: він приховує залежності, унеможливлює
тестування класу в ізоляції без реального контейнера та є кодовим запахом. У
всьому решті кодової бази `resolve()` не використовується для DI жодного разу.

**Виправлення для `EncryptedCalendarCredential` cast:**

Laravel резолвить cast-класи через IoC-контейнер, тому constructor injection
працює:

```php
class EncryptedCalendarCredential implements CastsAttributes
{
    public function __construct(
        private readonly CalendarCredentialEncrypter $encrypter,
    ) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->encrypter->decrypt((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->encrypter->encrypt((string) $value);
    }
}
```

**Виправлення для Jobs та `ExternalCalendarSynchronizationService`:**

Проблема тут у тому, що сервіс визначається динамічно на основі runtime-значення
`$integration->provider`. Правильне рішення — ввести фабрику:

```php
// app/Services/ExternalCalendar/ExternalCalendarServiceFactory.php
class ExternalCalendarServiceFactory
{
    public function __construct(
        private readonly GoogleAppExternalCalendarService $google,
        private readonly GoogleExternalCalendarService $googlePersonal,
        private readonly OutlookExternalCalendarService $outlook,
        private readonly AppleCalDavExternalCalendarService $apple,
    ) {}

    public function for(CalendarProviderEnum $provider): ExternalCalendarServiceInterface
    {
        return match ($provider) {
            CalendarProviderEnum::Google            => $this->google,
            CalendarProviderEnum::GooglePersonalApp => $this->googlePersonal,
            CalendarProviderEnum::Outlook           => $this->outlook,
            CalendarProviderEnum::Apple             => $this->apple,
        };
    }
}
```

У Jobs inject фабрику через метод `handle()` (Laravel резолвить параметри
`handle()` з контейнера під час виконання job, а не під час серіалізації):

```php
public function handle(ExternalCalendarServiceFactory $factory): void
{
    $service = $factory->for($this->integration->provider);
    $externalEventId = $service->createEvent($this->calendarEvent, $this->integration);
    // ...
}
```

`ExternalCalendarSynchronizationService` інжектує
`ExternalCalendarServiceFactory` у конструктор замість виклику `resolve()`.

---

#### I8. `ExternalCalendarConnectCallback` — непослідовний базовий клас винятку

**Файл:**
`app/Actions/Calendar/ExternalCalendar/ExternalCalendarConnectCallback.php`

```php
use Exception;
...
try {
    $this->synchronizationService->handleCallback(...);
} catch (Exception $exception) {
    Log::error('Calendar authorization failed.'.$exception->getMessage());
    return $this->handleError($state, 'Failed to complete calendar authorization. Please try again.');
}
```

Всередині `nonEmptyStateProcess` того ж файлу коректно ловиться `Throwable`. У
`handle()` ловиться лише `Exception`, тому підкласи `TypeError` / `Error`
(наприклад, невідповідно типізований response body, що спричиняє `Error` під час
`decrypt` / `json_decode` всередині сервісу) проминуть цей handler та
відрендерять загальну 500.

**Виправлення:** ловіть `Throwable` і в `handle()`. Причина, чому це більше ніж
косметика: при інтеграції з третьою стороною саме ті режими відмови, що
знаходяться нижче `Exception` (TypeError від неочікуваної форми response,
ValueError від `json_decode` тощо), і є точними точками збою. Користувач повинен
отримувати дружній редирект у всіх випадках.

---

#### I9. `CalendarCredentialEncrypter` використовує AES-256-CBC без тегу цілісності

**Файл:** `app/Services/Encryption/CalendarCredentialEncrypter.php`

```php
private const string CIPHER = 'aes-256-cbc';
...
$encrypted = openssl_encrypt($value, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
...
return base64_encode($iv).':'.base64_encode($encrypted);
```

`decrypt()` приймає payload та перебирає всі налаштовані ключі. CBC без HMAC
вразливий до padding-oracle атак, якщо будь-який шлях помилки розрізняє "padding
failed" від інших помилок — і практичніше, CBC без HMAC дозволяє непомічене
підроблення. Стандартний `Illuminate\Encryption\Encrypter` у Laravel
використовує AES-256-CBC _з_ явним HMAC над `iv || value`, який цей клас
переімплементовує без MAC.

**Виправлення (рекомендоване):** використовуйте `AES-256-GCM` (аутентифіковане
шифрування):

```php
$tag = '';
$encrypted = openssl_encrypt($value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
return base64_encode($iv).':'.base64_encode($tag).':'.base64_encode($encrypted);
```

…та відхиляйте payload, чий тег не проходить аутентифікацію. Це також повністю
усуває потенційну padding-oracle поверхню.

**Виправлення (альтернативне):** якщо CBC необхідно залишити, додайте
HMAC-SHA256 над `iv || ciphertext` з окремим MAC-ключем та перевіряйте його в
constant time перед `openssl_decrypt`.

Примітка: у проєкті вже є доступ до Laravel-фасаду `Crypt` для AES-256-CBC+HMAC
— єдина причина для власного encrypter — це ротація ключів (підтримка
`KEY1`/`KEY2`), яку можна відтворити, інстанціювавши два об'єкти `Encrypter` на
кожен ключ та перебираючи їх по черзі.

---

### Suggestions

---

#### S1. `ReEncryptCalendarCredentials` — захардкоджений `'id'`

**Файл:** `app/Jobs/ReEncryptCalendarCredentials.php:78-80`

```php
$integration->newQuery()
    ->where('id', $integration->getKey())
    ->update($updates);
```

Використовуйте `$integration->getKeyName()` для відповідності конвенціям проєкту
та стійкості до майбутнього перейменування PK:

```php
$integration->newQuery()
    ->where($integration->getKeyName(), $integration->getKey())
    ->update($updates);
```

Альтернатива:
`UserCalendarIntegration::query()->whereKey($integration->getKey())->update($updates);`.

---

#### S2. `ConfirmCalendarEvent` — зайві пусті рядки та непослідовні відступи early-return

**Файл:** `app/Actions/Calendar/CalendarEvent/ConfirmCalendarEvent.php`

Кілька пустих рядків всередині тіла методів (після `{` та перед `}`) не
відповідають стилю, що використовується в решті проєкту:

```php
public function handle(MentorProgram $mentorProgram, CalendarEvent $calendarEvent): Response
{
                                                                  // <- пустий рядок після відкриваючої дужки
    if ($calendarEvent->start_date_time->lessThan(Date::now())) {
```

Pint нормалізує це, але дивно, що файл пройшов крок форматування в CI.
Перевірте, чи PR пропустив `vendor/bin/pint` або чи набір правил `pint.json` не
обробляє ці випадки.

---

#### S3. `ExternalCalendarRetrySync::buildSyncJobs()` використовує `->id` у запиті

**Файл:**
`app/Actions/Calendar/ExternalCalendar/ExternalCalendarRetrySync.php:56`

```php
->whereNotIn('id', $syncedSubquery)
```

`id` коректний з SQL-точки зору, але конвенція проєкту надає перевагу
`(new CalendarEvent)->getKeyName()`. Не блокуючий, але зазначено для
послідовності з рештою кодової бази.

---

#### S4. `ProcessCalendarEventExternalCalendarIntegrations::handle()` dispatches у циклі — розгляньте `Bus::batch`

**Файл:** `app/Jobs/ProcessCalendarEventExternalCalendarIntegrations.php`

```php
foreach ($integrations as $integration) {
    dispatch(new CreateExternalCalendarEvent($this->calendarEvent, $integration));
}
```

Для спостережуваності (одна подія → N sync-jobs через кількох провайдерів /
календарів) batch надає атомарний дескриптор: ви знаєте, коли всі N завершені,
можете відображати агреговані помилки користувачу та скасовувати незавершені.
`ExternalCalendarRetrySync` вже використовує `Bus::batch()` — те ж саме тут
зберігає однорідність патерну.

---

#### S5. Коментарі, що переповідають код

**Файл:** `app/Jobs/ReEncryptCalendarCredentials.php:74, 83`

```php
// Decrypt with current key(s), then re-encrypt with current key
$plaintext = $encrypter->decrypt($raw);
$updates[$field] = $encrypter->encrypt($plaintext);
...
// Write raw values directly to avoid double-encryption via the cast
$integration->newQuery()
    ->where('id', $integration->getKey())
    ->update($updates);
```

Перший коментар повторює те, що говорить код. Другий корисний — пояснює _чому_
використовується `->update()` замість `$integration->save()` (cast виконав би
повторне шифрування). Залиште другий, видаліть перший. Згідно з правилом
проєкту: "Код має бути самодокументованим. Якщо потрібен коментар для пояснення
ЩО робить код — розгляньте рефакторинг."

Той самий патерн є в `AbstractGoogleExternalCalendarService::mapGoogleEvent()`:

```php
// Google may return all-day events with `date` instead of `dateTime`
```

Цей коментар коректний (пояснює специфіку Google-протоколу) — залиште.
Перегляньте PR на наявність інших коментарів-переказів та видаліть їх.

---

## Позитивні нотатки

- `CalendarCredentialEncrypter` з ротацією двох ключів + job
  `ReEncryptCalendarCredentials` — зрілий підхід до міграції облікових даних,
  саме правильний примітив для подальшої роботи (з урахуванням проблеми AEAD у
  I8).
- Розподіл `OAuthCalendarServiceInterface` / `ExternalCalendarServiceInterface`
  чистий та робить non-OAuth flow Apple CalDAV тривіально вираженим через
  `LogicException` у `resolveOAuthService`.
- `CalendarEventObserver` dispatches `Process*` jobs замість inline-роботи —
  коректно для synchronous DB lifecycle hook. Правильно використовує
  `$event->wasChanged()`, щоб уникнути дублюючих dispatch.
- Резолвінг стану в `ExternalCalendarConnectCallback` ретельний: обробляє обидва
  flow — state-in-query та state-in-session (для Outlook особистих акаунтів),
  логує спроби підробки і деградує до flash-повідомлення, а не 500.
- Factories та тести для шару інтеграції присутні та комплексні
  (`DecryptTestTokensCommandTest`, `ConcurrentBookingTest` тощо).
- `refreshTokenIfExpired()` встановлює `needs_reauth = true` при постійній
  помилці refresh — саме правильний спосіб відображення "користувач має
  переконнектитись" у UI.

---

## Follow-up задачі, які слід відкрити незалежно від цього PR

1. **AEAD-міграція для `CalendarCredentialEncrypter`** (I8) — перейдіть з CBC на
   GCM або делегуйте Laravel `Crypt` з обгорткою для ротації. Включіть
   одноразовий job для повторного шифрування (повторно використовуйте
   `ReEncryptCalendarCredentials` з маркерною колонкою).
2. **Документ rate-limit policy для точок дотику з third-party API** — поза
   межами календаря, проведіть аудит інших зовнішніх інтеграцій (чат, оплата) та
   встановіть стандартну конвенцію іменування `RateLimiter::for(...)`.
3. **Dashboard здоров'я синхронізації** — `ExternalCalendarEventLog`
   заповнюється, але не відображається. Побудуйте Filament resource або
   user-facing сторінку, щоб користувачі бачили причини невдалої синхронізації.
4. **Contract test для контракту retry у jobs** — Pest feature test, що мокає
   сервіс із помилками та перевіряє, що job повторюється `$tries` разів і
   завершується в `failed_jobs`. Відразу виявив би C1.
