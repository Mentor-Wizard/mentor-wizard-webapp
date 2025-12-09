# Проблеми з Таймзонами та Датами - PR #83

**Джерело:** CODE_REVIEW_PR_83.md  
**Дата створення:** 2025-12-09  
**Статус:** 🔴 Потребує виправлення

---

## Огляд

Цей документ містить всі проблеми, пов'язані з обробкою таймзон та дат у
компоненті Календаря (PR #83). Проблеми впливають на коректність збереження,
відображення та валідації подій у різних часових зонах.

---

## 🔴 Критичні Проблеми

### 1. Неконсистентна обробка часових зон

- [ ] **Priority: Critical**
- **Files:**
    - `app/Http/Requests/Calendar/StoreCalendarEventRequest.php:100-110`
    - `app/Http/Requests/Calendar/EditCalendarEventRequest.php:100-110`
    - Multiple Service classes

**Проблема:**

Код змішує `config('app.timezone')` з user-provided timezone:

```php
// StoreCalendarEventRequest.php:105
->setTimezone(config('app.timezone')); // має бути UTC!

// EditCalendarEventRequest.php:108
->setTimezone('UTC'); // тут правильно, але inconsistent
```

**Поточна ситуація:**

- `StoreCalendarEventRequest` конвертує в `config('app.timezone')`
- `EditCalendarEventRequest` конвертує в `UTC`
- Немає єдиного підходу до збереження дат в БД
- Плутанина між timezone користувача та timezone зберігання

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

**Файли що потребують перевірки:**

- [ ] `app/Http/Requests/Calendar/StoreCalendarEventRequest.php`
- [ ] `app/Http/Requests/Calendar/EditCalendarEventRequest.php`
- [ ] `app/Services/Calendar/GetDailyCalendarEventsService.php`
- [ ] `app/Services/Calendar/GetMonthCalendarEventsService.php`
- [ ] `app/Services/Calendar/GetWeeklyCalendarEventsService.php`
- [ ] `app/Services/Calendar/CheckTimeSlotReservedService.php`

**Обґрунтування:**

- Зберігання в UTC є стандартною практикою
- Дозволяє уникнути проблем з DST (daylight saving time)
- Спрощує роботу з користувачами з різних часових зон
- Конвертація виконується тільки на рівні представлення (Resources)

**Вплив:**

- 🔴 **Критичний** - може призвести до неправильного відображення часу подій
- Може створити плутанину для користувачів у різних часових зонах
- Ускладнює debugging та troubleshooting

---

### 2. Мовчазне повернення при помилці парсингу дати

- [ ] **Priority: Medium**
- **Files:**
    - `app/Http/Requests/Calendar/StoreCalendarEventRequest.php:111-113`
    - `app/Http/Requests/Calendar/EditCalendarEventRequest.php` (аналогічно)

**Проблема:**

```php
} catch (Exception) {
    return [];
}
```

Мовчазні провали приховують баги при обробці дат.

**Чому це проблема:**

- Користувач не отримує зрозумілої помилки
- Складно debug-ити проблеми з форматом дати
- Може призвести до створення події з порожніми даними
- Порушує принцип "fail fast"

**Виправлення:**

```php
} catch (Exception $e) {
    throw ValidationException::withMessages([
        'fromDate' => __('calendar.validation.datetime_parse_error'),
    ]);
}
```

**Додаткові перевірки:**

```php
// Додати в withValidator
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
```

---

## 🟡 Важливі Покращення

### 3. Валідація timezone

- [ ] **Priority: Medium**
- **Files:**
    - `app/Http/Requests/Calendar/StoreCalendarEventRequest.php`
    - `app/Http/Requests/Calendar/EditCalendarEventRequest.php`

**Проблема:**

```php
'timezone' => ['required', 'string'],
```

Немає валідації що timezone є валідним PHP timezone identifier.

**Виправлення:**

```php
'timezone' => ['required', 'string', 'timezone:all'],
```

**Обґрунтування:**

- Захищає від неправильних timezone значень
- Використовує вбудовану Laravel валідацію
- `timezone:all` валідує проти списку PHP `DateTimeZone::listIdentifiers()`

**Приклади валідних timezone:**

- `Europe/Kiev`
- `America/New_York`
- `UTC`
- `Asia/Tokyo`

**Приклади невалідних timezone:**

- `GMT+2` (застаріле)
- `Eastern Time` (не ISO формат)
- `Kiev` (без регіону)

---

### 4. Відсутність індексів на дати

- [ ] **Priority: High**
- **File:**
  `database/migrations/2025_07_06_205059_create_calendar_events_table.php`

**Проблема:**

Немає індексів на колонках дат, які активно використовуються в запитах.

**Виправлення:**

```php
// В міграції calendar_events
$table->index('start_date_time');
$table->index('date');
$table->index('end_date_time');
$table->index(['mentor_program_id', 'start_date_time']); // composite для filtering
```

**Обґрунтування:**

Сервіси що покладаються на ці індекси:

- `GetDailyCalendarEventsService` - фільтрує по `date`
- `GetMonthCalendarEventsService` - фільтрує по `start_date_time`
- `GetWeeklyCalendarEventsService` - використовує range queries
- `CheckTimeSlotReservedService` - перевіряє overlap по start/end dates

**Без індексів:**

- Full table scan для кожного запиту
- O(n) складність при зростанні кількості подій
- Повільні відповіді API
- Проблеми з продуктивністю при масштабуванні

**З індексами:**

- O(log n) складність для пошуку
- Швидкі range queries
- Оптимізація composite запитів

---

## 🟢 Рекомендації

### 5. Додати перевірку на DST (Daylight Saving Time)

- [ ] **Priority: Low**
- **Scope:** Service Layer

**Проблема:**

При переході на літній/зимовий час можуть виникнути проблеми:

- "Зникання" години при переході на літній час
- Дублювання години при переході на зимовий час

**Рекомендоване рішення:**

```php
// В BaseCalendarEventRequest або Service
protected function validateDSTTransition(Carbon $startDateTime, Carbon $endDateTime): void
{
    // Перевірити чи event не потрапляє в "зниклу" годину
    if ($startDateTime->isDST() !== $endDateTime->isDST()) {
        // Warn user або adjust time
    }
}
```

**Альтернатива:**

- Використовувати UTC для всіх розрахунків (рекомендовано)
- Конвертувати в локальний час тільки для відображення

---

### 6. Додати timezone в CalendarEvent модель

- [ ] **Priority: Low**
- **File:** `app/Models/CalendarEvent.php`

**Поточна ситуація:**

Timezone зберігається лише в pivot таблиці або взагалі не зберігається.

**Рекомендація:**

```php
// Додати колонку в міграцію
$table->string('timezone')->default('UTC');

// Додати в модель
protected function casts(): array
{
    return [
        'start_date_time' => 'datetime',
        'end_date_time' => 'datetime',
        'date' => 'date',
        'timezone' => 'string',
    ];
}

// Accessor для конвертації в timezone події
public function getLocalStartTimeAttribute(): Carbon
{
    return $this->start_date_time->setTimezone($this->timezone);
}
```

**Переваги:**

- Зберігає інформацію про оригінальну timezone події
- Дозволяє правильно відображати час для всіх учасників
- Спрощує логіку конвертації

---

### 7. Додати максимальну тривалість події

- [ ] **Priority: Low**
- **Files:** Request validation

**Рекомендація:**

```php
// Додати константу в модель
public const int MAXIMUM_EVENT_DURATION_HOURS = 24;

// Додати валідацію в Request
public function withValidator(Validator $validator): void
{
    $validator->after(function ($validator): void {
        $start = Date::parse($this->input('fromDate').' '.$this->input('fromTime'));
        $end = Date::parse($this->input('toDate').' '.$this->input('toTime'));

        $durationHours = $start->diffInHours($end);

        if ($durationHours > CalendarEvent::MAXIMUM_EVENT_DURATION_HOURS) {
            $validator->errors()->add(
                'toDate',
                __('calendar.validation.event_too_long', [
                    'max' => CalendarEvent::MAXIMUM_EVENT_DURATION_HOURS
                ])
            );
        }
    });
}
```

---

## 📋 Тести що потребують додавання

### Unit Tests

- [ ] **Test:** Конвертація timezone при збереженні

    ```php
    it('converts event datetime to UTC when storing', function () {
        $data = [
            'fromDate' => '2025-12-10',
            'fromTime' => '14:00',
            'toDate' => '2025-12-10',
            'toTime' => '15:00',
            'timezone' => 'Europe/Kiev',
        ];

        // Kiev is UTC+2
        // 14:00 Kiev = 12:00 UTC

        $event = CalendarEvent::create(...);

        expect($event->start_date_time->timezone->getName())->toBe('UTC');
        expect($event->start_date_time->format('H:i'))->toBe('12:00');
    });
    ```

- [ ] **Test:** Валідація невалідної timezone

    ```php
    it('rejects invalid timezone', function () {
        $response = $this->postJson('/calendar/events', [
            'timezone' => 'Invalid/Timezone',
            // ... інші дані
        ]);

        $response->assertJsonValidationErrors('timezone');
    });
    ```

- [ ] **Test:** Події через межу DST

    ```php
    it('handles DST transition correctly', function () {
        // Створити подію на межі переходу на літній час
        // Перевірити що час зберігається коректно
    });
    ```

- [ ] **Test:** Учасники з різних часових зон

    ```php
    it('displays event in correct timezone for each participant', function () {
        $mentor = User::factory()->create(['timezone' => 'America/New_York']);
        $mentee = User::factory()->create(['timezone' => 'Europe/Kiev']);

        $event = CalendarEvent::factory()->create([
            'start_date_time' => '2025-12-10 12:00:00', // UTC
        ]);

        // Mentor (UTC-5) повинен бачити 07:00
        // Mentee (UTC+2) повинен бачити 14:00
    });
    ```

- [ ] **Test:** Парсинг неправильного формату дати

    ```php
    it('throws validation error for invalid date format', function () {
        $response = $this->postJson('/calendar/events', [
            'fromDate' => '2025-13-45', // invalid date
            'fromTime' => '14:00',
            // ...
        ]);

        $response->assertJsonValidationErrors('fromDate');
    });
    ```

### Integration Tests

- [ ] **Test:** Перевірка overlap подій у різних timezone
- [ ] **Test:** Створення події в минулому (має бути заборонено)
- [ ] **Test:** Максимальна тривалість події
- [ ] **Test:** Події на межі допустимого періоду (6 місяців)

---

## 🎯 План виправлення

### Етап 1: Критичні виправлення (4 години)

1. **Уніфікувати обробку timezone** (2 години)
    - Створити trait `HandlesTimezone`
    - Виправити `StoreCalendarEventRequest`
    - Виправити `EditCalendarEventRequest`
    - Оновити всі Service класи

2. **Виправити error handling** (1 година)
    - Замінити `return []` на `ValidationException`
    - Додати детальні повідомлення про помилки

3. **Додати індекси на дати** (1 година)
    - Створити нову міграцію для додавання індексів
    - Протестувати на staging

### Етап 2: Важливі покращення (2 години)

1. **Додати валідацію timezone** (30 хвилин)
2. **Оновити тести** (1.5 години)

### Етап 3: Рекомендації (за бажанням)

1. Додати перевірку DST
2. Зберігати timezone в події
3. Додати максимальну тривалість

---

## 📚 Ресурси

- [PHP Timezones List](https://www.php.net/manual/en/timezones.php)
- [Laravel Date/Time Handling](https://laravel.com/docs/12.x/dates)
- [Carbon Documentation](https://carbon.nesbot.com/docs/)
- [DST Best Practices](https://stackoverflow.com/questions/2532729/daylight-saving-time-and-time-zone-best-practices)

---

## ✅ Checklist

**Критичні:**

- [ ] Уніфіковано обробку timezone (UTC в БД)
- [ ] Виправлено мовчазні провали при парсингу дат
- [ ] Додано індекси на колонки дат

**Важливі:**

- [ ] Додано валідацію timezone
- [ ] Написано unit тести для timezone
- [ ] Написано integration тести

**Рекомендовані:**

- [ ] Додано перевірку DST
- [ ] Додано timezone в модель CalendarEvent
- [ ] Додано обмеження на максимальну тривалість

---

**Останнє оновлення:** 2025-12-09  
**Створено на базі:** CODE_REVIEW_PR_83.md  
**Відповідальний:** @Asafailo
