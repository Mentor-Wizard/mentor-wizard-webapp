# Проблеми з Ролями та Авторизацією - PR #83

**Джерело:** CODE_REVIEW_PR_83.md  
**Дата створення:** 2025-12-09  
**Статус:** 🔴 Потребує виправлення

---

## Огляд

Цей документ містить всі проблеми, пов'язані з визначенням ролей, авторизацією
та політиками доступу в компоненті Календаря (PR #83). Проблеми впливають на
безпеку додатку та правильність розмежування прав доступу між користувачами.

---

## 🔴 Критичні Проблеми

### 1. Відсутня колонка `role` в withPivot

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

**Наслідки:**

- Policy не може перевірити роль користувача
- Будь-який учасник може редагувати/видаляти події
- Порушення системи авторизації
- Потенційна security vulnerability

**Виправлення:**

```php
public function calendarEventUsers(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'calendar_event_user', 'calendar_event_id')
        ->withPivot('colour', 'role')
        ->withTimestamps();
}
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

**Файли що потребують оновлення:**

- [ ] `app/Models/CalendarEvent.php` - додати 'role' в withPivot
- [ ] `app/Models/User.php` - додати 'role' в withPivot
- [ ] Перевірити всі місця де використовується relationship

---

### 2. Дублювання авторизації в Action

- [ ] **Priority: Critical**
- **File:** `app/Actions/Calendar/StoreCalendarEvent.php`
- **Line:** 21

**Проблема:**

```php
abort_if($request->user()->cannot('create', CalendarEvent::class),
    Response::HTTP_FORBIDDEN, 'Unauthorized action.');
```

**Чому це проблема:**

- Авторизація має бути в middleware, не в екшені
- Порушення separation of concerns
- Дублювання логіки авторизації
- Ускладнює тестування та підтримку
- Може пропустити перевірки якщо забути додати в новий екшн

**Виправлення:**

**Варіант 1 (Рекомендований):** Використати Route middleware

```php
// routes/web.php
Route::post('/calendar/events', StoreCalendarEvent::class)
    ->middleware('can:create,App\Models\CalendarEvent')
    ->name('calendar.store');
```

**Варіант 2:** Використати Gate в екшені (якщо потрібна складна логіка)

```php
// В handle методі
Gate::authorize('create', CalendarEvent::class);
```

**Варіант 3:** Використати Form Request authorization

```php
// В StoreCalendarEventRequest
public function authorize(): bool
{
    return $this->user()->can('create', CalendarEvent::class);
}
```

**Рекомендація:** Прибрати `abort_if` з екшену та використати middleware на
рівні маршруту.

**Файли що потребують оновлення:**

- [ ] `app/Actions/Calendar/StoreCalendarEvent.php` - прибрати abort_if
- [ ] `app/Actions/Calendar/UpdateCalendarEvent.php` - перевірити чи немає
      аналогічного коду
- [ ] `app/Actions/Calendar/DeleteCalendarEvent.php` - перевірити чи немає
      аналогічного коду
- [ ] `routes/web.php` - додати middleware на маршрути

---

### 3. N+1 Запити в Policy

- [ ] **Priority: High**
- **File:** `app/Policies/CalendarEventPolicy.php`
- **Lines:** 21-24, 29-32, 35-37

**Проблема:**

Кожна перевірка policy робить окремий запит до БД:

```php
public function update(User $user, CalendarEvent $calendarEvent): bool
{
    return $user->hasRole('mentor')
        && $calendarEvent->calendarEventUsers()
            ->where('user_id', $user->getKey())
            ->where('role', CalendarEventRoleEnum::HOST->value)
            ->exists(); // Окремий запит!
}
```

**Наслідки:**

- При перевірці списку подій це O(n) запитів
- Кожна перевірка policy = 1 додатковий запит
- 100 подій = 100+ запитів до БД
- Серйозні проблеми з продуктивністю

**Приклад:**

```php
// Якщо перевіряємо 20 подій
@foreach($events as $event)
    @can('update', $event) // 20 запитів!
        <button>Edit</button>
    @endcan
@endforeach
```

**Виправлення:**

**Крок 1:** Eager load в Action/Controller/Resource:

```php
// CalendarsListPage.php або інших місцях
$events = CalendarEvent::query()
    ->with('calendarEventUsers') // Eager load!
    ->get();
```

**Крок 2:** Змінити Policy на in-memory filtering:

```php
public function update(User $user, CalendarEvent $calendarEvent): bool
{
    // Якщо relationship не loaded - fallback to query
    if (!$calendarEvent->relationLoaded('calendarEventUsers')) {
        return $user->hasRole('mentor')
            && $calendarEvent->calendarEventUsers()
                ->where('user_id', $user->getKey())
                ->where('role', CalendarEventRoleEnum::HOST->value)
                ->exists();
    }

    // Використовуємо loaded relationship
    return $user->hasRole('mentor')
        && $calendarEvent->calendarEventUsers
            ->where('id', $user->getKey())
            ->where('pivot.role', CalendarEventRoleEnum::HOST->value)
            ->isNotEmpty();
}
```

**Альтернативний підхід:** Використати Policy filter

```php
// В CalendarEventPolicy
public function before(User $user, string $ability): bool|null
{
    if ($user->hasRole('admin')) {
        return true; // Admins can do everything
    }

    return null; // Continue to policy method
}
```

**Файли що потребують оновлення:**

- [ ] `app/Policies/CalendarEventPolicy.php` - оптимізувати всі методи
- [ ] Controllers/Resources - додати eager loading
- [ ] Переписати методи: `update()`, `delete()`, `view()`

---

## 🟡 Важливі Покращення

### 4. Відсутність перевірки ролі при створенні події

- [ ] **Priority: High**
- **File:** `app/Policies/CalendarEventPolicy.php`
- **Line:** 16-18

**Поточний код:**

```php
public function create(User $user): bool
{
    return $user->hasRole('mentor');
}
```

**Проблема:**

Тільки перевіряється чи користувач є ментором, але не перевіряється:

- Чи може ментор створювати події для конкретної програми?
- Чи не перевищено ліміт подій?
- Чи активна програма менторства?

**Рекомендоване покращення:**

```php
public function create(User $user): bool
{
    if (!$user->hasRole('mentor')) {
        return false;
    }

    // Додаткові перевірки
    return $user->mentorPrograms()
        ->where('status', MentorProgramStatusEnum::ACTIVE)
        ->exists();
}
```

Або якщо потрібно перевіряти конкретну програму:

```php
public function createForProgram(User $user, MentorProgram $program): bool
{
    return $user->hasRole('mentor')
        && $program->mentor_id === $user->getKey()
        && $program->status === MentorProgramStatusEnum::ACTIVE;
}
```

---

### 5. Немає перевірки на дублювання ролей

- [ ] **Priority: Medium**
- **File:**
  `database/migrations/2025_07_06_205111_create_calendar_event_user_table.php`

**Проблема:**

Користувач може бути доданий до події кілька разів з різними ролями:

```php
// Можливо:
User #1 - Event #5 - Role: HOST
User #1 - Event #5 - Role: PARTICIPANT
```

**Виправлення:**

Додати унікальний індекс в міграцію:

```php
$table->unique(['user_id', 'calendar_event_id', 'role']); // prevent duplicates
```

Або якщо користувач може мати тільки одну роль на подію:

```php
$table->unique(['user_id', 'calendar_event_id']); // one role per user per event
```

**Додатково:** Додати валідацію в бізнес-логіку

```php
// В Service або Action
public function attachUserToEvent(User $user, CalendarEvent $event, string $role): void
{
    // Перевірити чи користувач вже є учасником
    if ($event->calendarEventUsers()->where('user_id', $user->getKey())->exists()) {
        throw new \DomainException('User is already attached to this event');
    }

    $event->calendarEventUsers()->attach($user->getKey(), [
        'role' => $role,
        'colour' => CalendarEventColoursEnum::randomValue(),
    ]);
}
```

---

### 6. Відсутність ролі PARTICIPANT в Enum

- [ ] **Priority: Medium**
- **File:** `app/Enums/CalendarEventRoleEnum.php`

**Припущення:**

Якщо існує роль `HOST`, ймовірно має бути і `PARTICIPANT` або `ATTENDEE`.

**Рекомендація:**

```php
enum CalendarEventRoleEnum: string
{
    case HOST = 'host';
    case PARTICIPANT = 'participant';
    case OBSERVER = 'observer'; // optional

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isHost(): bool
    {
        return $this === self::HOST;
    }

    public function canEdit(): bool
    {
        return $this === self::HOST;
    }

    public function canView(): bool
    {
        return true; // All roles can view
    }
}
```

**Оновити Policy:**

```php
public function update(User $user, CalendarEvent $calendarEvent): bool
{
    return $user->hasRole('mentor')
        && $calendarEvent->calendarEventUsers
            ->where('id', $user->getKey())
            ->filter(fn($u) => CalendarEventRoleEnum::from($u->pivot->role)->canEdit())
            ->isNotEmpty();
}
```

---

## 🟢 Рекомендації

### 7. Використання Policy Gates в Blade/Inertia

- [ ] **Priority: Low**
- **Scope:** Frontend

**Рекомендація:**

Переконатись що всі дії в UI перевіряють policy:

```blade
{{-- Blade --}}
@can('update', $event)
    <button>Edit Event</button>
@endcan

@can('delete', $event)
    <button>Delete Event</button>
@endcan
```

```js
// Inertia/React
{
    can.update && <Button onClick={() => editEvent(event)}>Edit Event</Button>;
}

{
    can.delete && (
        <Button onClick={() => deleteEvent(event)}>Delete Event</Button>
    );
}
```

**Передача permissions в Inertia:**

```php
// В Resource або Controller
return Inertia::render('Calendar/Show', [
    'event' => $event,
    'can' => [
        'update' => Gate::allows('update', $event),
        'delete' => Gate::allows('delete', $event),
        'invite' => Gate::allows('invite', $event),
    ],
]);
```

---

### 8. Додати Role Helper Methods

- [ ] **Priority: Low**
- **File:** `app/Models/User.php`

**Рекомендація:**

Додати helper методи для перевірки ролі в контексті події:

```php
// В User model
public function isHostOf(CalendarEvent $event): bool
{
    return $this->calendarEvents()
        ->where('calendar_event_id', $event->getKey())
        ->wherePivot('role', CalendarEventRoleEnum::HOST->value)
        ->exists();
}

public function isParticipantOf(CalendarEvent $event): bool
{
    return $this->calendarEvents()
        ->where('calendar_event_id', $event->getKey())
        ->wherePivot('role', CalendarEventRoleEnum::PARTICIPANT->value)
        ->exists();
}

public function getRoleFor(CalendarEvent $event): ?CalendarEventRoleEnum
{
    $pivot = $this->calendarEvents()
        ->where('calendar_event_id', $event->getKey())
        ->first()
        ?->pivot;

    return $pivot ? CalendarEventRoleEnum::from($pivot->role) : null;
}
```

**Використання:**

```php
// В Policy
public function update(User $user, CalendarEvent $calendarEvent): bool
{
    return $user->hasRole('mentor') && $user->isHostOf($calendarEvent);
}

// В Controller
if ($user->isHostOf($event)) {
    // Allow action
}
```

---

### 9. Додати Scope для фільтрації по ролі

- [ ] **Priority: Low**
- **File:** `app/Models/CalendarEvent.php`

**Рекомендація:**

```php
// В CalendarEvent model
public function scopeWhereUserHasRole(Builder $query, User $user, CalendarEventRoleEnum $role): Builder
{
    return $query->whereHas('calendarEventUsers', function ($q) use ($user, $role) {
        $q->where('user_id', $user->getKey())
          ->where('role', $role->value);
    });
}

public function scopeWhereUserIsHost(Builder $query, User $user): Builder
{
    return $query->whereUserHasRole($user, CalendarEventRoleEnum::HOST);
}

public function scopeWhereUserIsParticipant(Builder $query, User $user): Builder
{
    return $query->whereUserHasRole($user, CalendarEventRoleEnum::PARTICIPANT);
}
```

**Використання:**

```php
// Отримати всі події де користувач є host
$hostedEvents = CalendarEvent::whereUserIsHost($user)->get();

// Отримати всі події де користувач є participant
$participatingEvents = CalendarEvent::whereUserIsParticipant($user)->get();

// Або всі події користувача
$allUserEvents = CalendarEvent::whereHas('calendarEventUsers', function ($q) use ($user) {
    $q->where('user_id', $user->getKey());
})->get();
```

---

## 📋 Тести що потребують додавання

### Policy Tests

- [ ] **Test:** Mentor може створювати події

    ```php
    it('allows mentor to create events', function () {
        $mentor = User::factory()->mentor()->create();

        expect($mentor->can('create', CalendarEvent::class))->toBeTrue();
    });
    ```

- [ ] **Test:** Mentee не може створювати події

    ```php
    it('prevents mentee from creating events', function () {
        $mentee = User::factory()->mentee()->create();

        expect($mentee->can('create', CalendarEvent::class))->toBeFalse();
    });
    ```

- [ ] **Test:** Host може редагувати подію

    ```php
    it('allows host to update event', function () {
        $mentor = User::factory()->mentor()->create();
        $event = CalendarEvent::factory()->create();

        $event->calendarEventUsers()->attach($mentor->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);

        expect($mentor->can('update', $event))->toBeTrue();
    });
    ```

- [ ] **Test:** Participant не може редагувати подію

    ```php
    it('prevents participant from updating event', function () {
        $mentee = User::factory()->mentee()->create();
        $event = CalendarEvent::factory()->create();

        $event->calendarEventUsers()->attach($mentee->getKey(), [
            'role' => CalendarEventRoleEnum::PARTICIPANT->value,
        ]);

        expect($mentee->can('update', $event))->toBeFalse();
    });
    ```

- [ ] **Test:** Host може видаляти подію

    ```php
    it('allows host to delete event', function () {
        $mentor = User::factory()->mentor()->create();
        $event = CalendarEvent::factory()->create();

        $event->calendarEventUsers()->attach($mentor->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);

        expect($mentor->can('delete', $event))->toBeTrue();
    });
    ```

### Authorization Tests

- [ ] **Test:** Заборона створення події без ролі mentor

    ```php
    it('prevents creating event without mentor role', function () {
        $user = User::factory()->create(); // no role

        actingAs($user)
            ->post(route('calendar.store'), [
                // valid data
            ])
            ->assertForbidden();
    });
    ```

- [ ] **Test:** Заборона редагування події без ролі host

    ```php
    it('prevents editing event without host role', function () {
        $user = User::factory()->create();
        $event = CalendarEvent::factory()->create();

        actingAs($user)
            ->put(route('calendar.update', $event), [
                // valid data
            ])
            ->assertForbidden();
    });
    ```

### Role Tests

- [ ] **Test:** Перевірка role в pivot таблиці

    ```php
    it('stores role in pivot table', function () {
        $mentor = User::factory()->mentor()->create();
        $event = CalendarEvent::factory()->create();

        $event->calendarEventUsers()->attach($mentor->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);

        expect($event->calendarEventUsers->first()->pivot->role)
            ->toBe(CalendarEventRoleEnum::HOST->value);
    });
    ```

- [ ] **Test:** Неможливість дублювання ролей

    ```php
    it('prevents duplicate role assignments', function () {
        $user = User::factory()->create();
        $event = CalendarEvent::factory()->create();

        $event->calendarEventUsers()->attach($user->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);

        // Спроба додати ще раз
        expect(fn() => $event->calendarEventUsers()->attach($user->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]))->toThrow(QueryException::class);
    });
    ```

---

## 🎯 План виправлення

### Етап 1: Критичні виправлення (3 години)

1. **Додати 'role' в withPivot** (30 хвилин)
    - Оновити CalendarEvent model
    - Оновити User model
    - Перевірити всі використання relationship

2. **Винести авторизацію з Action** (1 година)
    - Додати middleware на маршрути
    - Прибрати abort_if з екшенів
    - Оновити тести

3. **Оптимізувати Policy (N+1)** (1.5 години)
    - Додати eager loading в Resources
    - Переписати Policy методи
    - Протестувати продуктивність

### Етап 2: Важливі покращення (2 години)

1. **Покращити Policy::create()** (30 хвилин)
2. **Додати unique constraint на ролі** (30 хвилин)
3. **Розширити CalendarEventRoleEnum** (1 година)

### Етап 3: Тести (3 години)

1. Написати Policy tests
2. Написати Authorization tests
3. Написати Role tests

---

## ✅ Checklist

**Критичні:**

- [ ] Додано 'role' в withPivot для обох моделей
- [ ] Авторизація винесена на рівень middleware
- [ ] Оптимізовано Policy (додано eager loading)

**Важливі:**

- [ ] Покращено Policy::create() з додатковими перевірками
- [ ] Додано unique constraint для запобігання дублювання ролей
- [ ] Розширено CalendarEventRoleEnum (додано PARTICIPANT)

**Рекомендовані:**

- [ ] Додано перевірки policy в UI
- [ ] Додано helper методи для ролей в User model
- [ ] Додано scopes для фільтрації по ролям

**Тести:**

- [ ] Написано Policy tests (6 тестів)
- [ ] Написано Authorization tests (2 тести)
- [ ] Написано Role tests (2 тести)

---

**Останнє оновлення:** 2025-12-09  
**Створено на базі:** CODE_REVIEW_PR_83.md  
**Відповідальний:** @Asafailo
