# Code Review: PR #83 - Calendar Component

**PR Link:** https://github.com/Mentor-Wizard/mentor-wizard-webapp/pull/83
**Author:** @Asafailo **Reviewer:** Claude Code **Date:** 2025-12-08 **Status:**
🟡 Changes Requested

---

## Огляд PR

**Опис фічі:**

- Календар подій для місяця, тижня та конкретної доби з навігацією
- Модель Event та реалізація зв'язку з юзерами і їх ролями
- Апдейт моделі User з функціями отримання подій та вільних слотів
- Вікно створення/редагування/видалення події
- Валідація полів, перевірка на конфлікти часу
- Покриття фабриками та тестами

**Статистика:**

- Файлів змінено: 76
- Додано: 9440 рядків
- Видалено: 6 рядків
- Коментарів: 4
- Review коментарів: 58

---

## 🔴 Критичні Проблеми (Must Fix Before Merge)

### 1. Database: Відсутні Індекси

- [ ] **Priority: Critical**
- **File:**
  `database/migrations/2025_07_06_205059_create_calendar_events_table.php`
- **Line:** Throughout migration

**Проблема:** Немає жодних індексів на колонках, які активно використовуються в
запитах. Це призведе до серйозних проблем з продуктивністю при зростанні
кількості подій.

**Виправлення:**

```php
// В міграції calendar_events
$table->index('start_date_time');
$table->index('date');
$table->index('end_date_time');
$table->index(['mentor_program_id', 'start_date_time']); // composite

// В міграції calendar_event_user
$table->index(['user_id', 'role']);
$table->index('calendar_event_id');
$table->unique(['user_id', 'calendar_event_id', 'role']); // prevent duplicates
```

**Обгрунтування:** Сервіси (`GetDailyCalendarEventsService`,
`GetMonthCalendarEventsService`, `GetWeeklyCalendarEventsService`) роблять
запити по `start_date_time`, `date`, та через relationships. Без індексів кожен
запит буде full table scan.

---

### 2. Models: Відсутня колонка `role` в withPivot

- [ ] **Priority: Critical**
- **File:** `app/Models/CalendarEvent.php`
- **Line:** 50-54

**Проблема:**

```php
public function calendarEventUsers(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'calendar_event_user', 'calendar_event_id')
        ->withPivot('colour') // role відсутня!
        ->withTimestamps();
}
```

Але код в `CalendarEventPolicy.php:22` покладається на `role`:

```php
->where('role', CalendarEventRoleEnum::HOST->value)
```

**Виправлення:**

```php
return $this->belongsToMany(User::class, 'calendar_event_user', 'calendar_event_id')
    ->withPivot('colour', 'role')
    ->withTimestamps();
```

**Також в User.php:174** додати до всіх relationships:

```php
public function calendarEvents(): BelongsToMany
{
    return $this->belongsToMany(CalendarEvent::class, 'calendar_event_user', 'user_id')
        ->withPivot('colour', 'role')
        ->withTimestamps();
}
```

---

### 3. Factory: Неправильний PHPDoc

- [ ] **Priority: Critical**
- **File:** `database/factories/CalendarEventFactory.php`
- **Line:** 32

**Проблема:**

```php
/** @use HasFactory<CurrencyFactory> */ // WRONG!
use HasFactory;
```

**Виправлення:**

```php
/** @use HasFactory<CalendarEventFactory> */
use HasFactory;
```

---

### 4. Security: Дублювання авторизації в Action

- [ ] **Priority: Critical**
- **File:** `app/Actions/Calendar/StoreCalendarEvent.php`
- **Line:** 21

**Проблема:**

```php
abort_if($request->user()->cannot('create', CalendarEvent::class),
    Response::HTTP_FORBIDDEN, 'Unauthorized action.');
```

Авторизація має бути в middleware, не в екшені (порушення separation of
concerns).

**Виправлення:**

**Варіант 1 (Рекомендований):** Використати Route middleware

```php
// routes/web.php
Route::post('/calendar/events', StoreCalendarEvent::class)
    ->middleware('can:create,App\Models\CalendarEvent')
    ->name('calendar.store');
```

**Варіант 2:** Використати Gate в екшені

```php
// В handle методі
Gate::authorize('create', CalendarEvent::class);
```

Прибрати `abort_if` з екшену.

---

### 5. Timezone: Неконсистентна обробка часових зон

- [ ] **Priority: Critical**
- **Files:**
    - `app/Http/Requests/Calendar/StoreCalendarEventRequest.php:100-110`
    - `app/Http/Requests/Calendar/EditCalendarEventRequest.php:100-110`
    - Multiple Service classes

**Проблема:** Код змішує `config('app.timezone')` з user-provided timezone:

```php
// StoreCalendarEventRequest.php:105
->setTimezone(config('app.timezone')); // має бути UTC!

// EditCalendarEventRequest.php:108
->setTimezone('UTC'); // тут правильно, але inconsistent
```

**Виправлення:**

**Правило:** Завжди зберігати в БД у UTC, конвертувати лише при відображенні.

```php
// В Request->getEventData()
$startDateTime = Date::createFromFormat(
    'Y-m-d H:i',
    $validated['fromDate'].' '.$validated['fromTime'],
    $validated['timezone']
)?->setTimezone('UTC'); // Завжди UTC для БД

$endDateTime = Date::createFromFormat(
    'Y-m-d H:i',
    $validated['toDate'].' '.$validated['toTime'],
    $validated['timezone']
)?->setTimezone('UTC'); // Завжди UTC для БД
```

```php
// В Resources - конвертувати в timezone користувача
$date = Date::parse($this->start_date_time)
    ->setTimezone($this->timezone ?? config('app.timezone'));
```

**Також перевірити:**

- [ ] `GetDailyCalendarEventsService.php`
- [ ] `GetMonthCalendarEventsService.php`
- [ ] `GetWeeklyCalendarEventsService.php`
- [ ] `CheckTimeSlotReservedService.php`

---

### 6. Security: Потенційна SQL Injection

- [ ] **Priority: High**
- **File:** `app/Services/Calendar/GetAvailableSlotsService.php`
- **Line:** 23-24

**Проблема:**

```php
->whereKeyNot($this->excludeEvents)
```

`$this->excludeEvents` приходить з user input через `EditCalendarEventRequest`.

**Виправлення:**

```php
->whereKeyNot(array_map('intval', $this->excludeEvents))
```

Або додати валідацію в конструктор:

```php
public function __construct(
    private readonly User $user,
    private readonly string $timezone,
    private readonly array $excludeEvents = []
) {
    $this->excludeEvents = array_map('intval', $excludeEvents);
}
```

---

## 🟡 Важливі Покращення (Should Fix)

### 7. Performance: N+1 Запити в Policy

- [ ] **Priority: High**
- **File:** `app/Policies/CalendarEventPolicy.php`
- **Lines:** 21-24, 29-32, 35-37

**Проблема:** Кожна перевірка policy робить окремий запит:

```php
return $calendarEvent->calendarEventUsers()
    ->where('user_id', $user->getKey())
    ->where('role', CalendarEventRoleEnum::HOST->value)
    ->exists();
```

При перевірці списку подій це O(n) запитів.

**Виправлення:**

**Крок 1:** Eager load в Action/Controller:

```php
// CalendarsListPage.php або інших місцях
$calendarEvent->load('calendarEventUsers');
```

**Крок 2:** Змінити Policy на in-memory filtering:

```php
public function update(User $user, CalendarEvent $calendarEvent): bool
{
    return $user->hasRole('mentor')
        && $calendarEvent->calendarEventUsers
            ->where('id', $user->getKey())
            ->where('pivot.role', CalendarEventRoleEnum::HOST->value)
            ->isNotEmpty();
}
```

---

### 8. Code Quality: Дублювання коду в Request класах

- [ ] **Priority: High**
- **Files:**
    - `app/Http/Requests/Calendar/StoreCalendarEventRequest.php`
    - `app/Http/Requests/Calendar/EditCalendarEventRequest.php`

**Проблема:** 90% коду дублюється між двома класами (rules, messages,
getEventData, withValidator).

**Виправлення:**

Створити абстрактний базовий клас:

```php
// app/Http/Requests/Calendar/BaseCalendarEventRequest.php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Services\Calendar\CheckTimeSlotReservedService;
use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

abstract class BaseCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'fromDate'    => ['required', 'date', 'after_or_equal:today'],
            'toDate'      => ['required', 'date', 'after_or_equal:fromDate'],
            'fromTime'    => ['required', 'date_format:H:i'],
            'toTime'      => ['required', 'date_format:H:i', 'after:fromTime'],
            'colour'      => ['required', Rule::in(CalendarEventColoursEnum::values())],
            'description' => ['nullable', 'max:2000'],
            'type'        => ['required', Rule::in(CalendarEventTypeEnum::values())],
            'timezone'    => ['required', 'string', 'timezone:all'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'          => __('calendar.validation.title_required'),
            'title.max'               => __('calendar.validation.title_max'),
            'fromDate.required'       => __('calendar.validation.from_date_required'),
            'fromDate.date'           => __('calendar.validation.from_date_invalid'),
            'fromDate.after_or_equal' => __('calendar.validation.from_date_past'),
            'toDate.required'         => __('calendar.validation.to_date_required'),
            'toDate.date'             => __('calendar.validation.to_date_invalid'),
            'toDate.after_or_equal'   => __('calendar.validation.to_date_before_start'),
            'fromTime.required'       => __('calendar.validation.from_time_required'),
            'fromTime.date_format'    => __('calendar.validation.from_time_format'),
            'toTime.required'         => __('calendar.validation.to_time_required'),
            'toTime.date_format'      => __('calendar.validation.to_time_format'),
            'toTime.after'            => __('calendar.validation.to_time_before_start'),
            'description.max'         => __('calendar.validation.description_max'),
            'type.required'           => __('calendar.validation.type_required'),
            'type.in'                 => __('calendar.validation.type_invalid'),
            'colour.required'         => __('calendar.validation.colour_required'),
            'timezone.timezone'       => __('calendar.validation.timezone_invalid'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $this->validateDateParsing($validator);
            $this->validateSlotAvailability($validator);
        });
    }

    protected function validateDateParsing(Validator $validator): void
    {
        try {
            Date::parse($this->input('fromDate'));
            Date::parse($this->input('fromDate').' '.$this->input('fromTime'));
        } catch (Exception) {
            $validator->errors()->add('fromDate', __('calendar.validation.from_date_invalid'));
        }

        try {
            Date::parse($this->input('toDate'));
            Date::parse($this->input('toDate').' '.$this->input('toTime'));
        } catch (Exception) {
            $validator->errors()->add('toDate', __('calendar.validation.to_date_invalid'));
        }
    }

    abstract protected function validateSlotAvailability(Validator $validator): void;

    public function getEventData(): array
    {
        $validated = $this->validated();

        try {
            $startDateTime = Date::createFromFormat(
                'Y-m-d H:i',
                $validated['fromDate'].' '.$validated['fromTime'],
                $validated['timezone']
            )?->setTimezone('UTC');

            $endDateTime = Date::createFromFormat(
                'Y-m-d H:i',
                $validated['toDate'].' '.$validated['toTime'],
                $validated['timezone']
            )?->setTimezone('UTC');
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'fromDate' => __('calendar.validation.datetime_parse_error'),
            ]);
        }

        $duration = (int) $startDateTime?->diffInSeconds($endDateTime);

        $eventType = match ($validated['type']) {
            'individual' => CalendarEventTypeEnum::INDIVIDUAL->value,
            'group'      => CalendarEventTypeEnum::GROUP->value,
            default      => CalendarEventTypeEnum::INDIVIDUAL->value,
        };

        return [
            'title'           => $validated['title'],
            'start_date_time' => $startDateTime,
            'end_date_time'   => $endDateTime,
            'duration'        => $duration,
            'type'            => $eventType,
            'colour'          => $validated['colour'],
            'description'     => $validated['description'] ?? null,
            'status'          => CalendarEventStatusEnum::CONFIRMED,
            'date'            => $startDateTime?->format('Y-m-d'),
        ];
    }
}
```

**Потім дочірні класи:**

```php
// StoreCalendarEventRequest.php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Services\Calendar\CheckTimeSlotReservedService;
use Illuminate\Contracts\Validation\Validator;

class StoreCalendarEventRequest extends BaseCalendarEventRequest
{
    protected function validateSlotAvailability(Validator $validator): void
    {
        $isAvailable = new CheckTimeSlotReservedService(
            $this->input('fromDate'),
            $this->input('fromTime'),
            $this->input('toDate'),
            $this->input('toTime'),
            $this->input('timezone', 'UTC'),
            auth()->user()
        )->isSlotAvailable();

        if (!$isAvailable) {
            $validator->errors()->add('fromDate', __('calendar.validation.slot_not_available'));
        }
    }
}
```

```php
// EditCalendarEventRequest.php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Services\Calendar\CheckTimeSlotReservedService;
use Illuminate\Contracts\Validation\Validator;

class EditCalendarEventRequest extends BaseCalendarEventRequest
{
    protected function validateSlotAvailability(Validator $validator): void
    {
        $isAvailable = new CheckTimeSlotReservedService(
            $this->input('fromDate'),
            $this->input('fromTime'),
            $this->input('toDate'),
            $this->input('toTime'),
            $this->input('timezone', 'UTC'),
            auth()->user(),
            [$this->input('id')]
        )->isSlotAvailable();

        if (!$isAvailable) {
            $validator->errors()->add('fromDate', __('calendar.validation.slot_not_available'));
        }
    }
}
```

---

### 9. i18n: Відсутні переклади для валідації

- [ ] **Priority: Medium**
- **Files:** All Request classes

**Проблема:** Всі повідомлення про помилки захардкоджені англійською мовою, але
додаток підтримує мультимовність.

**Виправлення:**

Створити файл перекладів:

```php
// lang/en/calendar.php
<?php

return [
    'validation' => [
        'title_required' => 'Event title is required.',
        'title_max' => 'Event title cannot exceed 255 characters.',
        'from_date_required' => 'Start date is required.',
        'from_date_invalid' => 'Start date is not valid.',
        'from_date_past' => 'Start date cannot be in the past.',
        'to_date_required' => 'End date is required.',
        'to_date_invalid' => 'End date is not valid.',
        'to_date_before_start' => 'End date must be on or after the start date.',
        'from_time_required' => 'Start time is required.',
        'from_time_format' => 'Start time must be in HH:MM format.',
        'to_time_required' => 'End time is required.',
        'to_time_format' => 'End time must be in HH:MM format.',
        'to_time_before_start' => 'End time must be after start time.',
        'description_max' => 'Description cannot exceed 2000 characters.',
        'type_required' => 'Event type is required.',
        'type_invalid' => 'Event type must be either individual or group.',
        'colour_required' => 'Colour is required.',
        'timezone_invalid' => 'Invalid timezone provided.',
        'slot_not_available' => 'There are other events scheduled at this time.',
        'datetime_parse_error' => 'Invalid date/time format provided.',
    ],
];
```

```php
// lang/uk/calendar.php
<?php

return [
    'validation' => [
        'title_required' => 'Назва події обов\'язкова.',
        'title_max' => 'Назва події не може перевищувати 255 символів.',
        'from_date_required' => 'Дата початку обов\'язкова.',
        'from_date_invalid' => 'Дата початку невірна.',
        'from_date_past' => 'Дата початку не може бути в минулому.',
        // ... інші переклади
        'slot_not_available' => 'На цей час вже заплановані інші події.',
    ],
];
```

---

### 10. UX: Неправильне використання кольорів

- [ ] **Priority: Medium**
- **File:** `app/Http/Resources/EventDayViewResource.php`
- **Line:** 51

**Проблема:**

```php
'colour' => CalendarEventColoursEnum::randomValue(),
```

Це ігнорує збережені налаштування кольорів користувача!

**Виправлення:**

```php
'colour' => $this->resource->calendarEventUsers
    ?->where('id', '=', $this->additional['user']?->getKey())
    ?->first()
    ?->pivot
    ?->colour ?? CalendarEventColoursEnum::randomValue(),
```

**Також перевірити використання в:**

- [ ] `EventWeekViewResource.php` - тут правильно, використовує pivot
- [ ] `EventMonthViewResource.php` - тут взагалі немає кольору

---

### 11. Error Handling: Мовчазне повернення порожнього масиву

- [ ] **Priority: Medium**
- **File:** `app/Http/Requests/Calendar/StoreCalendarEventRequest.php`
- **Line:** 111-113

**Проблема:**

```php
} catch (Exception) {
    return [];
}
```

Мовчазні провали приховують баги.

**Виправлення:**

```php
} catch (Exception $e) {
    throw ValidationException::withMessages([
        'fromDate' => __('calendar.validation.datetime_parse_error'),
    ]);
}
```

**Також в:**

- [ ] `EditCalendarEventRequest.php` має подібну проблему

---

## 🟢 Рекомендації (Nice to Have)

### 12. Models: Використання getKey() замість id

- [ ] **Priority: Low**
- **Files:** Multiple Resources and Services

**Згідно з CLAUDE.md:** завжди використовувати `$model->getKey()` замість
прямого доступу до `$model->id`.

**Перевірити та виправити в:**

- [ ] `EventMonthViewResource.php:35` - `'id' => $this->resource->getKey()`
- [ ] `EventShowResource.php:36` - `'id' => $this->resource->getKey()`
- [ ] Інші місця використання `->id`

---

### 13. Code Quality: Типізовані константи (PHP 8.4)

- [ ] **Priority: Low**
- **File:** `app/Models/CalendarEvent.php`
- **Line:** 35

**Поточний код:**

```php
const MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET = 6;
```

**PHP 8.4 підтримка:**

```php
public const int MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET = 6;
```

---

### 14. Validation: Додати правило timezone

- [ ] **Priority: Low**
- **Files:** `StoreCalendarEventRequest.php`, `EditCalendarEventRequest.php`

**Поточний код:**

```php
'timezone' => ['required', 'string'],
```

**Покращення:**

```php
'timezone' => ['required', 'string', 'timezone:all'],
```

Це валідує timezone проти списку PHP timezone identifiers.

---

### 15. Service Naming: Переіменувати методи з execute()

- [ ] **Priority: Low**
- **Files:** Service classes

Якщо сервіси мають метод `execute()`, переіменувати на більш описові назви:

- `GetDailyCalendarEventsService::execute()` → `getDailyCalendarEvents()`
- `GetMonthCalendarEventsService::execute()` → `getMonthCalendarEvents()`
- Тощо.

**Примітка:** У поточній версії вже використовуються правильні назви, тому цей
пункт можна пропустити.

---

### 16. Models: Додати константу для pivot таблиці

- [ ] **Priority: Low**
- **File:** `app/Models/CalendarEvent.php`

**Додати:**

```php
public const string PIVOT_TABLE = 'calendar_event_user';

public function calendarEventUsers(): BelongsToMany
{
    return $this->belongsToMany(User::class, self::PIVOT_TABLE, 'calendar_event_id')
        ->withPivot('colour', 'role')
        ->withTimestamps();
}
```

---

### 17. Enums: Додати безпечний tryFrom

- [ ] **Priority: Low**
- **Files:** All Enum classes

**Додати до кожного Enum:**

```php
public static function tryFromValue(string $value): ?self
{
    foreach (self::cases() as $case) {
        if ($case->value === $value) {
            return $case;
        }
    }
    return null;
}
```

---

## 📋 Тестування

### Must Have Tests

- [ ] **Feature Test:** Створення події ментором
- [ ] **Feature Test:** Заборона створення події менті
- [ ] **Feature Test:** Редагування події власником
- [ ] **Feature Test:** Заборона редагування події не-власником
- [ ] **Feature Test:** Видалення події
- [ ] **Feature Test:** Перегляд події учасником
- [ ] **Feature Test:** Заборона перегляду події не-учасником
- [ ] **Unit Test:** Валідація overlapping events
- [ ] **Unit Test:** Валідація timezone conversion
- [ ] **Unit Test:** GetAvailableSlotsService
- [ ] **Unit Test:** CheckTimeSlotReservedService
- [ ] **Policy Test:** CalendarEventPolicy всі методи

### Edge Cases to Test

- [ ] Події через межу DST (daylight saving time)
- [ ] Учасники з різних часових зон
- [ ] Події що перетинаються
- [ ] Події в минулому (валідація)
- [ ] Максимальна тривалість події
- [ ] Події на межі допустимого періоду (6 місяців)

### Test Command

```bash
# Run tests
docker compose exec app php artisan test --filter=Calendar

# Run with coverage
docker compose exec app ./vendor/bin/pest --coverage --min=80

# Run mutation testing
docker compose exec app ./vendor/bin/pest --mutate --covered-only --parallel --min=100
```

---

## ✅ Сильні Сторони

1. ✅ **Чиста архітектура** - відмінне використання Laravel Actions pattern
2. ✅ **Строга типізація** - `declare(strict_types=1)` у всіх файлах
3. ✅ **PHP 8.4 Enums** - правильне використання енумів для type safety
4. ✅ **Resource Pattern** - API Resources правильно форматують дані
5. ✅ **Policy Authorization** - логіка авторизації чітко відокремлена
6. ✅ **Service Layer** - бізнес-логіка винесена в окремі сервіси
7. ✅ **Factory Coverage** - є factories та seeders для тестових даних
8. ✅ **Pivot Relationships** - правильне використання many-to-many з
   додатковими полями

---

## 📊 Progress Tracker

**Критичні проблеми:** 0/6 виправлено **Важливі покращення:** 0/5 виправлено
**Рекомендації:** 0/6 виконано **Тести:** 0/16 написано

**Overall Status:** 🔴 Not Ready for Merge

---

## 💬 Коментарі та Нотатки

### Додаткові спостереження:

1. **Архітектура:** Код добре структурований, слідує Laravel conventions
2. **Безпека:** Є Policy для авторизації, але потребує винесення перевірок з
   екшенів
3. **Продуктивність:** Основні проблеми - відсутність індексів та N+1 запити
4. **Підтримуваність:** Дублювання коду в Request класах ускладнить підтримку

### Орієнтовний час на виправлення:

- 🔴 Критичні проблеми: ~4 години
- 🟡 Важливі покращення: ~6 годин
- 🟢 Рекомендації: ~3 години
- 📋 Тести: ~8 годин

**Загальний час:** ~21 година

---

## 🚀 Наступні кроки

1. Виправити всі 🔴 критичні проблеми
2. Запустити тести та переконатись що все працює
3. Виправити 🟡 важливі покращення
4. Написати відсутні тести
5. Запустити mutation testing
6. Code review round 2
7. Merge to develop

---

**Останнє оновлення:** 2025-12-08 **Reviewer:** Claude Code **Contact:** Ask
questions in PR comments
