# Детальне Ревʼю Pull Request #83

## Загальна Інформація

- PR: Calendar component (#83)
- URL: https://github.com/Mentor-Wizard/mentor-wizard-webapp/pull/83
- Файлів змінено: 78
- Додано: 9401 рядків
- Видалено: 6 рядків

---

## 1. Критичні Проблеми (Critical Issues)

### 1.1 Проблеми з Таймзонами

#### Файл: `/app/Actions/Calendar/BaseCalendarEventAction.php` (Рядки 22-31)

**Проблема:** Дати зберігаються у БД **у таймзоні користувача**, а не у UTC.

```php
$startDateTime = Date::createFromFormat(
    'Y-m-d H:i',
    $validated['fromDate'].' '.$validated['fromTime'],
    $validated['timezone']  // ← Створюється в таймзоні користувача
);
// Ця дата потім йде в БД без конвертації в UTC (рядки 41-42)
```

**Наслідки:**

- Порушується стандарт зберігання дат у БД
- Неможливо коректно відображати події для користувачів з різних таймзон
- Проблеми з порівнянням дат в запитах

**Рекомендація:**

```php
$startDateTime = Date::createFromFormat(
    'Y-m-d H:i',
    $validated['fromDate'].' '.$validated['fromTime'],
    $validated['timezone']
)->setTimezone('UTC'); // ← Конвертувати в UTC перед збереженням

$endDateTime = Date::createFromFormat(
    'Y-m-d H:i',
    $validated['toDate'].' '.$validated['toTime'],
    $validated['timezone']
)->setTimezone('UTC'); // ← Конвертувати в UTC перед збереженням
```

---

### 1.2 N+1 Query Problems

#### Файл: `/app/Http/Resources/ShowCalendarEventResource.php` (Рядок 51)

**Проблема:** Явний N+1 query при доступі до `calendarEventUsers` (є FIXME
коментар).

```php
// FIXME: potential N+1 problem;
'colour' => $this->resource->calendarEventUsers?->where('id', '=', $user?->getKey())?->first()?->pivot?->colour,
```

**Наслідки:**

- Для кожного CalendarEvent виконується окремий запит до БД
- При відображенні 100 подій = 101 запит (1 основний + 100 для relationships)

**Рішення реалізоване частково:** У файлі
`/app/Actions/Pages/Calendar/ShowCalendarEventPage.php` (рядок 23) є eager
loading:

```php
ShowCalendarEventResource::make($calendarEvent->load('calendarEventUsers'))
```

**Але проблема залишається**, тому що:

1. У Resources використовується колекційна фільтрація `where()` замість
   використання попередньо завантажених даних
2. Потрібно оптимізувати логіку отримання кольору

**Рекомендація:**

```php
// У ShowCalendarEventResource.php
public function toArray(Request $request): array
{
    $user = auth()->user();
    $timezone = $user?->profile->timezone ?? config('app.timezone');
    $startDateTime = $this->start_date_time->copy()->tz($timezone);
    $endDateTime = $this->end_date_time->copy()->tz($timezone);

    // Знайти pivot дані без додаткових запитів
    $userPivot = $this->calendarEventUsers->where('id', $user?->getKey())->first()?->pivot;

    return [
        // ... інші поля
        'colour' => $userPivot?->colour,
    ];
}
```

#### Файл: `/app/Http/Resources/EventWeekViewResource.php` (Рядок 53)

**Проблема:** Така ж N+1 проблема без коментаря FIXME.

```php
'colour' => $this->resource->calendarEventUsers?->where('id', '=', $user->getKey())?->first()?->pivot?->colour,
```

**Рішення вже частково впроваджене:** У файлі
`/app/Services/Calendar/WeeklyCalendarEventsService.php` (рядок 37):

```php
$userEvents = $this->user->calendarEvents()->with('calendarEventUsers');
```

**Але треба оптимізувати Resource:**

```php
// У EventWeekViewResource.php
public function toArray(Request $request): array
{
    $user = $this->additional['user'] ?? null;
    $date = Date::parse($this->start_date_time)->setTimezone($this->timezone);

    // Оптимізована версія - шукаємо в попередньо завантажених даних
    $userPivot = $this->calendarEventUsers->firstWhere('id', $user->getKey())?->pivot;

    return [
        // ... інші поля
        'colour' => $userPivot?->colour,
    ];
}
```

#### Файл: `/app/Services/Calendar/WeeklyCalendarEventsService.php` (Рядки 37-52)

**Критична проблема:** Дублювання запитів до БД через клонування query builder.

```php
$userEvents = $this->user->calendarEvents()->with('calendarEventUsers');
$userEventsForCalendar = clone $userEvents;
// TODO подумати як уникнути клонування колекцій

$eventsCollection = $userEvents->whereBetween('start_date_time',
    [$startUTCDate, $endUTCDate])->orderBy('start_date_time')->get(); // ← Запит 1

$eventsForCalendar = $userEventsForCalendar->get(); // ← Запит 2: БЕЗ whereBetween!
```

**Проблеми:**

1. Два повних запити замість одного
2. Другий запит отримує **ВСІ** події користувача (може бути тисячі)
3. Неефективне використання памʼяті

**Рекомендація:**

```php
public function getWeeklyCalendarEvents(): array
{
    $startDate = $this->date->startOfWeek();
    $startUTCDate = $startDate->copy()->timezone('UTC'); // ← Завжди UTC!
    $endDate = $this->date->endOfWeek();
    $endUTCDate = $endDate->copy()->timezone('UTC'); // ← Завжди UTC!

    // Один запит для обох цілей
    $eventsCollection = $this->user->calendarEvents()
        ->with('calendarEventUsers')
        ->whereBetween('start_date_time', [$startUTCDate, $endUTCDate])
        ->orderBy('start_date_time')
        ->get();

    // Використовуємо ту ж колекцію для обох операцій
    foreach ($eventsCollection as $dayEvent) {
        $this->calendarEvents[] = new EventWeekViewResource($dayEvent, $this->timezone)
            ->additional(['user' => $this->user])
            ->resolve();
    }

    // Використовуємо ту ж колекцію для календаря
    $eventsCollection->each(function (CalendarEvent $event): void {
        $event->date = Date::parse($event->start_date_time)
            ->timezone($this->timezone)
            ->format('Y-m-d');
    });

    $daysEvents = $eventsCollection->pluck('date')->unique()->toArray();

    // ... решта коду
}
```

---

### 1.3 Відсутній Timezone для UserProfile

**Файл:** `/app/Http/Resources/ShowCalendarEventResource.php` (Рядок 33)

```php
$timezone = $user?->profile->timezone ?? config('app.timezone');
```

**Проблема:** У коді передбачається, що `UserProfile` має поле `timezone`, але:

1. Немає міграції для додавання цього поля
2. Немає оновлення моделі `UserProfile`
3. У PR description згадується "Користувач має властивість timezone", але її
   немає

**Рекомендація:** Створити міграцію:

```php
Schema::table('user_profiles', function (Blueprint $table) {
    $table->string('timezone')->default('UTC')->after('avatar');
    $table->index('timezone');
});
```

---

## 2. Важливі Покращення (Important Improvements)

### 2.1 Таймзони в Сервісах

#### Файл: `/app/Services/Calendar/MonthCalendarEventsService.php`

**Проблеми:**

- Рядок 44-45: `startOfMonth()->startOfWeek()` - без урахування таймзони
- Рядок 103: `Date::now()` без параметра timezone

**Рекомендація:**

```php
private function prepareDateConfiguration(): array
{
    // Конвертувати $this->date в UTC перед операціями
    $utcDate = Date::parse($this->date)->timezone('UTC');
    $startDate = $utcDate->copy()->startOfMonth()->startOfWeek();
    $endDate = $utcDate->copy()->endOfMonth()->endOfWeek();
    // ...
}

// Рядок 103
if (Date::now('UTC')->isSameDay($eventDate)) {
    $payload['isToday'] = true;
}
```

---

### 2.2 Відсутність Індексів у БД

**Файл:**
`/database/migrations/2025_07_06_205059_create_calendar_events_table.php`

**Проблема:** Немає індексів на часто використовувані колонки.

**Рекомендація:** Додати індекси:

```php
public function up(): void
{
    Schema::create('calendar_events', function (Blueprint $table): void {
        // ... існуючі колонки

        // Індекси для швидших запитів
        $table->index('start_date_time');
        $table->index('date');
        $table->index(['start_date_time', 'end_date_time']); // для whereBetween
        $table->index('mentor_program_id'); // вже є foreign key, але додатковий індекс покращить performance
    });
}
```

---

### 2.3 Validation Rules для Timezone

**Файл:** `/app/Http/Requests/Calendar/StoreCalendarEventRequest.php`

**Проблема:** Немає валідації поля `timezone` (воно передається з фронтенду).

**Рекомендація:**

```php
public function rules(): array
{
    return [
        // ... існуючі правила
        'timezone' => ['required', 'string', 'timezone:all'], // ← Додати валідацію
    ];
}
```

**Додати в `EditCalendarEventRequest` так само.**

---

### 2.4 Policy Authorization

**Файл:** `/app/Policies/CalendarEventPolicy.php` (Рядок 18-24)

**Потенційна проблема:** N+1 у Policy methods.

```php
public function update(User $user, CalendarEvent $calendarEvent): bool
{
    return $user->hasRole('mentor')
        && $calendarEvent->calendarEventUsers() // ← Кожен раз новий запит!
            ->where('user_id', $user->getKey())
            ->where('role', CalendarEventRoleEnum::HOST->value)
            ->exists();
}
```

**Рекомендація:**

```php
public function update(User $user, CalendarEvent $calendarEvent): bool
{
    if (!$user->hasRole('mentor')) {
        return false;
    }

    // Використовувати завантажений relationship якщо є
    if ($calendarEvent->relationLoaded('calendarEventUsers')) {
        return $calendarEvent->calendarEventUsers
            ->where('id', $user->getKey())
            ->where('pivot.role', CalendarEventRoleEnum::HOST->value)
            ->isNotEmpty();
    }

    // Fallback до запиту
    return $calendarEvent->calendarEventUsers()
        ->where('user_id', $user->getKey())
        ->where('role', CalendarEventRoleEnum::HOST->value)
        ->exists();
}
```

---

## 3. Suggestions (Рекомендації)

### 3.1 FIXME та TODO коментарі

**Файл:** `/app/Actions/Pages/Calendar/CalendarsListPage.php` (Рядок 26)

```php
$mode = $request->get('mode') ?? 'Month view'; // FIXME: чому в такому форматі? тобто ми в реквест суємо таку фігню? О_о
```

**Рекомендація:** Використовувати Enum або константи:

```php
// Створити Enum
enum CalendarViewMode: string {
    case MONTH = 'month';
    case WEEK = 'week';
    case DAY = 'day';
}

// У коді
$mode = CalendarViewMode::tryFrom($request->get('mode')) ?? CalendarViewMode::MONTH;
```

---

### 3.2 Дублювання Логіки

**Файли:**

- `/app/Services/Calendar/DailyCalendarEventsService.php`
- `/app/Services/Calendar/WeeklyCalendarEventsService.php`
- `/app/Services/Calendar/MonthCalendarEventsService.php`

**Проблема:** Дублювання логіки побудови payload (buildDayPayload,
buildWeekPayload тощо).

**Рекомендація:** Створити базовий клас або trait:

```php
trait BuildsCalendarPayload
{
    protected function buildDayPayload(CarbonInterface $date, CarbonInterface $referenceDate, array $eventsData): array
    {
        $payload = ['date' => $date->format('Y-m-d')];

        if ($date->isSameMonth($referenceDate)) {
            $payload['isCurrentMonth'] = true;
        }

        if ($date->isSameDay($referenceDate)) {
            $payload['isSelected'] = true;
        }

        if (Date::now($this->timezone)->isSameDay($date)) {
            $payload['isToday'] = true;
        }

        if (in_array($date->format('Y-m-d'), $eventsData)) {
            $payload['hasEvent'] = true;
        }

        return $payload;
    }
}
```

---

### 3.3 Hard-coded Config Values

**Файл:** `/app/Services/Calendar/WeeklyCalendarEventsService.php` (Рядок 33)

```php
$startUTCDate = $startDate->copy()->timezone(config('app.timezone'));
```

**Проблема:** Використання `config('app.timezone')` замість 'UTC'.

**Рекомендація:** Завжди використовувати 'UTC' для БД операцій.

---

### 3.4 Magic Numbers

**Файл:** `/app/Http/Resources/EventWeekViewResource.php` (Рядки 49-50)

```php
'durationIndex' => (int) ($this->duration * 12 / 3600),
'startIndex'    => (int) (($secondsSinceMidnight * 6 / 3600) + 2),
```

**Рекомендація:** Винести константи:

```php
private const DURATION_INDEX_MULTIPLIER = 12;
private const SECONDS_IN_HOUR = 3600;
private const START_INDEX_MULTIPLIER = 6;
private const START_INDEX_OFFSET = 2;

// У коді
'durationIndex' => (int) ($this->duration * self::DURATION_INDEX_MULTIPLIER / self::SECONDS_IN_HOUR),
'startIndex'    => (int) (($secondsSinceMidnight * self::START_INDEX_MULTIPLIER / self::SECONDS_IN_HOUR) + self::START_INDEX_OFFSET),
```

---

## 4. Strengths (Позитивні Моменти)

1. Добре структурований код з розділенням відповідальності (Services, Actions,
   Resources)
2. Використання Laravel Actions pattern
3. Використання PHP 8.4 features (readonly properties, match expressions)
4. Eager loading реалізоване в багатьох місцях (WeeklyCalendarEventsService
   рядок 37)
5. Proper type declarations на всіх методах
6. Хороші policy для авторизації
7. Factory та Seeder покриття

---

## 5. Тестування (Testing Notes)

**Зауваження:** У PR немає тестів для нового функціоналу.

**Критично необхідно додати:**

1. **Feature Tests:**
    - Створення події з різними таймзонами
    - Отримання подій для різних представлень (день/тиждень/місяць)
    - Перевірка конвертації таймзон при відображенні
    - Перевірка прав доступу (policy tests)

2. **Unit Tests:**
    - Тестування Services (DailyCalendarEventsService,
      WeeklyCalendarEventsService, MonthCalendarEventsService)
    - Тестування Resources з різними таймзонами
    - Тестування CheckTimeSlotReservedService

**Приклад тесту:**

```php
it('stores calendar event in UTC regardless of user timezone', function () {
    $user = User::factory()->create();
    $user->profile->update(['timezone' => 'America/New_York']);

    $response = $this->actingAs($user)->postJson(route('calendar.store'), [
        'title' => 'Test Event',
        'fromDate' => '2025-12-10',
        'fromTime' => '14:00', // 2 PM EST = 7 PM UTC
        'toDate' => '2025-12-10',
        'toTime' => '15:00',
        'timezone' => 'America/New_York',
        'type' => 'Individual',
        'colour' => 'blue',
    ]);

    $response->assertSuccessful();

    $event = CalendarEvent::latest()->first();
    expect($event->start_date_time->timezone)->toBe('UTC');
    expect($event->start_date_time->format('H:i'))->toBe('19:00'); // 7 PM UTC
});
```

---

## Підсумок

### Критичні проблеми, які потрібно виправити:

1. ❌ Зберігання дат у UTC замість таймзони користувача
2. ❌ N+1 query problems у Resources
3. ❌ Дублювання запитів у WeeklyCalendarEventsService
4. ❌ Відсутність поля timezone у UserProfile

### Важливі покращення:

1. ⚠️ Додати індекси БД
2. ⚠️ Виправити роботу з таймзонами у всіх сервісах
3. ⚠️ Оптимізувати Policy methods
4. ⚠️ Додати валідацію timezone

### Рекомендації:

1. 💡 Рефакторинг дублювання коду
2. 💡 Використання Enum для режимів перегляду
3. 💡 Винесення magic numbers у константи

### Тестування:

- ❗ Додати Feature та Unit тести (критично важливо!)

---

## Пріоритетність виправлень

### 🔴 Висока пріоритетність (обовʼязково перед merge):

1. Виправити зберігання дат в UTC
2. Виправити N+1 проблеми
3. Додати міграцію для timezone
4. Додати тести

### 🟡 Середня пріоритетність (бажано виправити):

1. Додати індекси БД
2. Оптимізувати Policy methods
3. Додати валідацію timezone

### 🟢 Низька пріоритетність (можна в наступному PR):

1. Рефакторинг дублювання
2. Створення Enum для режимів
3. Винесення констант
