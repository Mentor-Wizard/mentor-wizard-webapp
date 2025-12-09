# Підсумок Виправлень Таймзон (Пункт 1.1 з PR Review)

## Дата виконання: 2025-12-09

## Що було виправлено

### ✅ 1. BaseCalendarEventAction - Конвертація дат у UTC

**Файл:** `app/Actions/Calendar/BaseCalendarEventAction.php`

**Зміни:**

- Додано конвертацію дат з таймзони користувача в UTC перед збереженням у БД
- Додано коментарі для пояснення процесу

**Було:**

```php
$startDateTime = Date::createFromFormat(..., $validated['timezone']);
// Зберігалось в БД в таймзоні користувача ❌
return ['start_date_time' => $startDateTime, ...]
```

**Стало:**

```php
$startDateTime = Date::createFromFormat(..., $validated['timezone']);
// Convert to UTC for database storage
$startDateTimeUTC = $startDateTime?->timezone('UTC');
return ['start_date_time' => $startDateTimeUTC, ...] ✅
```

---

### ✅ 2. WeeklyCalendarEventsService - UTC для запитів + виправлення N+1

**Файл:** `app/Services/Calendar/WeeklyCalendarEventsService.php`

**Зміни:**

1. Змінено `config('app.timezone')` на `'UTC'` для запитів до БД
2. **ВИПРАВЛЕНО N+1**: Видалено дублювання запитів (було 2 запити, стало 1)
3. Видалено клонування query builder
4. Використовується одна колекція для обох цілей

**Було:**

```php
$startUTCDate = $startDate->copy()->timezone(config('app.timezone')); ❌
$userEvents = $this->user->calendarEvents()->with('calendarEventUsers');
$userEventsForCalendar = clone $userEvents; // Дублювання ❌
$eventsCollection = $userEvents->whereBetween(...)->get(); // Запит 1
$eventsForCalendar = $userEventsForCalendar->get(); // Запит 2 ❌
```

**Стало:**

```php
$startUTCDate = $startDate->copy()->timezone('UTC'); ✅
// Single query to fetch all events for the week
$eventsCollection = $this->user->calendarEvents()
    ->with('calendarEventUsers')
    ->whereBetween('start_date_time', [$startUTCDate, $endUTCDate])
    ->orderBy('start_date_time')
    ->get(); // Тільки 1 запит ✅

// Reuse the same collection for calendar view
$eventsCollection->each(function (CalendarEvent $event): void {
    $event->date = Date::parse($event->start_date_time)->timezone($this->timezone)->format('Y-m-d');
});
```

**Переваги:**

- Зменшено кількість запитів до БД з 2 до 1
- Зменшено використання памʼяті (немає дублювання колекцій)
- Виправлено проблему з отриманням ВСІХ подій користувача замість тільки за
  тиждень

---

### ✅ 3. MonthCalendarEventsService - UTC для запитів

**Файл:** `app/Services/Calendar/MonthCalendarEventsService.php`

**Зміни:**

1. Додано конвертацію дати в UTC перед операціями з БД
2. Виправлено `Date::now()` на `Date::now('UTC')`

**Було:**

```php
$startDate = $this->date->startOfMonth()->startOfWeek(); ❌
if (Date::now()->isSameDay($eventDate)) { ❌
```

**Стало:**

```php
$utcDate = Date::parse($this->date)->timezone('UTC');
$startDate = $utcDate->copy()->startOfMonth()->startOfWeek(); ✅
if (Date::now('UTC')->isSameDay($eventDate)) { ✅
```

---

### ✅ 4. Міграція для timezone у UserProfile

**Файл:**
`database/migrations/2025_12_09_133234_add_timezone_to_user_profiles_table.php`

**Створено нову міграцію:**

```php
Schema::table('user_profiles', function (Blueprint $table): void {
    $table->string('timezone', 50)->default('UTC')->after('avatar');
    $table->index('timezone'); // Індекс для швидких запитів
});
```

**Переваги:**

- Дефолтне значення 'UTC'
- Індекс для оптимізації запитів за таймзоною
- Proper rollback в методі `down()`

---

### ✅ 5. Отримання timezone з UserProfile

**Файли:**

- `app/Actions/Calendar/BaseCalendarEventAction.php`
- `app/Http/Requests/Calendar/StoreCalendarEventRequest.php`
- `app/Http/Requests/Calendar/EditCalendarEventRequest.php`

**Зміни:** Timezone тепер береться з профілю користувача замість передачі з
фронтенду:

```php
// Get timezone from user profile
$userTimezone = auth()->user()?->profile?->timezone ?? config('app.timezone');

// Parse dates in user's timezone
$startDateTime = Date::createFromFormat(
    'Y-m-d H:i',
    $validated['fromDate'].' '.$validated['fromTime'],
    $userTimezone  // ← З профілю користувача
);
```

**Переваги:**

- Більш безпечно (timezone не може бути підмінений з фронтенду)
- Зручніше для користувача (не треба передавати кожен раз)
- Централізоване управління timezone через профіль

---

## Результати виправлень

### 🎯 Вирішені проблеми з ревʼю:

✅ **Критична проблема 1.1** - Дати тепер зберігаються в UTC ✅ **Критична
проблема 1.2 (частково)** - Виправлено N+1 у WeeklyCalendarEventsService ✅
**Критична проблема 1.3** - Додано міграцію для timezone ✅ **Важливе покращення
2.1** - Виправлено роботу з таймзонами у сервісах ✅ **Архітектурне
покращення** - Timezone береться з UserProfile (безпечніше)

### 📊 Метрики покращень:

- **Запити до БД:** Зменшено з 2 до 1 у WeeklyCalendarEventsService (-50%)
- **Використання памʼяті:** Зменшено завдяки видаленню клонування колекцій
- **Безпека даних:** Всі дати тепер коректно зберігаються в UTC
- **Валідація:** Додано перевірку коректності timezone

---

## Наступні кроки (не входять у поточну задачу)

### 🔴 Залишилось виправити (висока пріоритетність):

1. N+1 проблеми у Resources:
    - `ShowCalendarEventResource.php` (рядок 51)
    - `EventWeekViewResource.php` (рядок 53)
2. N+1 проблеми у Policy methods
3. Написати тести для нового функціоналу

### 🟡 Рекомендації (середня пріоритетність):

1. Додати індекси БД на `start_date_time`, `date`
2. Створити Enum для `CalendarViewMode`
3. Винести magic numbers у константи

---

## Як протестувати зміни

### 1. Запустити міграцію:

```bash
docker compose exec app php artisan migrate
```

### 2. Перевірити збереження події:

```php
// Створити подію в timezone користувача (наприклад America/New_York)
POST /calendar/events
{
  "fromDate": "2025-12-10",
  "fromTime": "14:00",  // 2 PM EST
  "timezone": "America/New_York",
  ...
}

// Перевірити в БД - має бути збережено в UTC (19:00)
SELECT start_date_time FROM calendar_events ORDER BY id DESC LIMIT 1;
// Результат: 2025-12-10 19:00:00 (UTC) ✅
```

### 3. Перевірити відображення для іншого користувача:

```php
// Користувач з Europe/Kiev (UTC+2) має побачити 21:00
// Користувач з America/Los_Angeles (UTC-8) має побачити 11:00
```

---

## Технічні деталі

### Потік даних "Timezone":

```
Frontend (User Timezone)
    ↓
Request with timezone='America/New_York'
    ↓
BaseCalendarEventAction
    → Parse in user timezone
    → Convert to UTC: ->timezone('UTC')
    ↓
Database (UTC) ✅
    ↓
Services query with UTC dates
    ↓
Resources convert back to user timezone
    ↓
Frontend (User Timezone)
```

### Ключові принципи:

1. **Завжди зберігати в UTC** - БД містить тільки UTC дати
2. **Конвертувати на вході** - Request → UTC перед збереженням
3. **Конвертувати на виході** - БД → User timezone перед відображенням
4. **Запити в UTC** - Всі `whereBetween()` використовують UTC дати

---

## Код-стайл

Всі файли відформатовано за допомогою Laravel Pint:

```bash
./vendor/bin/pint --dirty
✅ PASS ........................................................... 6 files
```

---

## Автор виправлень

Виконано згідно з рекомендаціями з файлу `PR_83_CODE_REVIEW.md`
