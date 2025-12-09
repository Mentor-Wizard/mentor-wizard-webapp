# Проблеми з Базою Даних - PR #83

**Джерело:** CODE_REVIEW_PR_83.md  
**Дата створення:** 2025-12-09  
**Статус:** 🔴 Потребує виправлення

---

## Огляд

Цей документ містить всі проблеми, пов'язані з базою даних в компоненті
Календаря (PR #83): індекси, міграції, запити, оптимізація, та структура БД.
Проблеми критично впливають на продуктивність та масштабованість додатку.

---

## 🔴 Критичні Проблеми

### 1. Відсутні індекси на таблиці calendar_events

- [ ] **Priority: Critical**
- **File:**
  `database/migrations/2025_07_06_205059_create_calendar_events_table.php`
- **Impact:** 🔴 Серйозні проблеми з продуктивністю

**Проблема:**

Немає жодних індексів на колонках, які активно використовуються в запитах. Це
призведе до full table scan для кожного запиту.

**Поточна структура таблиці:**

```php
Schema::create('calendar_events', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->dateTime('start_date_time');
    $table->dateTime('end_date_time');
    $table->date('date');
    $table->integer('duration');
    $table->string('type');
    $table->string('colour');
    $table->text('description')->nullable();
    $table->string('status');
    $table->foreignId('mentor_program_id')->constrained()->cascadeOnDelete();
    $table->timestamps();

    // ІНДЕКСИ ВІДСУТНІ!
});
```

**Виправлення:**

```php
Schema::create('calendar_events', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->dateTime('start_date_time');
    $table->dateTime('end_date_time');
    $table->date('date');
    $table->integer('duration');
    $table->string('type');
    $table->string('colour');
    $table->text('description')->nullable();
    $table->string('status');
    $table->foreignId('mentor_program_id')->constrained()->cascadeOnDelete();
    $table->timestamps();

    // Додати індекси
    $table->index('start_date_time', 'idx_calendar_events_start_date');
    $table->index('date', 'idx_calendar_events_date');
    $table->index('end_date_time', 'idx_calendar_events_end_date');
    $table->index('status', 'idx_calendar_events_status');
    $table->index(['mentor_program_id', 'start_date_time'], 'idx_calendar_events_program_start');
    $table->index(['date', 'status'], 'idx_calendar_events_date_status');
});
```

**Обґрунтування для кожного індексу:**

1. **`start_date_time`** - Використовується в:
    - `GetMonthCalendarEventsService` - фільтрація подій за місяць
    - `GetWeeklyCalendarEventsService` - фільтрація подій за тиждень
    - `CheckTimeSlotReservedService` - перевірка на overlap
    - Сортування подій

2. **`date`** - Використовується в:
    - `GetDailyCalendarEventsService` - отримання подій за день
    - Швидкий lookup подій на конкретну дату
    - Group by date в календарі

3. **`end_date_time`** - Використовується в:
    - `CheckTimeSlotReservedService` - перевірка на overlap
    - Range queries (between start and end)

4. **`status`** - Використовується в:
    - Фільтрація активних/завершених подій
    - Відображення тільки confirmed events

5. **`[mentor_program_id, start_date_time]`** (composite) - Використовується в:
    - Отримання всіх подій програми в хронологічному порядку
    - Найчастіший тип запиту

6. **`[date, status]`** (composite) - Використовується в:
    - Денний вигляд календаря з фільтром по статусу
    - Оптимізація COUNT запитів

**Вплив на продуктивність:**

| Запит                 | Без індексів        | З індексами  | Покращення |
| --------------------- | ------------------- | ------------ | ---------- |
| Get events for date   | O(n) - Full scan    | O(log n)     | 100x-1000x |
| Get events for month  | O(n) - Full scan    | O(log n + k) | 50x-500x   |
| Check time overlap    | O(n²) - Nested scan | O(log n)     | 1000x+     |
| Get by program + date | O(n) - Full scan    | O(log n)     | 100x-1000x |

**Приклад:**

- 10,000 подій без індексів: ~500ms на запит
- 10,000 подій з індексами: ~5ms на запит

---

### 2. Відсутні індекси на таблиці calendar_event_user

- [ ] **Priority: Critical**
- **File:**
  `database/migrations/2025_07_06_205111_create_calendar_event_user_table.php`
- **Impact:** 🔴 N+1 проблеми та повільні JOIN запити

**Проблема:**

Pivot таблиця без індексів призводить до повільних JOIN операцій.

**Поточна структура:**

```php
Schema::create('calendar_event_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('role');
    $table->string('colour');
    $table->timestamps();

    // ІНДЕКСИ ВІДСУТНІ!
});
```

**Виправлення:**

```php
Schema::create('calendar_event_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('role');
    $table->string('colour');
    $table->timestamps();

    // Індекси для JOIN операцій
    $table->index(['user_id', 'role'], 'idx_calendar_event_user_user_role');
    $table->index('calendar_event_id', 'idx_calendar_event_user_event');

    // Unique constraint - запобігає дублюванню
    $table->unique(['user_id', 'calendar_event_id', 'role'], 'unq_calendar_event_user');
});
```

**Обґрунтування:**

1. **`[user_id, role]`** - Composite index для:
    - Policy перевірок (is user HOST?)
    - Отримання всіх подій користувача з певною роллю
    - Фільтрація подій де користувач є host

2. **`calendar_event_id`** - Для:
    - JOIN з calendar_events
    - Eager loading relationships
    - Видалення CASCADE

3. **Unique constraint** - Запобігає:
    - Дублюванню записів
    - Конфліктам ролей
    - Data integrity проблемам

**Приклад використання:**

```php
// Цей запит тепер буде швидким
$userHostEvents = DB::table('calendar_event_user')
    ->where('user_id', $userId)
    ->where('role', 'host')
    ->join('calendar_events', 'calendar_event_user.calendar_event_id', '=', 'calendar_events.id')
    ->get();
```

---

### 3. Потенційна SQL Injection через whereKeyNot

- [ ] **Priority: High**
- **File:** `app/Services/Calendar/GetAvailableSlotsService.php`
- **Line:** 23-24

**Проблема:**

```php
->whereKeyNot($this->excludeEvents)
```

`$this->excludeEvents` приходить з user input через `EditCalendarEventRequest`
без валідації.

**Сценарій атаки:**

```php
// User може передати:
$excludeEvents = ["1 OR 1=1", "'; DROP TABLE calendar_events; --"];

// Що може призвести до SQL injection
```

**Виправлення:**

**Варіант 1:** Валідація в конструктор сервісу

```php
public function __construct(
    private readonly User $user,
    private readonly string $timezone,
    array $excludeEvents = []
) {
    // Завжди конвертувати в integers
    $this->excludeEvents = array_map('intval', array_filter($excludeEvents));
}
```

**Варіант 2:** Валідація в Request

```php
// EditCalendarEventRequest
public function rules(): array
{
    return [
        // ...
        'excludeEvents' => ['sometimes', 'array'],
        'excludeEvents.*' => ['integer', 'exists:calendar_events,id'],
    ];
}
```

**Варіант 3:** Type hint в методі

```php
public function getAvailableSlots(int ...$excludeEventIds): Collection
{
    return CalendarEvent::query()
        ->whereKeyNot($excludeEventIds) // Гарантовано integers
        ->get();
}
```

**Рекомендація:** Використати всі 3 варіанти для defense in depth.

---

### 4. N+1 Запити в Policy

- [ ] **Priority: High**
- **File:** `app/Policies/CalendarEventPolicy.php`
- **Lines:** 21-24, 29-32, 35-37
- **Related:** Database queries optimization

**Проблема:**

Кожна перевірка Policy робить окремий запит:

```php
public function update(User $user, CalendarEvent $calendarEvent): bool
{
    return $user->hasRole('mentor')
        && $calendarEvent->calendarEventUsers()
            ->where('user_id', $user->getKey())
            ->where('role', CalendarEventRoleEnum::HOST->value)
            ->exists(); // +1 запит
}
```

**SQL що генерується:**

```sql
-- Для кожної події окремий запит
SELECT EXISTS(
    SELECT * FROM calendar_event_user
    WHERE calendar_event_id = 1
    AND user_id = 123
    AND role = 'host'
) as exists;

SELECT EXISTS(
    SELECT * FROM calendar_event_user
    WHERE calendar_event_id = 2
    AND user_id = 123
    AND role = 'host'
) as exists;

-- ... для кожної події
```

**Виправлення:**

**Крок 1:** Eager load в query

```php
// В Service або Resource
$events = CalendarEvent::query()
    ->with(['calendarEventUsers' => function ($query) use ($user) {
        $query->where('user_id', $user->getKey());
    }])
    ->get();
```

**SQL що генерується:**

```sql
-- 1 запит для events
SELECT * FROM calendar_events WHERE ...;

-- 1 запит для всіх users (eager load)
SELECT * FROM calendar_event_user
WHERE calendar_event_id IN (1, 2, 3, ...)
AND user_id = 123;
```

**Крок 2:** Оптимізувати Policy

```php
public function update(User $user, CalendarEvent $calendarEvent): bool
{
    // Якщо relationship не loaded - зробити запит
    if (!$calendarEvent->relationLoaded('calendarEventUsers')) {
        return $user->hasRole('mentor')
            && $calendarEvent->calendarEventUsers()
                ->where('user_id', $user->getKey())
                ->where('role', CalendarEventRoleEnum::HOST->value)
                ->exists();
    }

    // Використати loaded data
    return $user->hasRole('mentor')
        && $calendarEvent->calendarEventUsers
            ->where('id', $user->getKey())
            ->where('pivot.role', CalendarEventRoleEnum::HOST->value)
            ->isNotEmpty();
}
```

**Результат:**

- Було: 100 подій = 100+ запитів
- Стало: 100 подій = 2 запити (1 для events, 1 для users)

---

## 🟡 Важливі Покращення

### 5. Відсутність soft deletes

- [ ] **Priority: Medium**
- **Files:**
    - `database/migrations/2025_07_06_205059_create_calendar_events_table.php`
    - `app/Models/CalendarEvent.php`

**Проблема:**

Події видаляються назавжди, що може призвести до:

- Втрати історії
- Неможливості відновлення
- Порушення звітності
- Проблеми з аудитом

**Рекомендація:**

```php
// В міграції
Schema::create('calendar_events', function (Blueprint $table) {
    // ... existing columns
    $table->softDeletes(); // Додати
});
```

```php
// В моделі
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use HasFactory, SoftDeletes;

    // ...
}
```

**Переваги:**

- Події можна відновити
- Зберігається історія
- Можливість аудиту
- Не порушуються relationships

**Використання:**

```php
// Soft delete
$event->delete(); // deleted_at = now()

// Restore
$event->restore();

// Permanent delete
$event->forceDelete();

// Query with trashed
CalendarEvent::withTrashed()->get();

// Query only trashed
CalendarEvent::onlyTrashed()->get();
```

---

### 6. Відсутність індексу на mentor_program_id

- [ ] **Priority: Medium**
- **File:** Migration

**Проблема:**

Foreign key `mentor_program_id` не має окремого індексу (крім composite).

**Поточний стан:**

```php
$table->foreignId('mentor_program_id')->constrained()->cascadeOnDelete();
// Індекс створюється автоматично для FK, але може бути недостатнім
```

**Рекомендація:**

```php
// Якщо часто запитуємо події конкретної програми
$table->index('mentor_program_id', 'idx_calendar_events_program');
```

**Коли потрібно:**

- Якщо часто виконуються запити типу: `->where('mentor_program_id', $id)`
- Для статистики по програмах
- Для dashboard з подіями програми

---

### 7. Немає обмеження на довжину title

- [ ] **Priority: Medium**
- **File:** Migration

**Проблема:**

```php
$table->string('title'); // default 255, але можна більше
```

У валідації є обмеження 255, але в БД можна зберегти більше.

**Рекомендація:**

```php
$table->string('title', 255); // Explicit length
```

Або якщо потрібно більше:

```php
$table->string('title', 500);

// І в валідації
'title' => ['required', 'string', 'max:500'],
```

---

### 8. Відсутність timezone колонки

- [ ] **Priority: Medium**
- **File:**
  `database/migrations/2025_07_06_205059_create_calendar_events_table.php`

**Проблема:**

Timezone не зберігається в події, що ускладнює:

- Відображення для користувачів з різних timezone
- Розуміння в якій timezone була створена подія
- Конвертацію для різних регіонів

**Рекомендація:**

```php
Schema::table('calendar_events', function (Blueprint $table) {
    $table->string('timezone', 50)->default('UTC')->after('status');
    $table->index('timezone', 'idx_calendar_events_timezone');
});
```

```php
// В модель
protected function casts(): array
{
    return [
        'start_date_time' => 'datetime',
        'end_date_time' => 'datetime',
        'date' => 'date',
        'timezone' => 'string',
    ];
}
```

**Використання:**

```php
// При створенні
CalendarEvent::create([
    'start_date_time' => $startDateTime->setTimezone('UTC'), // Store in UTC
    'timezone' => $userTimezone, // Store original timezone
]);

// При відображенні
$localTime = $event->start_date_time->setTimezone($event->timezone);
```

---

## 🟢 Рекомендації

### 9. Додати database constraints

- [ ] **Priority: Low**
- **File:** Migrations

**Рекомендації:**

```php
// Перевірка що end_date_time > start_date_time
$table->rawCheck('end_date_time > start_date_time', 'chk_calendar_events_dates');

// Перевірка що duration > 0
$table->rawCheck('duration > 0', 'chk_calendar_events_duration');

// Перевірка валідних статусів (якщо не використовується enum)
$table->rawCheck(
    "status IN ('pending', 'confirmed', 'cancelled', 'completed')",
    'chk_calendar_events_status'
);
```

**Переваги:**

- Data integrity на рівні БД
- Захист від некоректних даних
- Навіть якщо валідація в Laravel пропущена

**Недоліки:**

- Може бути складніше тестувати
- Помилки БД замість Laravel validation errors

---

### 10. Додати composite index для range queries

- [ ] **Priority: Low**
- **File:** Migration

**Для оптимізації складних запитів:**

```php
// Для запитів з range на даті та фільтром по статусу
$table->index(['start_date_time', 'end_date_time', 'status'], 'idx_calendar_events_range');

// Для запитів з mentor_program_id та діапазоном дат
$table->index(['mentor_program_id', 'start_date_time', 'end_date_time'], 'idx_calendar_events_program_range');
```

**Коли використовувати:**

- Якщо профілювання показує повільні range queries
- При масштабуванні до >50k подій
- Коли EXPLAIN показує full table scan

---

### 11. Розглянути partitioning для великих таблиць

- [ ] **Priority: Low**
- **Scope:** Future optimization

**Для дуже великих обсягів даних (>1M записів):**

```sql
-- MySQL Partitioning by date range
ALTER TABLE calendar_events
PARTITION BY RANGE (YEAR(date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION pmax VALUES LESS THAN MAXVALUE
);
```

**Переваги:**

- Швидші queries на конкретні дати
- Легше видаляти старі дані
- Кращий розподіл навантаження

**Коли потрібно:**

- > 1M подій
- Повільні range queries навіть з індексами
- Потреба в архівації старих даних

---

### 12. Додати database seeder для тестових даних

- [ ] **Priority: Low**
- **File:** `database/seeders/CalendarEventSeeder.php`

**Рекомендація:**

```php
<?php

namespace Database\Seeders;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Enums\CalendarEventRoleEnum;
use Illuminate\Database\Seeder;

class CalendarEventSeeder extends Seeder
{
    public function run(): void
    {
        $mentors = User::role('mentor')->get();
        $mentees = User::role('mentee')->get();

        foreach ($mentors as $mentor) {
            // Створити 10-20 подій для кожного ментора
            $events = CalendarEvent::factory()
                ->count(rand(10, 20))
                ->create([
                    'mentor_program_id' => $mentor->mentorPrograms()->first()?->id,
                ]);

            foreach ($events as $event) {
                // Додати ментора як HOST
                $event->calendarEventUsers()->attach($mentor->id, [
                    'role' => CalendarEventRoleEnum::HOST->value,
                    'colour' => fake()->randomElement(['red', 'blue', 'green']),
                ]);

                // Додати випадкових mentees як PARTICIPANTS
                $participants = $mentees->random(rand(1, 3));
                foreach ($participants as $participant) {
                    $event->calendarEventUsers()->attach($participant->id, [
                        'role' => CalendarEventRoleEnum::PARTICIPANT->value,
                        'colour' => fake()->randomElement(['red', 'blue', 'green']),
                    ]);
                }
            }
        }
    }
}
```

---

## 📋 SQL Запити для перевірки

### Перевірка існуючих індексів

```sql
-- MySQL
SHOW INDEXES FROM calendar_events;
SHOW INDEXES FROM calendar_event_user;

-- PostgreSQL
SELECT * FROM pg_indexes
WHERE tablename IN ('calendar_events', 'calendar_event_user');
```

### Аналіз запитів (EXPLAIN)

```sql
-- Перевірити query plan
EXPLAIN SELECT * FROM calendar_events
WHERE date = '2025-12-10'
AND status = 'confirmed';

-- Має показувати використання індексу
-- type: ref
-- key: idx_calendar_events_date_status
```

### Знайти повільні запити

```sql
-- MySQL Slow Query Log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1; -- 1 second

-- Переглянути
SELECT * FROM mysql.slow_log
WHERE sql_text LIKE '%calendar_events%'
ORDER BY query_time DESC;
```

---

## 🎯 План виправлення

### Етап 1: Критичні індекси (2 години)

1. **Створити міграцію для індексів** (1 година)

    ```bash
    php artisan make:migration add_indexes_to_calendar_events_table
    ```

    - Додати всі необхідні індекси
    - Додати unique constraints
    - Протестувати на staging

2. **Оптимізувати Service queries** (1 година)
    - Додати eager loading
    - Оптимізувати Policy
    - Виміряти покращення продуктивності

### Етап 2: SQL Injection та безпека (1 година)

1. **Виправити GetAvailableSlotsService** (30 хвилин)
2. **Додати валідацію в Request** (30 хвилин)

### Етап 3: Важливі покращення (2 години)

1. **Додати soft deletes** (1 година)
2. **Додати timezone колонку** (30 хвилин)
3. **Додати database constraints** (30 хвилин)

### Етап 4: Тестування (2 години)

1. Performance benchmarks
2. Load testing
3. Query analysis

---

## 📊 Metrics для відстеження

### Before optimization:

```
GET /calendar/events (month view)
- Query time: ~500ms
- Queries count: 150+
- Memory: 50MB

GET /calendar/events/{id}
- Query time: ~100ms
- Queries count: 10+
- Memory: 10MB
```

### After optimization (target):

```
GET /calendar/events (month view)
- Query time: <50ms (10x faster)
- Queries count: 5-10 (15x less)
- Memory: <20MB

GET /calendar/events/{id}
- Query time: <10ms (10x faster)
- Queries count: 2-3
- Memory: <5MB
```

---

## ✅ Checklist

**Критичні:**

- [ ] Додано індекси на calendar_events (6 індексів)
- [ ] Додано індекси на calendar_event_user (3 індекси)
- [ ] Виправлено SQL injection в GetAvailableSlotsService
- [ ] Оптимізовано N+1 queries в Policy

**Важливі:**

- [ ] Додано soft deletes
- [ ] Додано timezone колонку
- [ ] Додано explicit length для title
- [ ] Додано додаткові індекси

**Рекомендовані:**

- [ ] Додано database constraints
- [ ] Додано composite indexes для range queries
- [ ] Створено seeder для тестових даних
- [ ] Розглянуто partitioning (для майбутнього)

**Тестування:**

- [ ] Виконано EXPLAIN для основних запитів
- [ ] Проведено performance benchmarks
- [ ] Виміряно покращення швидкодії
- [ ] Проведено load testing

---

## 📚 Ресурси

- [MySQL Index Documentation](https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html)
- [Laravel Eloquent Performance](https://laravel.com/docs/12.x/eloquent#eager-loading)
- [Database Indexing Best Practices](https://use-the-index-luke.com/)
- [Laravel Query Optimization](https://laravel.com/docs/12.x/queries#debugging)

---

**Останнє оновлення:** 2025-12-09  
**Створено на базі:** CODE_REVIEW_PR_83.md  
**Відповідальний:** @Asafailo
