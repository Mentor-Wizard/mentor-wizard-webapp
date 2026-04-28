# CodeRabbit Review Findings

This document contains the results of the CodeRabbit review for the Mentor
Wizard project.

## Summary of Review Process

- **Review Directory:**
  `/home/vadym/programming/PersonalProjects/mentor-wizard-webapp`
- **Status:** Review completed: 97 findings ✔

## 21. File: `app/Http/Requests/Calendar/ExternalCalendarDisconnectRequest.php`

- **Lines:** 30 to 36
- **Type:** `potential_issue`
- **Comment:** Потенційна помилка типізації: `cleanupIntegration` викликється з
  невалідним провайдером. Якщо валідація провалюється саме через те, що
  `resolveProvider()` не є `CalendarProviderEnum`, тоді цей самий невалідний
  результат передається в `cleanupIntegration()`. Це може спричинити type error
  або некоректну поведінку.

### 🐛 Proposed Fix

```php
#[Override]
protected function failedValidation(Validator $validator): never
{
-    $this->cleanupIntegration($this->user(), $this->resolveProvider());
+    $provider = $this->resolveProvider();
+    if ($provider instanceof CalendarProviderEnum) {
+        $this->cleanupIntegration($this->user(), $provider);
+    }

    parent::failedValidation($validator);
}
```

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Http/Requests/Calendar/ExternalCalendarDisconnectRequest.php` around
> lines 30 - 36, failedValidation currently calls
> cleanupIntegration($this->user(), $this->resolveProvider()) even when resolveProvider() may return an invalid value; update failedValidation to first ensure the resolved provider is a valid CalendarProviderEnum (e.g., call $provider = $this->resolveProvider(); check $provider instanceof CalendarProviderEnum or use CalendarProviderEnum::tryFrom($provider)
> / null-coalescing) and only call cleanupIntegration($this->user(), $provider)
> when the provider is valid; if invalid, skip cleanupIntegration or handle the
> invalid case explicitly to avoid type errors in cleanupIntegration.

---

## 22. File: `.env.example`

- **Lines:** 129 to 134
- **Type:** `potential_issue`
- **Comment:** Перевірте взаємну виключність Google-провайдерів під час запуску.
  Коментар попереджає: "Only one of the two Google options should be active at a
  time", але немає механізму runtime-валідації. Конфлікт конфігурації може
  призвести до непередбачуваної поведінки інтеграції календаря.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@.env.example` around lines 129 - 134, The .env example warns that only
> one Google provider should be active but there is no runtime check; add a
> startup/config validation (e.g., a new validateGoogleProviderConfig function
> called during app bootstrap or in the relevant service provider) that reads
> GOOGLE_CALENDAR_CLIENT_ID and GOOGLE_CALENDAR_CLIENT_SECRET and the flag/state
> that enables the GooglePersonalApp provider, and throw/log a clear error and
> halt startup if both the app-level credentials and the GooglePersonalApp
> provider are active at the same time; ensure the error message references
> GOOGLE_CALENDAR_CLIENT_ID/GOOGLE_CALENDAR_CLIENT_SECRET and "GooglePersonalApp
> provider" so it's easy to find and fix.

---

## 23. File: `app/Http/Requests/Calendar/ExternalCalendarConnectCallbackRequest.php`

- **Lines:** 31 to 37
- **Type:** `potential_issue`
- **Comment:** Потенційна проблема з null-користувачем у `failedValidation()`.
  `$this->user()` може повернути null, якщо користувач не автентифікований. Якщо
  `cleanupIntegration()` не обробляє null коректно, це призведе до помилки.
  Також, якщо `resolveProvider()` викликне виключення,
  `parent::failedValidation()` не буде виклично, що може порушити очікувану
  поведінку Laravel.

### 🛡️ Proposed Fix

```php
#[Override]
protected function failedValidation(Validator $validator): never
{
-    $provider = $this->resolveProvider();
-    $this->cleanupIntegration($this->user(), $provider);
+    $user = $this->user();
+    if ($user !== null) {
+        $provider = $this->resolveProvider();
+        $this->cleanupIntegration($user, $provider);
+    }

    parent::failedValidation($validator);
}
```

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Http/Requests/Calendar/ExternalCalendarConnectCallbackRequest.php`
> around lines 31 - 37, failedValidation currently calls
> $this->resolveProvider() and $this->cleanupIntegration($this->user(),
> $provider) without guarding against a null user or exceptions from resolveProvider; change it so resolveProvider() is called inside a try block, only call cleanupIntegration if $this->user() is non-null (or if ExternalCalendarRequest::cleanupIntegration() explicitly accepts null—verify its signature) and the provider was resolved, and ensure parent::failedValidation($validator)
> is always executed (use a try/finally so parent::failedValidation runs even if
> resolveProvider throws); reference methods: failedValidation, resolveProvider,
> cleanupIntegration, and parent::failedValidation on ExternalCalendarRequest.

---

## 28. File: `app/Services/Calendar/SplitSlotsPerSessionDuration.php`

- **Lines:** 32 to 33
- **Type:** `potential_issue`
- **Comment:** Критична помилка: `Date::create()` з Carbon об'єктом.
  `Date::create()` (тобто `Carbon::create()`) очікує окремі компоненти дати
  (`$year`, `$month`, `$day`...), а не Carbon об'єкт. Передача Carbon об'єкта як
  першого параметра буде інтерпретована як рік, що призведе до некоректної дати.
  `ceilHour()` вже повертає Carbon екземпляр — додатковий виклик
  `Date::create()` не потрібен і є помилковим.

### 🐛 Proposed Fix

```php
-            $slotStart = Date::create($slot['start']->ceilHour());
+            $slotStart = $slot['start']->ceilHour();
```

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Services/Calendar/SplitSlotsPerSessionDuration.php` around lines 32 -
> 33, The code incorrectly calls Date::create() with a Carbon object: replace
> the
> Date::create($slot['start']->ceilHour()) usage in SplitSlotsPerSessionDuration with the Carbon instance returned by $slot['start']->ceilHour() (or a cloned/immutable copy if mutation is a concern) so $slotStart is a valid Carbon object and $slotStart->diffInMinutes($slot['end'])
> produces the correct $slotDuration; remove the unnecessary Date::create call
> and use $slot['start']->ceilHour() directly.

---

## 31. File: `app/Services/ExternalCalendar/AppleCalDavExternalCalendarService.php`

- **Lines:** 191 to 196
- **Type:** `potential_issue`
- **Comment:** Метод `header()` може повернути `null`, а не порожній рядок. Якщо
  заголовок `Location` відсутній, `$response->header('Location')` поверне
  `null`, а не порожній рядок.

### 🛡️ Proposed Fix

```php
if ($response->redirect()) {
    $redirectUrl = $response->header('Location');

-    if ($redirectUrl === '') {
+    if ($redirectUrl === null || $redirectUrl === '') {
        return null;
    }
}
```

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Services/ExternalCalendar/AppleCalDavExternalCalendarService.php`
> around lines 191 - 196, The code assumes
> $response->header('Location') returns a string but it can be null; update the redirect handling in AppleCalDavExternalCalendarService (the block using $response->redirect() and $redirectUrl = $response->header('Location')) to explicitly treat a missing Location header as absent by checking for null or empty string (e.g., if ($redirectUrl
> === null || $redirectUrl === '') ) and return null in that case; keep using
> the same $response->redirect() check and only proceed when $redirectUrl is a
> non-empty string.

---

## 33. File: `app/Actions/Pages/Profile/GetMentorProfilePage.php`

- **Lines:** 39 to 40
- **Type:** `potential_issue`
- **Comment:** Відсутня валідація параметра `calendar_date`. `Date::parse()`
  може викликнути виняток, якщо `$dateInput` мітримує некоректний формат дати.
  Варто або валідувати вхідні дані, або обгорнути в try-catch.

### 🛡️ Proposed Fix

```php
$dateInput = $request->get('calendar_date');
- $date = $dateInput ? Date::parse($dateInput, $timezone) : Date::now($timezone);
+ try {
+     $date = $dateInput ? Date::parse($dateInput, $timezone) : Date::now($timezone);
+ } catch (\Exception) {
+     $date = Date::now($timezone);
+ }
```

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Actions/Pages/Profile/GetMentorProfilePage.php` around lines 39 - 40,
> Validate or safely parse the incoming calendar_date before calling Date::parse
> to avoid exceptions: either add request validation (e.g. use
> $request->validate(['calendar_date' => 'nullable|date']) in the controller handling GetMentorProfilePage) or wrap Date::parse($dateInput,
> $timezone) in a try-catch that falls back to Date::now($timezone) and logs the
> parse error; ensure you reference the existing $dateInput, Date::parse, and
> Date::now symbols so the change is made in the same method that currently
> reads $request->get('calendar_date').

---

## 41. File: `app/Actions/Pages/Profile/ListMentorProfilePage.php`

- **Lines:** 60 to 65
- **Type:** `potential_issue`
- **Comment:** Потенційний NPE при відсутності профілю користувача. Якщо
  `profile` дорівнює null, рядки 61-62 викликнуть помилку. Використовуйте
  null-safe оператор для консистентності з рядком 59. Також у рядку 64 оператор
  `=` є зайвим у методі `where()` колекції Laravel.

### 🛡️ Proposed Fix

```php
-                'userName'        => $mentor->user->profile->name,
-                'userAvatar'      => $mentor->user->profile->avatar,
+                'userName'        => $mentor->user?->profile?->name,
+                'userAvatar'      => $mentor->user?->profile?->avatar,
                 'mainProgramSlug' => $mentor->user->mentorPrograms
-                    ->where('is_main', '=', true)->first()?->slug,
+                    ->where('is_main', true)->first()?->slug,
```

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Actions/Pages/Profile/ListMentorProfilePage.php` around lines 60 -
> 65, The code accesses mentor->user->profile->name and ->avatar which can be
> null; change those to use the null-safe operator (mentor->user->profile?->name
> and mentor->user->profile?->avatar) to avoid NPEs, and simplify the collection
> where call on mentor->user->mentorPrograms by removing the unnecessary '=' so
> use where('is_main', true)->first()?->slug instead of where('is_main', '=',
> true)->first()?->slug.

---

## 42. File: `app/Actions/Calendar/ExternalCalendar/RerunExternalCalendarEventSync.php`

- **Lines:** 19 to 30
- **Type:** `potential_issue`
- **Comment:** Відсутня перевірка авторизації та валідація зв'язків між
  моделями.

1. Авторизація: Немає перевірки, чи поточний користувач має право ініціювати
   синхронізацію.
2. Валідація зв'язку: Немає перевірки, що `$externalCalendarEvent` насправді
   належить до `$calendarEvent`.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Actions/Calendar/ExternalCalendar/RerunExternalCalendarEventSync.php`
> around lines 19 - 30, Add an authorization check and validate model
> relationships before dispatching jobs: in handle() call the relevant
> Policy/Gate (e.g., authorize or Gate::allows) to ensure current user can sync
> CalendarEvent and ExternalCalendarEvent, verify that
> ExternalCalendarEvent->calendar_event_id (or related foreign key) equals
> CalendarEvent->id and that UserCalendarIntegration returned by
> UserCalendarIntegration::query() belongs to ExternalCalendarEvent->user_id
> (and matches $externalCalendarEvent->provider), and abort/throw an
> AuthorizationException or return a RedirectResponse with an error if any check
> fails; only then dispatch CreateExternalCalendarEvent or
> UpdateExternalCalendarEvent.

---

## 43. File: `app/Actions/Pages/Profile/GetMentorProfilePage.php`

- **Line:** 30
- **Type:** `potential_issue`
- **Comment:** Неефективне та надлишкове завантаження зв'язку. `load('mentor')`
  викликється на колекції перед `first()`, що завантажує зв'язок для всіх
  відфільтрованих записів. Крім того, ментор вже доступний у змінній `$mentor`.

### 🛠️ Proposed Fix

```php
-        $mainProgram = $mentor->mentorPrograms->where('is_main', true)->load('mentor')->first();
+        $mainProgram = $mentor->mentorPrograms->where('is_main', true)->first();
```

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Actions/Pages/Profile/GetMentorProfilePage.php` at line 30, The code
> is inefficiently calling load('mentor') on the mentorPrograms collection
> before first(), causing the mentor relation to be loaded for all filtered
> items and redundantly reloading the mentor already available in $mentor;
> change the assignment for $mainProgram to fetch only the first main program
> (e.g. use $mentor->mentorPrograms->where('is_main', true)->first() or
> $mentor->mentorPrograms->firstWhere('is_main', true)) and remove the
> load('mentor') call so you don't reload the existing $mentor relation.

---

## 44. File: `app/Actions/Calendar/CalendarEvent/StoreCalendarEvent.php`

- **Lines:** 23 to 48
- **Type:** `potential_issue`
- **Comment:** Відсутня транзакція бази даних. Операції створення
  `CalendarEvent`, прикріплення користувачів та виклик
  `CreateMentorSessionForCalendarEvent` не обгорнуті в транзакцію.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Actions/Calendar/CalendarEvent/StoreCalendarEvent.php` around lines
> 23 - 48, Wrap the calendar event creation, the two
> calendarEventUsers()->attach calls, and the
> CreateMentorSessionForCalendarEvent::run($calendarEvent) invocation in a single database transaction to ensure atomicity; use the DB transaction helper (e.g., DB::transaction or DB::beginTransaction()/commit()/rollBack()) around the block that contains CalendarEvent::query()->create([...]), both calendarEventUsers()->attach(...) calls, and the CreateMentorSessionForCalendarEvent::run($calendarEvent)
> call, and ensure failures roll back the transaction and rethrow or surface the
> exception.

---

## 46. File: `app/Jobs/UpdateExternalCalendarEvent.php`

- **Lines:** 58 to 74
- **Type:** `potential_issue`
- **Comment:** Обгорніть операції у `failed()` в try-catch. Якщо `update()` або
  `create()` викликнуть виняток у методі `failed()`, обробка помилки сама зазнає
  невдачі.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Jobs/UpdateExternalCalendarEvent.php` around lines 58 - 74, Wrap the
> body of the failed(Throwable $throwable) method in a try-catch that catches
> Throwable to ensure update() and
> ExternalCalendarEventLog::query()->create(...) cannot throw out of failed();
> inside the try keep the existing Log::error(...) call, the
> $this->externalEvent->update(['sync_status' =>
> ExternalCalendarEventSyncStatusEnum::Error]) call, and the
> ExternalCalendarEventLog::query()->create([...]) call, and in the catch log
> the secondary exception (e.g. via Log::error) along with context
> (external_event_id / integration key) so both the original $throwable and any
> new exception are recorded without rethrowing.

---

## 47. File: `app/Http/Requests/Calendar/ExternalCalendarSyncSingleEventRequest.php`

- **Lines:** 37 to 42
- **Type:** `potential_issue`
- **Comment:** Потенційний NPE: `$this->user()` може повернути null. Метод
  `$this->user()` повертає `Authenticatable|null`. Якщо маршрут не захищений
  middleware автентифікації, виклик `$user->getKey()` призведе до помилку.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Http/Requests/Calendar/ExternalCalendarSyncSingleEventRequest.php`
> around lines 37 - 42, The request handler assumes $this->user() always returns
> an authenticated user and calls $user->getKey() when checking
> CalendarEvent::calendarEventUsers(); add a null-check for $this->user() in the
> authorization block (and any other places in this class that call getKey(),
> e.g., where calendarEventUsers() is checked) and treat a null user as
> unauthorized: if $this->user() is null, add the same validation error via
> $validator->errors()->add('event', 'You are not authorized for this calendar
> event.') or otherwise abort the request, so getKey() is never called on null
> (referencing $this->user(), CalendarEvent, calendarEventUsers(), getKey(), and
> $validator->errors()->add).

---

## 48. File: `app/Actions/Calendar/ExternalCalendar/ExternalCalendarConnectCallback.php`

- **Lines:** 39 to 45
- **Type:** `potential_issue`
- **Comment:** Логування виключень без stack trace ускладнює дебаг. На лінії 42
  логується лише повідомлення, але не повний контекст виключення.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In
> `@app/Actions/Calendar/ExternalCalendar/ExternalCalendarConnectCallback.php`
> around lines 39 - 45, The catch block in ExternalCalendarConnectCallback
> (catching Throwable
> $throwable around $this->synchronizationService->handleCallback) only logs the message, not the stack trace; update the Log::error call to include full exception context by passing the Throwable as context or appending $throwable->getTraceAsString() (e.g., Log::error('Calendar authorization failed: '.$throwable->getMessage(),
> ['exception' => $throwable])) so the stack trace and exception details are
> captured, then return the existing handleError($state, ...) as before.

---

## 49. File: `app/Actions/Calendar/CalendarEvent/CreateMentorSessionForCalendarEvent.php`

- **Lines:** 41 to 50
- **Type:** `potential_issue`
- **Comment:** Відсутня транзакція для атомарності операцій. Створення
  `MentorSession` і оновлення `CalendarEvent` мають бути в транзакції.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In
> `@app/Actions/Calendar/CalendarEvent/CreateMentorSessionForCalendarEvent.php`
> around lines 41 - 50, Wrap the MentorSession creation and CalendarEvent update
> in a database transaction so both operations are atomic: move the
> MentorSession::query()->create([...]) call and the lines setting
> $event->mentor_session_id and $event->saveQuietly() inside a DB::transaction
> (or use DB::beginTransaction()/commit()/rollBack()) so any failure (including
> saveQuietly() failing) rolls back the created MentorSession; ensure exceptions
> are propagated or checked so the transaction can rollback on error.

---

## 50. File: `app/Services/ExternalCalendar/AppleCalDavExternalCalendarService.php`

- **Lines:** 109 to 110
- **Type:** `potential_issue`
- **Comment:** Помилка часового поясу: формат Z означає UTC, але
  використовується `config('app.timezone')`. Суфікс `\Z` в форматі дати означає
  UTC (Zulu time). Конвертація в `config('app.timezone')` створює некоректний
  діапазон часу.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Services/ExternalCalendar/AppleCalDavExternalCalendarService.php`
> around lines 109 - 110, The current formatting uses the literal Z (UTC) suffix
> while converting the Date to config('app.timezone'), producing incorrect
> timestamps; update the Date::instance(...)->timezone(...)->format(...) calls
> that produce $fromStr and $toStr so they either convert the Date to UTC before
> using the '\Z' suffix (e.g., ->timezone('UTC')->format('Ymd\\THis\\Z')) or
> remove the '\Z' and emit a local timestamp with a timezone offset; apply the
> same change to both $fromStr and $toStr and ensure you only pick one
> consistent approach.

---

## 51. File: `app/Services/XmlTools/ExternalCalendar/CalDavPropfindParser.php`

- **Lines:** 27 to 49
- **Type:** `potential_issue`
- **Comment:** Стан не скидається між викликами — баг при повторному
  використанні екземпляра. Якщо `extractValue()` викликнути двічі на тому ж
  екземплярі, `$this->found`, `$this->inTarget`, `$this->inChild` та
  `$this->value` зберігають значення з попереднього виклику.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Services/XmlTools/ExternalCalendar/CalDavPropfindParser.php` around
> lines 27 - 49, The parser retains state between calls causing stale results;
> add a protected resetState() method that sets $this->inTarget = false,
> $this->inChild = false, $this->value = null and $this->found = false, then
> call $this->resetState() at the start of extractValue() (before creating the
> XMLReader) so extractValue(), processNode() and any loop always operate on a
> fresh parser state.

---

## 53. File: `app/Actions/Calendar/CalendarEvent/CreateMentorSessionForCalendarEvent.php`

- **Line:** 45
- **Type:** `potential_issue`
- **Comment:** Потенційний null pointer на `mentorProgram`. Перевірка
  `$event->mentor_program_id` не гарантує існування пов'язаного запису. Якщо
  `MentorProgram` був видалений, виклик `$event->mentorProgram->cost` спричинить
  помилку.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In
> `@app/Actions/Calendar/CalendarEvent/CreateMentorSessionForCalendarEvent.php`
> at line 45, The mapping currently accesses $event->mentorProgram->cost
> directly which can NPE if the related MentorProgram was deleted; update
> CreateMentorSessionForCalendarEvent to first resolve the relation (e.g.
> $event->mentorProgram or eager-loaded relation) and guard it: if the relation
> is null, handle it explicitly (throw a clear exception, return/abort with a
> validation error, or supply a safe default) instead of dereferencing; replace
> the direct access at the 'cost' => $event->mentorProgram->cost site with a
> null-checked value (or fail-fast behavior) and ensure any callers expect the
> chosen failure path.

---

## 56. File: `config/calendar.php`

- **Lines:** 33 to 34
- **Type:** `potential_issue`
- **Comment:** Потенційна проблема: відсутні обов'язкові OAuth credentials.
  Microsoft credentials будуть null якщо не встановлені у `.env`.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@config/calendar.php` around lines 33 - 34, The config entries
> 'microsoft_client_id' and 'microsoft_client_secret' can be null if not set in
> .env; add an explicit validation and fail-fast so missing OAuth credentials
> don't cause runtime auth errors: in config/calendar.php or in your Calendar
> service provider's boot() (e.g., AppServiceProvider::boot or
> CalendarServiceProvider::boot) check config('calendar.microsoft_client_id')
> and config('calendar.microsoft_client_secret') and throw a clear exception or
> log/error and exit when they are empty, or mark them optional only if a
> feature flag allows it; ensure the validation references the exact keys
> 'microsoft_client_id' and 'microsoft_client_secret' so it's easy to locate and
> maintain.

---

## 60. File: `routes/web.php`

- **Lines:** 141 to 143
- **Type:** `potential_issue`
- **Comment:** Неконсистентний ключ зв'язування параметра маршруту. Маршрут
  використовує `{calendarEvent}` без `:id`, тоді як інші маршрути використовують
  `{calendarEvent:id}`.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@routes/web.php` around lines 141 - 143, The route definition for
> ExternalCalendarSyncSingleEvent uses an inconsistent route parameter
> "{calendarEvent}" instead of the model-bound "{calendarEvent:id}", which may
> break lookups when the model's route key differs; update the Route::post
> declaration that maps to ExternalCalendarSyncSingleEvent to use
> "{calendarEvent:id}" (matching the other routes) so Laravel resolves the
> correct model instance via implicit route model binding.

---

## 65. File: `resources/js/Pages/Calendar/ShowEditCalendarEvent.vue`

- **Lines:** 162 to 167
- **Type:** `potential_issue`
- **Comment:** Відсутня обробка помилок при `router.reload()`. Якщо запит
  завершиться з помилкою, `externalIntegrations` залишиться null, і користувач
  бачитиме skeleton назавжди.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@resources/js/Pages/Calendar/ShowEditCalendarEvent.vue` around lines 162 -
> 167, Wrap the router.reload call in loadIntegrationsTab inside a try/catch:
> call router.reload({ only: ['externalIntegrations'] }) in the try block and in
> the catch restore integrationsLoaded.value = false, set externalIntegrations
> (the ref used to render the tab) to a safe fallback (e.g., [] or null) so the
> skeleton is removed, and trigger the app's existing user-facing error
> notification (e.g., notifyError or useToast) with the caught error; ensure you
> reference loadIntegrationsTab, integrationsLoaded, router.reload and
> externalIntegrations when making the change.

---

## 69. File: `app/DTO/Calendar/CalendarEventData.php`

- **Lines:** 37 to 41
- **Type:** `potential_issue`
- **Comment:** `Date::createFromFormat()` може повернути `false` при помилці
  парсингу. Якщо формат дати/часу не відповідає очікуваному, метод поверне
  `false`, і виклик `->timezone('UTC')` викликне `TypeError`.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/DTO/Calendar/CalendarEventData.php` around lines 37 - 41,
> Date::createFromFormat(...) can return false on parse failure, so calling
> ->timezone('UTC') directly on its result causes a TypeError; in the
> CalendarEventData construction (where
> $startDateTime and $endDateTime are created) check the return value of Date::createFromFormat for false for both the start and end parses (using the same format and $validated['fromDate'].' '.$validated['fromTime']
> / $validated['toDate'].' '.$validated['toTime']), and if false throw/return a
> clear validation/argument exception (or use your existing validation error
> flow) instead of proceeding — only call ->timezone('UTC') on a successful
> CarbonImmutable instance.

---

## 72. File: `app/DTO/Calendar/CalendarEventData.php`

- **Line:** 35
- **Type:** `potential_issue`
- **Comment:** Потенційний null access на `profile->timezone`. Якщо у
  користувача немає профілю або timezone не встановлено, це викликне помилку.

### 🛡️ Proposed Fix

```php
-        $timezone = $request->user()->profile->timezone;
+        $timezone = $request->user()->profile?->timezone ?? config('app.timezone', 'UTC');
```

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/DTO/Calendar/CalendarEventData.php` at line 35, The line assigning
> $timezone from $request->user()->profile->timezone can cause a null access if
> user or profile or timezone is missing; update the CalendarEventData code to
> safely fetch the timezone (using null checks or null-safe operator on
> $request->user() and ->profile and ->timezone) and provide a sensible fallback
> (e.g., config('app.timezone') or 'UTC') before using $timezone so you never
> dereference a null profile or timezone value.

---

## 74. File: `app/DTO/Calendar/CalendarUIEventData.php`

- **Line:** 34
- **Type:** `potential_issue`
- **Comment:** Потенційна проблема з типом: `auth()->user()` повертає
  `Authenticatable|null`. `auth()->user()` може повернути будь-який
  `Authenticatable`, а не обов'язково `User`.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/DTO/Calendar/CalendarUIEventData.php` at line 34, The expression
> "$user ??= auth()->user()" can assign an Authenticatable|null which may not be
> an instance of your app's User class; update CalendarUIEventData to guard the
> assignment by checking the type of auth()->user() and only assign when it's an
> instance of User (or explicitly assert that $user is null or instanceof User),
> e.g. perform an instanceof User check or use an assertion after assignment to
> ensure $user is either null or a User; reference the $user variable and the
> auth()->user() call in CalendarUIEventData and add the type check/assertion to
> prevent unexpected types.

---

## 75. File: `config/calendar.php`

- **Lines:** 21 to 22
- **Type:** `potential_issue`
- **Comment:** Критична вразливість: відсутня валідація ключів шифрування. Якщо
  змінні середовища не встановлені, `env()` поверне null, що призведе до помилок
  шифрування/дешифрування.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@config/calendar.php` around lines 21 - 22, Файл config/calendar.php
> повертає null для 'encryption_key1' та 'encryption_key2' quando env() не
> встановлені, що викликє помилки/вразливості при шифруванні OAuth-токенів;
> виправте це валідацією під час завантаження конфига: перевірте значення
> env('CALENDAR_ENCRYPTION_KEY1') та env('CALENDAR_ENCRYPTION_KEY_PREVIOUS') у
> конфігураційному масиві (символи: 'encryption_key1', 'encryption_key2',
> функція env()) і якщо будь-яке дорівнює null — кидайте зрозуміле виняток або
> задайте безпечне значення за замовчуванням, або реалізуйте конфігурну
> валідацію при boot (наприклад в провайдері), щоб запобігти запуску програми
> без валідних ключів.

---

## 76. File: `app/Services/ExternalCalendar/GoogleAppExternalCalendarService.php`

- **Lines:** 54 to 58
- **Type:** `potential_issue`
- **Comment:** Потенційний `TypeError` через невідповідність типів. Метод
  оголошує повернення `string`, але `config('calendar.google_client_secret')`
  може повернути `null`.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Services/ExternalCalendar/GoogleAppExternalCalendarService.php`
> around lines 54 - 58, The method clientSecret currently declares a string
> return but calls config('calendar.google_client_secret') which may be null
> under strict_types; update clientSecret to guarantee a string return (or
> change the signature to ?string if the parent allows it). Locate the
> clientSecret method in GoogleAppExternalCalendarService and either (a) return
> a non-null value by providing a default/fallback or throwing a clear exception
> when config is missing, or (b) change the return type to ?string and propagate
> that nullable type through callers; ensure you reference
> config('calendar.google_client_secret') and the
> clientSecret(UserCalendarIntegration $integration = null) signature when
> making the change.

---

## 77. File: `app/DTO/ExternalCalendar/ExternalCalendarEventData.php`

- **Lines:** 15 to 36
- **Type:** `refactor_suggestion`
- **Comment:** Розгляньте додавання валідації часових меж у конструкторі.
  Відсутня перевірка того, що `$startUtc` передує `$endUtc`.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/DTO/ExternalCalendar/ExternalCalendarEventData.php` around lines 15 -
> 36, The constructor of ExternalCalendarEventData must validate that
> $startUtc is strictly before $endUtc because the class is readonly and values cannot be corrected later; inside the __construct of ExternalCalendarEventData check the CarbonImmutable timestamps (e.g. using $startUtc->lt($endUtc)
> or comparison operators) and throw an InvalidArgumentException (or a
> domain-specific exception) if $startUtc is not before $endUtc, including a
> descriptive error message mentioning the problematic values.

---

## 78. File: `app/Services/ExternalCalendar/OutlookExternalCalendarService.php`

- **Lines:** 347 to 354
- **Type:** `potential_issue`
- **Comment:** Новий `refresh_token` не зберігається під час оновлення токена.
  Microsoft OAuth може повернути новий `refresh_token` при оновленні.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Services/ExternalCalendar/OutlookExternalCalendarService.php` around
> lines 347 - 354, The current token refresh block reads $data =
> $response->json() and updates only access_token and token_expires_at via
> integration->update([...]), but it omits saving a new refresh_token returned
> by Microsoft; modify the update so that if $data['refresh_token'] exists you
> include 'refresh_token' => $data['refresh_token'] in the array passed to
> integration->update (e.g., add a conditional entry or merge it into the update
> payload) to persist the new refresh token and avoid using an invalidated old
> one.

---

## 79. File: `app/Actions/Calendar/CalendarEvent/ConfirmCalendarEvent.php`

- **Lines:** 81 to 95
- **Type:** `potential_issue`
- **Comment:** Скасовані події не повідомляють користувачів. Коли
  `checkEventsForCancellation` скасовує перекриваючі події, menti-користувачі
  цих подій не отримують сповіщення.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Actions/Calendar/CalendarEvent/ConfirmCalendarEvent.php` around lines
> 81 - 95, checkEventsForCancellation currently updates overlapping events to
> CANCELLED without notifying the mentee; modify it to first load the affected
> CalendarEvent models (using the same
> $overlappingIds from the mentor->calendarEvents query), then perform the status update and for each affected CalendarEvent dispatch the existing cancellation notification flow (e.g. call $event->mentee->notify(new CalendarEventCancelled($event))
> or dispatch a job/event that your app already uses for calendar
> notifications); ensure you reference CalendarEvent, CalendarEventStatusEnum
> and the checkEventsForCancellation method so the change updates status and
> sends the cancellation notification to the mentee for each cancelled event.

---

## 81. File: `resources/js/Pages/Calendar/CreateCalendarEvent.vue`

- **Line:** 97
- **Type:** `potential_issue`
- **Comment:** `session_type` може не оновитися, якщо `mentorProgram`
  завантажується асинхронно. `useForm` захоплює значення один раз при
  ініціалізації.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@resources/js/Pages/Calendar/CreateCalendarEvent.vue` at line 97, The form
> field session_type is initialized from availableSessionTypes.value[0]?.value
> at useForm creation and can remain empty if
> mentorProgram/availableSessionTypes loads asynchronously; update session_type
> after data arrives (e.g., in the same watch on props.open or in onMounted
> where you set form.mentor_program_id) so it mirrors availableSessionTypes when
> available. Specifically, add logic in the existing watch for props.open (or
> onMounted) to set form.session_type = availableSessionTypes.value[0]?.value ??
> '' (or choose the appropriate default) and ensure this mirrors the pattern
> used to initialize form.mentor_program_id so session_type updates when the
> deferred data becomes available.

---

## 82. File: `app/Actions/Calendar/ExternalCalendar/ExternalCalendarRetrySync.php`

- **Lines:** 42 to 47
- **Type:** `potential_issue`
- **Comment:** Потенційна неконсистентність стану при помилці. Статус інтеграції
  оновлюється до `Active` перед диспатчем джобів.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Actions/Calendar/ExternalCalendar/ExternalCalendarRetrySync.php`
> around lines 42 - 47, The integration status is set to Active before
> dispatching jobs which can leave the record inconsistent if
> buildSyncJobs($user, $calendarProvider) fails; to fix, either wrap the update
> and job dispatch in a DB transaction or move the $integration->update([...
> 'sync_status' => CalendarSyncStatusEnum::Active, ...]) call to occur only
> after $this->buildSyncJobs(...) completes successfully, ensuring the update is
> executed only when jobs are queued; use the existing buildSyncJobs method and
> the $integration model in your chosen approach and handle any exceptions to
> keep state consistent.

---

## 83. File: `app/Services/ExternalCalendar/OutlookExternalCalendarService.php`

- **Lines:** 120 to 122
- **Type:** `potential_issue`
- **Comment:** Відсутній виклик `refreshTokenIfExpired()` перед API-запитом.
  Метод `fetchCalendars` не викликє `refreshTokenIfExpired()`.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Services/ExternalCalendar/OutlookExternalCalendarService.php` around
> lines 120 - 122, The fetchCalendars method in OutlookExternalCalendarService
> is missing a call to refreshTokenIfExpired() before making the API request, so
> a stale token can cause a 401; update fetchCalendars to call
> $this->refreshTokenIfExpired($integration) (same pattern used in fetchEvents,
> createEvent, updateEvent, deleteEvent) immediately before performing the
> Http::withToken(...) get(self::CALENDAR_LIST_URL) request so the access token
> is refreshed when expired.

---

## 84. File: `app/Actions/Calendar/CalendarEvent/ConfirmCalendarEvent.php`

- **Lines:** 28 to 56
- **Type:** `potential_issue`
- **Comment:** Потенційний race condition (TOCTOU) при підтвердженні. Між
  перевіркою на перекриття та фактичним підтвердженням інший запит може
  підтвердити конфліктуючу подію.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Actions/Calendar/CalendarEvent/ConfirmCalendarEvent.php` around lines
> 28 - 56, Wrap the overlap-check and subsequent confirmation/update steps in a
> DB transaction and acquire a pessimistic lock when selecting the mentor's
> calendar events to prevent TOCTOU races: run the existence check query using
> lockForUpdate (or select ... for update) on the mentor's calendarEvents() (the
> same query currently at the start of ConfirmCalendarEvent) and perform
> fillConfirmationDates(), the calendarEvent->calendarEventUsers() check,
> calendarEvent->update(['status' => ...]), notifyUserAboutConfirmation(), and
> checkEventsForCancellation() inside that same transaction so the overlap check
> and the status update are atomic.

---

## 85. File: `app/Observers/CalendarEventObserver.php`

- **Lines:** 34 to 43
- **Type:** `potential_issue`
- **Comment:** Синхронний виклик `CreateMentorSessionForCalendarEvent::run()`
  блокує подальші dispatch-и при помилці. Якщо action кине виняток на рядку 35,
  job-и для зовнішніх календарів не будуть відправлені.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Observers/CalendarEventObserver.php` around lines 34 - 43, The
> synchronous call to CreateMentorSessionForCalendarEvent::run($event) can throw
> and prevent subsequent dispatches for external calendars; either wrap that
> call in a try-catch that logs/handles the exception so execution continues to
> the dispatch(...) calls (ProcessCalendarEventExternalCalendarIntegrations and
> ProcessDeleteExternalCalendarEvent), or move/convert the mentor-session
> creation to an asynchronous job (dispatch a
> CreateMentorSessionForCalendarEvent job) so it cannot block the dispatch of
> external-calendar jobs; update the block that checks
> $event->wasChanged('status') to implement one of these fixes and ensure
> failures in CreateMentorSessionForCalendarEvent do not stop the external
> dispatches.

---

## 87. File: `app/Services/ExternalCalendar/OutlookExternalCalendarService.php`

- **Lines:** 78 to 96
- **Type:** `potential_issue`
- **Comment:** Відсутня перевірка успішності відповіді OAuth. Якщо обмін коду на
  токен завершиться помилкою, код продовжить оновлення інтеграції з null
  токенами.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Services/ExternalCalendar/OutlookExternalCalendarService.php` around
> lines 78 - 96, The OAuth token exchange handling in
> OutlookExternalCalendarService currently updates the Integration with whatever
> $response->json() returns even on errors; modify the token-exchange block to
> first verify the HTTP response succeeded (e.g. $response->successful() or
> $response->ok()) and that required keys like 'access_token' exist in $data,
> and if not, abort the update by logging/throwing an exception or marking
> sync_status as Failed instead of writing null tokens; only call
> $integration->update([...]) and return $integration->refresh() when the
> response is valid, otherwise handle the error path (leave tokens unchanged and
> set a failure status or raise) to avoid corrupting integration state.

---

## 88. File: `resources/js/Pages/MentorProgram/CreateOrEdit.vue`

- **Lines:** 60 to 69
- **Type:** `potential_issue`
- **Comment:** Невідповідність часових поясів у парсингу дати/часу.
  `toDateInput` конвертує в UTC, але `toTimeInput` використовує локальний час.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@resources/js/Pages/MentorProgram/CreateOrEdit.vue` around lines 60 - 69,
> toDateInput and toTimeInput are inconsistent: toDateInput uses UTC via
> toISOString() while toTimeInput uses local time via toTimeString(), causing
> timezone desync; make both functions use the same timezone parsing (preferably
> UTC) — update toTimeInput to parse the Date and return the UTC time portion
> with the same approach as toDateInput (e.g., use new
> Date(datetimeStr).toISOString().slice(11,16)) and keep toDateInput as is (or
> alternatively change both to use local getters like
> getFullYear/getMonth/getDate and getHours/getMinutes) so date and time remain
> synchronized; ensure the default fallback ('08:00') remains and that both
> functions handle empty/null input consistently.

---

## 90. File: `resources/js/Pages/Calendar/MentorProgramEventBookingPage.vue`

- **Lines:** 159 to 171
- **Type:** `potential_issue`
- **Comment:** Потенційний runtime error при доступі до `mentorProgram`. Проп
  `mentorProgram` має `default: null`, але його властивості використовуються без
  перевірки.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@resources/js/Pages/Calendar/MentorProgramEventBookingPage.vue` around
> lines 159 - 171, The template uses mentorProgram.name,
> mentorProgram.description and mentorProgram.session_duration without guarding
> against mentorProgram being null (the prop defaults to null), so add a render
> guard: wrap the parent container (the div containing the h1/p elements) with
> v-if="mentorProgram" or change those bindings to use optional chaining (e.g.,
> mentorProgram?.name) so accesses to mentorProgram, programPeriod and
> session_duration are safe when mentorProgram is null.

---

## 91. File: `app/Services/XmlTools/ExternalCalendar/CalDavReportParser.php`

- **Lines:** 143 to 150
- **Type:** `potential_issue`
- **Comment:** Відсутнє розгортання рядків (line unfolding) за RFC 5545.
  ICS-формат дозволяє переносити довгі рядки за допомогою CRLF + пробіл/таб.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@app/Services/XmlTools/ExternalCalendar/CalDavReportParser.php` around
> lines 143 - 150, Long ICS lines folded with CRLF + space/tab aren't being
> unfolded, so extractIcsValue and other parsers can miss multi-line
> SUMMARY/DESCRIPTION; to fix, perform RFC5545 "line unfolding" at the start of
> parseIcsEvent() by replacing occurrences of CRLF (or LF) followed by space or
> tab with an empty string so the ICS content is normalized before calling
> extractIcsValue(), ensuring multi-line property values are treated as
> single-line values during regex extraction.

---

## 92. File: `database/migrations/2026_04_04_110000_create_external_calendar_events_table.php`

- **Lines:** 15 to 16
- **Type:** `potential_issue`
- **Comment:** Перевірте залежності міграцій і каскадне видалення `user_id`.

1. Порядок міграцій: Міграція передбачає, що таблиці вже існують.
2. Каскадне видалення: `cascadeOnDelete()` на `user_id` знищить всі записи при
   видаленні користувача.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In
> `@database/migrations/2026_04_04_110000_create_external_calendar_events_table.php`
> around lines 15 - 16, Міграція зараз створює foreign keys calendar_event_id і
> user_id з constrained()->cascadeOnDelete(), що потребує існування таблиць
> calendar_events і users перед виконанням і призведе до видалення всіх записів
> при видаленні користувача; виправте це так: переконайтеся, що міграція
> виконується після створення таблиць calendar_events і users (перенесіть або
> змініть порядок міграцій) та змініть поведінку для user_id з cascadeOnDelete()
> на більш підходящу (наприклад, nullOnDelete() з nullable('user_id') або
> restrict/remove cascade) залежно від потреб збереження історії; оновіть
> визначення полів calendar_event_id і user_id у тій же міграції (посилання на
> foreignId('calendar_event_id') і foreignId('user_id')) відповідно.

---

## 93. File: `resources/js/Pages/Profile/Tab/ExternalCalendarTab.vue`

- **Line:** 105
- **Type:** `potential_issue`
- **Comment:** SSR: `window` недоступний на сервері. `window.location.origin`
  виконується під час ініціалізації модуля.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@resources/js/Pages/Profile/Tab/ExternalCalendarTab.vue` at line 105, The
> module-level use of window in ExternalCalendarTab.vue (the const callbackUrl =
> ${window.location.origin}/settings/external-calendar/callback/google; line)
> causes SSR failures; replace it by accepting a backend-provided URL prop
> (e.g., googleCallbackUrl) populated via the named route
> (route('external-calendar.callback', ['provider'=>'google'])) or compute the
> value only on the client (use a computed/getter that checks for process.client
> or typeof window !== 'undefined') and reference that instead of the
> module-level callbackUrl constant so no window access happens during
> server-side initialization.

---

## 94. File: `resources/js/Pages/Profile/Tab/ExternalCalendarTab.vue`

- **Lines:** 250 to 257
- **Type:** `potential_issue`
- **Comment:** Потенційний доступ до undefined. Зовнішня умова
  `v-if="googleProvider || googlePersonalProvider"` не гарантує, що
  `googleProvider` існує.

### Prompt for AI Agent

> Verify each finding against the current code and only fix it if needed.
>
> In `@resources/js/Pages/Profile/Tab/ExternalCalendarTab.vue` around lines
> 250 - 257, The button's click handler calls authorize(googleProvider.key) but
> the surrounding condition can leave googleProvider undefined; update the
> template and click to guard against undefined by rendering the button only
> when !usePersonalGoogle && googleProvider exists (v-if="!usePersonalGoogle &&
> googleProvider") and/or pass the correct provider key using a safe expression
> to authorize, e.g. authorize((usePersonalGoogle ? googlePersonalProvider :
> googleProvider)?.key), so authorize is never invoked with undefined; adjust
> references to usePersonalGoogle, googleProvider, and googlePersonalProvider
> accordingly.

---

_Review completed: 97 findings ✔_
