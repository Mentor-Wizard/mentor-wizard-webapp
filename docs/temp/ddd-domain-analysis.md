# Каталог бізнес-доменів Mentor Wizard — межі та готовність до виносу в модулі

> **Дата зрізу:** 2026-08-05, гілка `feature/ddd-migration-laravel-modules`.
> **Тип:** довідник меж. Кожне твердження перевірене по коду в цьому проході.
> **Статус коду:** документ **не змінює жодного файлу** в `app/`, `Modules/`,
> `routes/`, `database/`.

> ⚠ **Застаріло щодо A1/A2 після дати зрізу.** Наступні коміти на цій же гілці
> (`019b97b` migrate Calendar → `Modules/Calendar`, `1e4ef1f` modularize Inertia
> pages/resources у `Modules/`) закрили гепи `G-1` (Chat без `resources/`) і
> винесли Calendar у `Modules/Calendar` разом із фронтендом. Розділи A1/A2 нижче
> лишені **як історичний запис аналізу**, а не як поточний стан — актуальний
> стан: `Modules/Chat` і `Modules/Calendar` обидва мають
> `resources/js/{Pages,Components}` усередині модуля (правило «модуль = бекенд +
> Inertia-фронтенд домену», `docs/MODULAR_ARCHITECTURE.md`,
> `docs/plans/migrate-calendar-domain-module/06-frontend-pilot.md`,
> `07-chat-frontend-pilot.md`).

---

## 0. Як читати цей документ

**Питання, на яке він відповідає:** «який домен я зараз чіпаю, де його межа, і
чи можна його вже виносити в `Modules/`».

**Чим він НЕ є.** Це не план міграції. Послідовність фаз, реєстр ризиків
(`R-1…R-17`), ризики рівня даних (`DR-1…DR-5`) і критерії приймання
(`AC-1…AC-19`) вже зафіксовані в
`docs/plans/ddd-migration-laravel-modules/01-business-analysis.md`. Тут вони
згадуються **лише за ідентифікатором**, без переказу. Якщо потрібен контекст ID
— читайте той документ.

**Три документи ініціативи:**

| Документ                                                | Про що                                                            |
| ------------------------------------------------------- | ----------------------------------------------------------------- |
| `ddd-migration-laravel-modules/01-business-analysis.md` | вимоги до **міграції**: ризики, AC, послідовність фаз (канонічно) |
| `ddd-domain-analysis/01-business-analysis.md`           | **інвентар** областей і статус міграції станом на 2026-08-05      |
| **цей документ**                                        | **межі доменів** і готовність кожного до виносу                   |

⚠ **Застереження про розташування (F-5).** `docs/temp/` **не входить** до
`.gitignore` (на відміну від `/docs/plans`, рядок 69). Тобто цей файл потрапить
у git попри слово «temp» у шляху. Шлях заданий постановкою задачі й не
змінювався самовільно. Якщо документ має жити довго — його варто перенести в
`docs/` з нормальною назвою; якщо ні — видалити після ухвалення рішень із
розділу 10.

**Легенда вердиктів** — визначення в розділі 4.0. Emoji вживаються **лише** як
вердикти.

---

## 1. Карта доменів — одна сторінка

**14 бізнес-областей** = 10 bounded contexts + 4 supporting capabilities. Окремо
(поза цими 14) — 2 не-доменні артефакти.

### Рівень A — bounded contexts (кандидати в `Modules/*`)

| #       | Домен                 | Стан         | Вердикт            | Борг інверсії       | Головний блокер                                 |
| ------- | --------------------- | ------------ | ------------------ | ------------------- | ----------------------------------------------- |
| **A1**  | **Chat**              | ✅ винесений | _поза шкалою_      | 1 (матеріалізовано) | — (див. 4.1)                                    |
| **A2**  | **Calendar**          | `app/`       | 🔴 not-recommended | +2                  | двонаправлений із A3 + Observer пише в A9       |
| **A3**  | **ExternalCalendar**  | `app/`       | 🔴 not-recommended | +1                  | двонаправлений із A2                            |
| **A4**  | **Identity & Access** | `app/`       | 🟡 needs-work      | 0                   | `UserObserver` пише в A7 (`R-5`)                |
| **A5**  | **MentorProgram**     | `app/`       | 🟡 needs-work      | +2                  | 3 **мертві** інверсні аксесори (розділ 3)       |
| **A6**  | **Marketplace**       | `app/`       | 🟡 needs-work      | +2                  | жива залежність від A5 + `User::rating` (`A-4`) |
| **A7**  | **UserProfile**       | `app/`       | 🟡 needs-work      | +1                  | `timezone` читає A2                             |
| **A8**  | **UserSchedule**      | `app/`       | 🟢 **ready**       | +2                  | —                                               |
| **A9**  | **MentorSession**     | `app/`       | 🔴 not-recommended | +1                  | створюється побічним ефектом A2                 |
| **A10** | **Payments**          | `app/`       | 🟢 ready\*         | 0                   | \*виносити нема чого (розділ 4.10)              |

### Рівень B — supporting capabilities (→ Core/Shared, **не** модулі)

| #      | Спроможність            | Чому не bounded context                                                  |
| ------ | ----------------------- | ------------------------------------------------------------------------ |
| **B1** | **Notifications**       | механізм доставки; єдиний конкретний `Notification`-клас належить A2     |
| **B2** | **Media**               | інфраструктура spatie; обслуговує `User` + `UserProfile` + `ChatMessage` |
| **B3** | **Presence / realtime** | канал `presence-online-users`; нуль бізнес-правил                        |
| **B4** | **Admin (Filament)**    | презентація **над** доменами; discovery вже модуле-обізнаний             |

### Не-домени (поза 14)

| Артефакт                                                                          | Природа                                       |
| --------------------------------------------------------------------------------- | --------------------------------------------- |
| `app/Actions/Pages` (**16** файлів; було 17 — `GetChatPage` переїхав)             | наскрізний presentation-шар під розчленування |
| Core / Shared Kernel (`User`, `Currency`, `RoleEnum`, `RoleGuardEnum`, морф-мапа) | спільне ядро; розділ 2                        |

**Готово до виносу вже зараз: рівно один домен — A8 UserSchedule.** Ще два (A5,
A6) переходять у 🟢 після дешевих правок із розділу 3.

---

## 2. Спільне ядро та проблема `User`

Цей розділ стоїть перед картками доменів, бо визначає читання всіх вердиктів.

### 2.1 Core вже залежить від модуля — три ребра, не одне

| #   | Файл                                         | Що саме                                                                 |
| --- | -------------------------------------------- | ----------------------------------------------------------------------- |
| 1   | `app/Models/User.php:27`                     | `use Modules\Chat\Models\Chat;` + `chats(): BelongsToMany` (`:160-165`) |
| 2   | `app/Providers/AppServiceProvider.php:33-34` | `use Modules\Chat\Models\{Chat, ChatMessage};` — морф-мапа (`:94-99`)   |
| 3   | `database/seeders/DatabaseSeeder.php:8`      | `use Modules\Chat\Database\Seeders\ChatDatabaseSeeder;`                 |

Додатково — тестові (`tests/Feature/MorphMap/*`), вони мають меншу вагу.

Це матеріалізований `R-2`: обрано де-факто **варіант A** («товстий `User` у
Core»), тоді як рекомендацією попереднього документа (`4.1`) був **варіант D**.
Розбіжність між рекомендацією і реалізацією не задокументована жодним ADR.

### 2.2 Асиметрія arch-правил — порушення структурно невидиме

`tests/Unit/ArchTest.php:56-58` забороняє **Chat → інші модулі**. Дзеркального
правила **`App → Modules` не існує**. Тобто всі три ребра з 2.1 не просто
дозволені — вони **невидимі** для CI, і кожен наступний модуль додасть ребра,
яких теж ніхто не полічить.

### 2.3 Борг інверсії — два різні числа

Плутати їх не можна:

- **N — relation-методів** у `User`: обсяг переписування.
- **M — класів домену**, які Core почне імпортувати: саме це побачить майбутнє
  правило `expect('App')->not->toUse('Modules')`.

`hostedCalendarEvents()` і `participatingCalendarEvents()` делегують у
`calendarEvents()` і **не додають** імпорту — звідси розбіжність.

| Домен               | N (методів)   | M (класів) | Які класи                                               |
| ------------------- | ------------- | ---------- | ------------------------------------------------------- |
| A2 Calendar         | 3             | **2**      | `CalendarEvent`, `CalendarEventRoleEnum` (`User.php:7`) |
| A6 Marketplace      | 3 (+`rating`) | **2**      | `MentorProfile`, `MentorReview`                         |
| A5 MentorProgram    | 2             | **2**      | `MentorProgram`, `MentorProgramBlockProgress`           |
| A8 UserSchedule     | 2             | **2**      | `UserSchedule`, `UserScheduleRecordType` (`User.php:9`) |
| A9 MentorSession    | 2             | **1**      | `MentorSession`                                         |
| A3 ExternalCalendar | 1             | **1**      | `UserCalendarIntegration`                               |
| A7 UserProfile      | 1             | **1**      | `UserProfile`                                           |
| A1 Chat             | 1             | **1**      | `Chat` — **уже матеріалізовано**                        |
| **Разом**           | **15**        | **12**     |                                                         |

Тобто «варіант A» до кінця = **12 Core→Module імпортів у `User`**, не 15.

⚠ **Нюанс, що ламає варіант B.** 2 з 12 — це **енуми** (`CalendarEventRoleEnum`,
`UserScheduleRecordType`), а не моделі. Вони вживаються в тілі
`wherePivot()`/`where()`, а не в сигнатурі зв'язку, тому **проходять повз**
мітигацію на `resolveRelationUsing()`. Цього аргументу немає в `4.1`
попереднього документа, і він означає: чистий варіант B не закриє задачу,
потрібен щонайменше гібрид.

### 2.4 «Товстий» `User` відмиває міжмодульні залежності

Найнеприємніший наслідок варіанта A — не кількість ребер, а те, що `User` працює
**транзитним вузлом, який приховує залежності від arch-тестів**. Живий приклад:

```
Modules/Chat/app/Actions/ChatListUser.php:121
    $companion->mentorProfile->mentorTags
    └── User (Core) ──▶ MentorProfile ──▶ MentorTag   (обидва — домен A6 Marketplace)
```

Модуль Chat **фактично залежить від Marketplace**. Правило
`expect('Modules\Chat')->not->toUse('Modules\Marketplace')` (`ArchTest.php:58`)
цього **не спіймає** ніколи, бо ланцюг іде через `App\Models\User`.

**Наслідок:** доки `User` тримає relation-методи всіх доменів, arch-тести на
межі модулів дають хибне відчуття захисту. Це самостійний аргумент за варіант
C/D — сильніший, ніж підрахунок ребер.

### 2.5 Що входить у Core

| Кандидат                                    | Обґрунтування                                                                                                     |
| ------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| `User` + auth-контракт                      | див. 2.1–2.4                                                                                                      |
| `Currency` (+`CurrencyEnum`)                | довідник; FK з `mentor_programs`, `mentor_profiles`, `user_profiles`; 9 файлів-посилань                           |
| `RoleEnum`, `RoleGuardEnum`                 | авторизація в усіх доменах                                                                                        |
| Морф-мапа (`AppServiceProvider:94-99`)      | спільний ресурс, росте лінійно з кожним модулем                                                                   |
| Глобальна конфігурація `AppServiceProvider` | `shouldBeStrict`, `CarbonImmutable`, `forceHttps`, rate limiters — **рівно в одному провайдері** (Octane, `R-12`) |
| `HandleInertiaRequests`, `Layouts`          | спільні props SPA                                                                                                 |

**Явно не Core:** `MentorProfile` (A6), `UserProfile` (A7), `Payment` (A10).

---

## 3. Модельний шар — сліпа зона попередніх аналізів

> Це головна нова знахідка каталогу. Вона змінює три вердикти.

### 3.1 Чому шар був невидимий

Обидва попередні документи будували граф залежностей через `grep 'use App\…'` по
**Actions / Services / Jobs / Policies / Http** — моделі в перелік джерел не
входили. А всі моделі живуть в одному просторі імен `App\Models`, тому посилання
моделі на модель **не потребує `use`-імпорту** і в такий grep не потрапляє.

Наслідок: `5` попереднього документа стверджує «`MentorProgram → Calendar`:
**ні**». Фактично `app/Models/MentorProgram.php` оголошує
`calendarEvents(): HasMany` на `CalendarEvent`. Твердження хибне — але не через
недбалість, а через методологію.

### 3.2 Повний граф моделей (міждоменні ребра, без Core)

`User` і `Currency` виключені як Core.

| Ребро                                      | Домени    | Механізм                | Живе?                                    |
| ------------------------------------------ | --------- | ----------------------- | ---------------------------------------- |
| `CalendarEvent → MentorProgram`            | A2 → A5   | `belongsTo`             | ✅ (`ExternalCalendarEventLogPolicy:18`) |
| `CalendarEvent → MentorSession`            | A2 → A9   | `belongsTo`             | ❌ **мертве**                            |
| `CalendarEvent` cast `session_type`        | A2 → A9   | `MentorSessionTypeEnum` | ✅                                       |
| `MentorProgram → CalendarEvent`            | A5 → A2   | `hasMany`               | ❌ **мертве**                            |
| `MentorProgram → MentorSession`            | A5 → A9   | `hasMany`               | ❌ **мертве**                            |
| `MentorProgram → MentorProfile`            | A5 → A6   | `belongsToMany`         | ❌ **мертве**                            |
| `MentorProfile → MentorProgram`            | A6 → A5   | `belongsToMany`         | ✅ (`ProgramCostFilter:32`)              |
| `MentorSession → CalendarEvent`            | A9 → A2   | `hasOne`                | ❌ **мертве**                            |
| `MentorSession → MentorProgram`            | A9 → A5   | `belongsTo`             | ❌ **мертве**                            |
| `MentorSession → Payment`                  | A9 → A10  | `hasOne`                | ❌ **мертве**                            |
| `Payment → MentorSession`                  | A10 → A9  | `belongsTo`             | ❌ **мертве**                            |
| `MentorTag → MentorProfile`                | A6 внутр. | `belongsToMany`         | ❌ мертве                                |
| `ExternalCalendarEvent → CalendarEvent`    | A3 → A2   | `belongsTo`             | ✅                                       |
| `ExternalCalendarEventLog → CalendarEvent` | A3 → A2   | `belongsTo`             | ✅                                       |

**П'ять двонаправлених пар на рівні моделей:** A2⇄A5, A2⇄A9, A5⇄A9, A5⇄A6,
A9⇄A10.

### 3.3 Але майже вся двонаправленість — мертвий код

Перевірено по `app/`, `Modules/`, `tests/`, `resources/js` — трьома незалежними
патернами: `->метод`, рядкове ім'я в `with()`/`load()`/`whereHas()`, **і рядкове
ім'я з вибором колонок** (`'relation:id,name'`). Третій патерн критичний: без
нього `participants` хибно класифікується як мертвий (див. примітку під
таблицею).

| #   | Мертвий аксесор                      | Ребро, яке він створює |
| --- | ------------------------------------ | ---------------------- |
| 1   | `MentorProgram::calendarEvents()`    | A5 → A2                |
| 2   | `MentorProgram::mentorSession()`     | A5 → A9                |
| 3   | `MentorProgram::mentorProfiles()`    | A5 → A6                |
| 4   | `MentorSession::calendarEvent()`     | A9 → A2                |
| 5   | `MentorSession::mentorProgram()`     | A9 → A5                |
| 6   | `MentorSession::payment()`           | A9 → A10               |
| 7   | `Payment::mentorSession()`           | A10 → A9               |
| 8   | `CalendarEvent::mentorSession()`     | A2 → A9                |
| 9   | `MentorTag::mentorProfiles()`        | внутрішнє A6           |
| 10  | `MentorSession::mentorSessionNote()` | внутрішнє A9           |

> `CalendarEvent::participants()` до цього списку **не входить** — він
> **живий**: `->with([… 'participants:id,username'])` у
> `PendingCalendarEventsListPage.php:25` і
> `ConfirmedCalendarEventsListPage.php:26`, споживається у Vue
> (`ListPendingCalendarEventsPage.vue:181`). Внутрішній для A2, на межі не
> впливає.

**Висновок, важливіший за сам список:** видалення 8 міждоменних мертвих
аксесорів (#1–#8) **руйнує всі 5 двонаправлених пар** модельного шару. Лишаються
тільки односпрямовані ребра: `A2 → A5`, `A6 → A5`, `A3 → A2`.

Це та сама категорія, що `R-8` (`User::coachChats()`) — дешева правка з великим
ефектом на межі. **Рекомендація: окремий PR «прибрати мертві інверсні аксесори»
перед кроком 3**, за зразком уже виконаного `R-8`.

⚠ **Застереження.** «Мертвий» тут = немає жодного виклику в репозиторії. Аксесор
міг призначатися для майбутньої фічі. Перед видаленням — підтвердження власника,
як і для `R-8`. На відміну від `coachChats`, ці аксесори **не зламані** (колонки
й FK існують), тож залишення їх — не баг, а лише зчеплення.

---

## 4. Картки доменів

### 4.0 Шкала вердиктів

| Вердикт                | Умови (всі)                                                                                                                                                              |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| 🟢 **ready**           | немає **поведінкової** двонаправленості з іншим доменом; немає неявних міждоменних записів (Observer/Job/Event у чужу модель); немає міжконтекстних FK, окрім як на Core |
| 🟡 **needs-work**      | рівно один **названий** блокер із **названим** засобом усунення; після зняття → 🟢                                                                                       |
| 🔴 **not-recommended** | **поведінкова** двонаправленість (обидві сторони викликають логіку одна одної) **або** сутність створюється побічним ефектом чужого коду                                 |

**Структурна ≠ поведінкова двонаправленість.** Мертвий інверсний аксесор
(розділ 3) — структурна: усувається видаленням рядка. Взаємні виклики сервісів
(A2 ⇄ A3) — поведінкова: потребує domain events і ACL. Тому A5 отримує 🟡, а A2
— 🔴.

---

### A1. Chat — еталон, уже винесений

**Межа.** Діалог двох користувачів: повідомлення, вкладення, статуси (бан, мут,
архів), лічильник непрочитаних, realtime-доставка.

**Всередині межі:** `Modules/Chat/` — **51 файл**: 10 Actions +
`Actions/Pages/GetChatPage`, моделі `Chat`/`ChatMessage`, 2 Policies, 2 Events,
`Broadcasting/ChatChannel`, `ChatStatusEnum`, 1 Request, 3 Resources,
`routes/web.php`, `ChatServiceProvider` + `RouteServiceProvider`, 4 міграції, 2
фабрики, 2 сідери, 13 тестів, `module.json`, `composer.json`,
`config/config.php`.

**Поза межею:** Vue-сторінки — `resources/js/Pages/Chat/` (6 файлів) **лишились
глобальними**; `OnlineUsersChannel` (B3) — не чатовий.

**Залежності:** `→ Core` (`User`) — єдина оголошена. **Але фактично
`→ A6 Marketplace`** через `$companion->mentorProfile->mentorTags` (розділ 2.4)
— не покривається arch-тестом.

**Вердикт:** поза шкалою (винесений). Прогалини: `G-1` (немає `resources/`),
`G-2`/`G-3` (міграції, зокрема дублікат `create_chats_table`), `G-4` (реєстрація
каналу розщеплена між `routes/channels.php:11-12` і `ChatServiceProvider:52`).

**Уроки для наступних модулів — розділ 8.1.**

---

### A2. Calendar — ядро продукту

**Межа.** Подія календаря від створення до підтвердження/скасування: розрахунок
вільних слотів, бронювання, перевірка перетинів, представлення day/week/month.

**Всередині межі:** модель `CalendarEvent` + півот `calendar_event_user`;
`app/Actions/Calendar/CalendarEvent/*` (6); `app/Services/Calendar/*` (10);
`app/DTO/Calendar/*` (5); `app/Traits/Calendar/*` (4);
`app/Casts/UtcDateTime.php`; `CalendarEventPolicy`; `CalendarEventObserver`;
енуми `CalendarEventStatusEnum`, `CalendarEventTypeEnum`,
`CalendarEventRoleEnum`, `CalendarEventColoursEnum`, `CalendarViewModeEnum`,
`CalendarEventMinimumBookingTimeInMinutes`;
`app/Http/Requests/Calendar/CalendarEvent/*` (4); `app/Actions/Pages/Calendar/*`
(5); `resources/js/Pages/Calendar/` (6) + `Components/Calendar/` (4) +
`Stores/calendar.js`.

**Поза межею (типові плутанини):**

- `app/Actions/Calendar/ExternalCalendar/*` — попри шлях, це **A3**.
- `CreateMentorSessionForCalendarEvent` лежить у теці Calendar, але створює
  сутність **A9**.
- `ExcludeUserScheduleSchemeService` — сервіс Calendar, що **читає** A8;
  належить A2.

**Залежності:**

| Напрям | Домен               | Механізм                                                      | Файл                                           |
| ------ | ------------------- | ------------------------------------------------------------- | ---------------------------------------------- |
| →      | A5 MentorProgram    | `belongsTo` + читання `session_duration`, `need_confirmation` | `CalendarEvent.php`, `Services/Calendar/*` (5) |
| →      | A9 MentorSession    | **синхронний запис** з Observer                               | `CalendarEventObserver.php:36`                 |
| →      | A9 MentorSession    | cast `session_type`                                           | `CalendarEvent.php`                            |
| →      | A3 ExternalCalendar | dispatch 3 типів джобів                                       | `CalendarEventObserver.php:29,39,43,50`        |
| ←      | A3 ExternalCalendar | `belongsTo` з 2 моделей + сервіси                             | `ExternalCalendarEvent.php`                    |
| →      | A8 UserSchedule     | читання розкладу                                              | `ExcludeUserScheduleSchemeService`             |
| →      | A7 UserProfile      | читання `timezone`, `minimum_pre_booking_time`                | `Services/Calendar/*`                          |

**Борг інверсії:** +2 (`CalendarEvent`, `CalendarEventRoleEnum`).

**Вердикт: 🔴 not-recommended.**

- _Cohesion:_ висока — найбільша й найзв'язніша область продукту.
- _Coupling:_ поведінково двонаправлений з A3; **пише** в A9 синхронно.
- _Blast radius:_ `CalendarEventObserver` — фактично готовий набір domain
  events, замаскований під Observer (`R-4`). Без його заміни на
  `CalendarEventConfirmed/Cancelled/Rescheduled` A2 не відокремлюється ні від
  A3, ні від A9.
- Виносити **тільки** після кроку «domain events» (`R-3`, `R-4`).

---

### A3. ExternalCalendar — інтеграції

**Межа.** Двостороння синхронізація подій із зовнішніми календарями (Google,
Outlook, Apple CalDAV): OAuth-підключення, вибір календаря, синхронізація, логи,
повторні спроби.

**Всередині межі:** моделі `UserCalendarIntegration`, `ExternalCalendarEvent`,
`ExternalCalendarEventLog`; `app/Actions/Calendar/ExternalCalendar/*` (8) +
`ExternalCalendarLog/*` (1); `app/Services/ExternalCalendar/*` (9, з 2
контрактами й фабрикою); `app/Services/XmlTools/ExternalCalendar/*` (5);
`Services/Encryption/ CalendarCredentialEncrypter`;
`app/Casts/EncryptedCalendarCredential.php`; **усі 7 `app/Jobs/*`**;
`app/DTO/ExternalCalendar/*` (2); `app/Traits/ExternalCalendar/*`; 3 Policies;
енуми `CalendarProviderEnum`, `CalendarSyncStatusEnum`,
`ExternalCalendarEventSyncStatusEnum`, `ExternalCalendarEventLogTypeEnum`; rate
limiters `calendar-connect`/`calendar-retry`/`calendar-sync`
(`AppServiceProvider:73-79`); `Pages/Settings/ExternalCalendarPage.vue`,
`Pages/Profile/Tab/ExternalCalendarTab.vue`,
`Composables/useExternalCalendar.js`.

**Поза межею:** `SyncCalendarEventToIntegration` лежить у теці
`Calendar/CalendarEvent/`, але за змістом — A3 (це і є зворотне ребро).

**Залежності:** `→ A2` (імпортує `CalendarEvent` напряму в сервісах, джобах,
політиках; FK `calendar_event_id` з `cascadeOnDelete` × 2), `← A2` (Observer
диспатчить джоби). **Двонаправлено, поведінково.**

**Борг інверсії:** +1 (`UserCalendarIntegration`).

**Вердикт: 🔴 not-recommended.**

- _Cohesion:_ дуже висока — власна термінологія, таблиці, політики, зовнішній
  контракт, окремі rate limiters. Це **безумовно окремий bounded context**, а не
  субдомен A2 (відповідь на `Q1` попереднього документа; питання формально
  лишається відкритим).
- _Coupling:_ поведінкова двонаправленість із A2 — потребує ACL.
- _Blast radius:_ усі 7 джобів тут → зміна їхніх FQCN зачіпає payload'и в
  польоті (`R-7`). Плюс `A-7` (енум `CalendarProviderEnum` імпортує 4 сервіси —
  інверсія всередині домену) і `A-8` (крихкі `withoutScopedBindings()` на 2
  маршрутах).

---

### A4. Identity & Access

**Межа.** Хто такий користувач і чи має він доступ: реєстрація, вхід/вихід,
скидання й зміна пароля, підтвердження email, OAuth через Socialite, призначення
ролей.

**Всередині межі:** `app/Actions/Auth/**` (17); `app/Http/Requests/Auth/**` (7);
`app/Observers/UserObserver.php`; енуми `RoleEnum`, `RoleGuardEnum`,
`SocialiteDriverEnum`; `routes/auth.php`; `resources/js/Pages/Auth/` (6); пакет
`spatie/laravel-permission`.

**Поза межею:**

- модель `User` — **Core**, не A4 (розділ 2).
- `Pages/Profile/Partials/Form/UpdatePasswordForm.vue` — зміна пароля належить
  A4, хоч лежить у теці Profile.
- `RoleEnum`/`RoleGuardEnum` — фізично в Core, бо потрібні всім доменам.

**Залежності:**

| Напрям | Домен          | Механізм                             | Файл                     |
| ------ | -------------- | ------------------------------------ | ------------------------ |
| →      | A7 UserProfile | **синхронний запис** при `created()` | `UserObserver.php:16-18` |
| →      | Core           | `User`, `RoleEnum`                   | —                        |

**Борг інверсії:** 0 — A4 не має власних моделей (працює над Core-`User`).

**Вердикт: 🟡 needs-work.**

- _Блокер:_ `UserObserver::created()` створює `UserProfile` і призначає роль
  (`R-5`).
- _Засіб:_ domain event `UserRegistered` + слухач у A7 — **або** свідоме рішення
  тримати `UserProfile` у Core.
- _Особливість:_ A4 — єдиний домен із нульовим боргом інверсії, але саме він
  **упирається в рішення по `User`** (розділ 2), тому в послідовності стоїть
  безпосередньо перед Core.

---

### A5. MentorProgram

**Межа.** Пропозиція ментора: назва, опис, вартість, тривалість сесії, типи
сесій, вікно доступності (`start_time`/`end_time`), потреба підтвердження, блоки
програми та прогрес менті.

**Всередині межі:** моделі `MentorProgram`, `MentorProgramBlock`,
`MentorProgramBlockProgress`; `app/Actions/MentorPrograms/*` (5);
`app/Actions/Pages/MentorProgram/*` (3); `MentorProgramObserver` (slug +
`is_main`); `MentorProgramPolicy`; `app/Http/Requests/MentorProgram/*` (2);
`MentorProgramsResource`; `resources/js/Pages/MentorProgram/` (2); маршрути
`mentor-program.*` (7, middleware `role:mentor`).

**Поза межею:** `AvailableSlotOptionsForMentorProgram` і
`MentorProgramEventBookingPage` — попри назви, це **A2** (розрахунок слотів).

**Залежності:**

| Напрям | Домен            | Механізм                         | Живе?                          |
| ------ | ---------------- | -------------------------------- | ------------------------------ |
| ←      | A2 Calendar      | `belongsTo` + читання параметрів | ✅                             |
| ←      | A6 Marketplace   | `belongsToMany` (півот)          | ✅                             |
| ←      | A9 MentorSession | `belongsTo`                      | ❌ мертве                      |
| →      | A2, A9, A6       | 3 інверсні аксесори              | ❌ **усі мертві** (розділ 3.3) |
| →      | Core             | `User`, `Currency`               | ✅                             |

**Борг інверсії:** +2 (`MentorProgram`, `MentorProgramBlockProgress`).

**Вердикт: 🟡 needs-work.**

- _Cohesion:_ висока; Observer пише **лише у власну модель** — безпечний.
- _Coupling:_ після видалення 3 мертвих аксесорів (розділ 3.3) — **чистий
  downstream-постачальник** для A2 і A6. Класичний Customer/Supplier.
- _Блокер:_ 3 мертві інверсні аксесори. _Засіб:_ видалити (PR за зразком `R-8`).
- Після цього → 🟢. **Найдешевший перехід у ready серед усіх доменів.**

---

### A6. Marketplace (публічний профіль ментора)

**Межа.** Вітрина: публічний профіль ментора, теги (стек/мова), відгуки й
рейтинг, пошук і фільтрація менторів, підбір схожих.

**Всередині межі:** моделі `MentorProfile`, `MentorTag`, `MentorReview` + півоти
`mentor_profile_mentor_tag`, `mentor_profile_mentor_program`; `app/Filters/*`
(6) + `app/Traits/ParsesNumericRange.php`;
`app/Actions/MentorTag/CreateMentorTag.php`; `TagEnum`;
`app/Actions/Pages/Profile/{GetMentorProfilePage, ListMentorProfilePage, GetMentorReviewPage}`;
ресурси `MentorProfilePageResource`, `SimilarMentorResource`,
`MentorReviewResource`; `resources/js/Pages/Profile/Mentor/**` (10),
`Pages/Profile/MentorListPage.vue`, `Components/UI/Table/MentorsList.vue`;
маршрути `page.profile-programs`, `page.mentor`, `page.mentor-review`.

**Поза межею:**

- `Pages/Profile/Tab/**` і `Partials/Form/**` — це **A7** (налаштування
  акаунта), не вітрина.
- `UserProfile` — A7. Розрізнення: `MentorProfile` = **публічна** вітрина,
  `UserProfile` = **приватні** налаштування.

**Рішення про склад.** `MentorTag` і `MentorReview` — **не окремі домени**,
попри окрему теку `app/Actions/MentorTag/`: тег має 1 Action і 1 модель, його
єдиний споживач — `MentorProfile`; відгук існує лише як джерело рейтингу
профілю. Спільна мова «профіль ↔ тег ↔ відгук ↔ рейтинг» неподільна.

**Залежності:** `→ A5` (`MentorProfile::mentorPrograms()`, **живе** —
`ProgramCostFilter:32`, `TagStacksFilter:27`, `TagLanguagesFilter:27` через
`whereHas`); `→ Core` (`User`, `Currency`); `← A1 Chat` (розділ 2.4, приховане).

**Борг інверсії:** +2 (`MentorProfile`, `MentorReview`) **+ атрибут**
`User::rating` (`A-4`).

**Вердикт: 🟡 needs-work.**

- _Блокер:_ `User::rating` (`User.php:245-250`) — доменна логіка Marketplace
  всередині Core-сутності; `RatingFilter:28` фільтрує через `whereHas('user')`,
  тобто фільтр домену ходить у Core і назад.
- _Засіб:_ перенести обчислення рейтингу в A6 (на `MentorProfile`) разом із
  рішенням по `R-2`.
- Жива залежність `→ A5` блокером **не є**: вона односпрямована
  (Customer/Supplier).

---

### A7. UserProfile (налаштування акаунта)

**Межа.** Приватні дані акаунта: ім'я, контакти
(linkedin/telegram/whatsapp/phone), аватар, ставка `cost_per_hour` + валюта,
**`timezone`**, **`minimum_pre_booking_time`**, прапорець `is_mute`; видалення
акаунта (GDPR).

**Всередині межі:** модель `UserProfile` (`implements HasMedia`, колекція
`avatar`, конверсія `preview`);
`app/Actions/Profile/{UpdateUserProfile, DeleteUserProfile}`;
`app/Actions/User/{UpdateUser, AddAvatar}`;
`app/Http/Requests/{User,UserProfile}/*` (3); `UserProfileResource`;
`app/Actions/Pages/Profile/GetProfilePage.php`;
`resources/js/Pages/Profile/{Edit,EditPage}.vue`, `Partials/**` (5), `Tab/**`
(6).

**Поза межею:** `Tab/ExternalCalendarTab.vue` → A3; `Tab/BillingTab.vue` → A10;
`Tab/NotificationTab.vue` → B1; `Tab/MentorScheduleTab.vue` → A8;
`Partials/Form/UpdatePasswordForm.vue` → A4. **Тека `Pages/Profile/` (24 Vue) —
найменш чиста в проєкті (`G-5`); при виносі потребує розчленування на 5
доменів.**

**Залежності:** `← A2 Calendar` — читає `timezone` і `minimum_pre_booking_time`
при розрахунку слотів; `→ Core` (`User`, `Currency`); `→ B2 Media`.

**Борг інверсії:** +1 (`UserProfile`).

**Вердикт: 🟡 needs-work.**

- _Блокер:_ `timezone` + `minimum_pre_booking_time` — це **параметри
  бронювання**, а не «профіль». Домен, що зветься UserProfile, володіє
  конфігурацією домену A2.
- _Засіб:_ або визнати A7 частиною Core (тоді `R-5` знімається автоматично), або
  винести ці два поля в A2 як `BookingPreferences`. Рішення впливає на `R-5` і
  на вердикт A4.
- ⚠ `DeleteUserProfile` виконує GDPR-каскад **через FK БД, а не через код**
  (`R-17`): при розділенні на модулі каскад лишиться робочим, але стане неявним.

---

### A8. UserSchedule (робочий розклад) — 🟢 ready

**Межа.** Коли ментор доступний: схема робочих годин по днях тижня, разові
вихідні (`day_off_date`), перевірка перетину інтервалів при збереженні.

**Всередині межі:** модель `UserSchedule`;
`app/Actions/UserSchedule/StoreBatchUserSchedule.php`;
`app/Actions/Pages/UserSchedule/UserSchedulePage.php`;
`app/Services/UserSchedule/CheckUserScheduleOverlap.php`; `UserSchedulePolicy`;
`app/Http/Requests/UserSchedule/*`;
`app/Http/Resources/UserSchedule/UserScheduleViewResource.php`;
`UserScheduleRecordType`; `resources/js/Pages/UserSchedule/ListPage.vue`;
маршрути `user-schedule.index`, `user-schedule.batch`.

**Поза межею:** `ExcludeUserScheduleSchemeService` — **A2**: це споживач
розкладу, а не його частина; `Pages/Profile/Tab/MentorScheduleTab.vue` — точка
входу з UI A7.

**Залежності:**

| Напрям | Домен       | Механізм                                 |
| ------ | ----------- | ---------------------------------------- |
| ←      | A2 Calendar | читання розкладу при розрахунку слотів   |
| →      | Core        | `User` (FK `user_id`, `cascadeOnDelete`) |

Вихідних міждоменних ребер — **нуль**. Модель посилається лише на `User`.

**Борг інверсії:** +2 (`UserSchedule`, `UserScheduleRecordType` — енум, див.
застереження 2.3).

**Вердикт: 🟢 ready.**

- _Cohesion:_ повна — один агрегат, один Action, один Service, одна політика.
- _Coupling:_ чистий upstream для A2; зворотних посилань немає.
- _Blast radius:_ мінімальний — 2 маршрути, 1 Vue-сторінка, 0 джобів, 0 подій, 0
  морф-типів.
- **Найкращий кандидат на модуль №2.** Домен перевіряє те, чого не перевірив
  пілот Chat: винос **енуму**, на який посилається Core-`User` (`User.php:9`) —
  тобто вперше поставить питання 2.3 практично.

---

### A9. MentorSession (факт наданої послуги)

**Межа.** Запис про проведену сесію: хто, кому, коли, за якою програмою,
вартість, статуси (`is_success`, `is_paid`, `is_cancelled`, `is_date_changed`),
нотатка до сесії.

**Всередині межі:** моделі `MentorSession`, `MentorSessionNote`; енуми
`MentorSessionTypeEnum`, `MentorSessionDurationOptionsEnum`;
`app/Actions/Calendar/CalendarEvent/CreateMentorSessionForCalendarEvent.php`
(лежить у теці A2).

**Поза межею:** нічого власного — у домену **0 маршрутів, 0 сторінок, 0
політик**.

**Залежності:** `← A2` (**створюється** Observer'ом A2); `← A10`
(`payments.mentor_session_id`, `cascadeOnDelete`); `↔ A5` (FK
`mentor_program_id`, `cascadeOnDelete`); `← A2` (FK
`calendar_events.mentor_session_id`, `cascadeOnDelete`).

**Борг інверсії:** +1 (`MentorSession`).

**Вердикт: 🔴 not-recommended.**

- _Cohesion:_ формально висока, фактично — **домен без поведінки**: 17 файлів у
  `app/` посилаються на `MentorSession`, але жоден ним не керує.
- _Coupling:_ сутність **створюється побічним ефектом**
  `CalendarEventObserver::updated()` — це прямо кваліфікує 🔴 за шкалою 4.0.
- _Blast radius:_ вузол стику **чотирьох** доменів (A2, A5, A10 + Core) із
  трьома `cascadeOnDelete`.
- ⚠ **Не відкидати як тривіальний.** Це «записаний факт наданої послуги», до
  якого чіпляється монетизація. Малий обсяг ≠ мала важливість. Виносити разом із
  A2 на кроці domain events.

---

### A10. Payments — 🟢 ready\*, але виносити нема чого

**Межа.** Транзакція оплати сесії: `order_reference`, сума, валюта, статус,
платіжна система, тип картки, банк-емітент. Сліди інтеграції WayForPay.

**Всередині межі:** модель `Payment`; таблиця `payments`;
`Pages/Profile/Tab/BillingTab.vue` (UI-заглушка).

**Залежності:** `→ A9` — **єдина**, і та мертва (`Payment::mentorSession()`,
розділ 3.3). Вхідних — одна, теж мертва (`MentorSession::payment()`).

**Борг інверсії:** 0 — `User` не має жодного relation-методу в бік Payments.

**Вердикт: 🟢 ready\* — із зірочкою.** За шкалою 4.0 домен **формально чистий**:
нуль вихідних імпортів, нуль неявних записів, нуль не-Core FK назовні. Але це
чистота порожньої кімнати: **0 маршрутів, 0 Actions, 0 політик, 0 тестів, 1
файл-модель**. Правильна теза — не «не готовий», а **«готовий і безвартісний»**:
виносити нема чого.

⚠ `A-2`: `payments.currency` — `varchar(3)`, тоді як усі інші таблиці вживають
`currency_id → currencies`. Це може бути **легітимним DDD-рішенням** (валюта
фіксується на момент транзакції і не повинна змінюватись разом із довідником),
але рішення ніде не зафіксоване.

**Рекомендація:** межа-заглушка або відкласти до появи реальної платіжної
логіки. Не робити модулем «щоб було».

---

## 5. Supporting capabilities — чому не bounded contexts

### B1. Notifications

`app/Actions/Notifications/*` (3: `ListNotifications`, `MarkNotificationAsRead`,
`MarkAllNotificationsAsRead`),
`app/Notifications/CalendarEventConfirmedNotification.php`,
`NotificationResource`, поліморфна таблиця `notifications`, 3 маршрути,
`Components/UI/Notifications/NotificationBell.vue`,
`Pages/Profile/Tab/NotificationTab.vue`.

**Чому не домен:** це **механізм доставки** без власних бізнес-правил. Єдиний
конкретний `Notification`-клас належить A2. Модуль «Notifications» довелося б
розібрати назад на кроці 7.

**Куди:** механізм + 3 Actions → Core; конкретні `Notification`-класи →
домени-власники подій. ⚠ `receivesBroadcastNotificationsOn()`
(`User.php:232-235`) закріплює ім'я каналу `App.Models.User.{id}` — це закритий
`DR-4`; **не чіпати** при будь-якому переміщенні `User`.

### B2. Media

`app/Support/MediaLibrary/PathGenerator.php` (зареєстрований у
`config/media-library.php:85`). Обслуговує `User`, `UserProfile` (колекція
`avatar`, конверсія `preview`) і `ChatMessage` (вкладення). **Інфраструктура
spatie**, спільна для доменів → Core. Морф-аліаси `user`, `user_profile`,
`chat_message` уже зареєстровані (`DR-2` закритий).

### B3. Presence / realtime

`app/Broadcasting/OnlineUsersChannel.php` + `routes/channels.php`. Технічна
спроможність «хто зараз онлайн», нуль бізнес-правил → Core. ⚠ `G-4`: після
пілота реєстрація каналів розщеплена між `routes/channels.php` і провайдерами
модулів — узгоджено **коментарем** (`channels.php:11-12`), а не механізмом.

### B4. Admin (Filament)

`app/Filament/Resources/User/**` (6 файлів), панель `supervisor`, 3 маршрути,
єдиний `UserResource`. **Презентація над доменами**, не домен. 🟢 **Discovery
вже модуле-обізнаний:** `AdminPanelProvider.php:82` будує неймспейс
`Modules\{Studly}\Filament` у циклі по `Module::all()` — тобто `R-14`/`Q5`
закриті інфраструктурно; лишається лише додати ресурси, коли вони з'являться.

---

## 6. Не-домени

### 6.1 `app/Actions/Pages` — наскрізний presentation-шар

**16 файлів** (було 17 — `Pages/Chat/GetChatPage` переїхав у модуль). Імпортує
одночасно `CalendarEvent`, `Currency`, `ExternalCalendarEvent`,
`ExternalCalendarEventLog`, `MentorProfile`, `MentorProgram`, `User`,
`UserCalendarIntegration`, `UserProfile` + 5 сервісів Calendar.

Це не контекст, а групування **за типом артефакту**. У модульній структурі
аналога не має: кожен `*Page` переїжджає у свій домен (`Pages/Calendar/*` → A2,
`Pages/Profile/*` → A6/A7/A3, `Pages/MentorProgram/*` → A5,
`Pages/UserSchedule/*` → A8; `DashboardPage`, `WelcomePage` → Core).

⚠ **Конфлікт з arch-тестом.** `ArchTest.php:30-33` містить правила на літеральні
простори імен `App\Actions\Pages` і `App\Actions\Pages\Profile`. Після
розчленування простір перестане існувати → правило стане **тихо порожнім** і
перестане щось перевіряти (`R-6`, `AC-10`). Переписувати **в тій самій фазі**.

### 6.2 Core / Shared Kernel

Склад — розділ 2.5. Ключове обмеження: глобальна конфігурація має лишитись
**рівно в одному провайдері** (`R-12`, Octane); морф-мапа — централізована точка
з лінійним ростом: кожен новий модуль **зобов'язаний** дописати аліас, інакше
`enforceMorphMap` кине `ClassMorphViolationException` (це навмисна fail-loud
поведінка, `AppServiceProvider:84-92`).

---

## 7. Фронтенд — межі й чинний стандарт

| Тека                                                                    | Файлів | Домен                                                                                                       |
| ----------------------------------------------------------------------- | ------ | ----------------------------------------------------------------------------------------------------------- |
| `Pages/Profile/**`                                                      | 24     | ⚠ **змішана**: A6 + A7 + A3 + A10 + B1 + A4                                                                 |
| `Pages/Calendar/`                                                       | 6      | A2                                                                                                          |
| `Pages/Chat/`                                                           | 6      | A1 — ⚠ **не переїхав** у модуль                                                                             |
| `Pages/Auth/`                                                           | 6      | A4                                                                                                          |
| `Pages/MentorProgram/`                                                  | 2      | A5                                                                                                          |
| `Pages/UserSchedule/`                                                   | 1      | A8                                                                                                          |
| `Pages/Settings/ExternalCalendarPage.vue`                               | 1      | A3                                                                                                          |
| `Pages/{Dashboard,Welcome}Page.vue`                                     | 2      | Core                                                                                                        |
| `Components/UI/`                                                        | 26     | глобальні — ⚠ 2 доменні протікання: `Table/MentorsList.vue` (A6), `Notifications/NotificationBell.vue` (B1) |
| `Components/Calendar/`, `Stores/calendar.js`, `Stores/Calendar/`        | 6      | A2                                                                                                          |
| `Layouts/`, `Navigation/`, `Stores/{footer,navigation}.js`, `UseCases/` | 11     | глобальні                                                                                                   |

**Чинний стандарт де-факто — «бекенд-only модуль».** Це не недогляд: увесь
**бекенд**-тулінг оновлено під `Modules/` (розділ 9), а **фронтенд**-тулінг —
свідомо ні: `vite.config.js:14` (`input`), `resources/js/app.js:18` і
`ssr.js:19` (`import.meta.glob('./Pages/**/*.vue')`) лишились прив'язаними до
`resources/js/Pages`.

⚠ `docs/FRONTEND_ARCHITECTURE.md` описує `resources/js/Pages/**` як єдину
канонічну структуру і **про `Modules/` не згадує взагалі** (`G-1`). Тобто пілот
дотримався чинної конвенції мовчки. Потрібно або підтвердити її явно, або
оновити документ — **до** модуля №2, інакше кожен наступний домен вирішуватиме
це заново (`R-C`).

---

## 8. Рекомендована послідовність виносу

### 8.1 Уроки пілота Chat

| #       | Урок                                             | Доказ                                                               | Наслідок                                                                                      |
| ------- | ------------------------------------------------ | ------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| **L-1** | Бекенд-тулінг закривається **один раз для всіх** | розділ 9                                                            | для модулів 2…10 це **не робота** — лише `+1` рядок у матриці шардів `ci.yml`                 |
| **L-2** | Фронтенд свідомо лишився поза модулем            | `vite.config.js:14`, `app.js:18`, `ssr.js:19`                       | потрібне явне рішення до модуля №2 (`G-1`)                                                    |
| **L-3** | Міграції переїхали **повністю**, всупереч `R-11` | `Modules/Chat/database/migrations/` — 4 файли, у корені копій немає | потрібне однозначне правило: міжмодульні FK залежать від порядку таймстемпів (`G-2`, `AC-17`) |
| **L-4** | Реєстрація broadcast-каналів **розщепилась**     | `channels.php:11-12` + `ChatServiceProvider:52`                     | узгоджено коментарем, не механізмом (`G-4`)                                                   |
| **L-5** | Модулю потрібен **явний** guard на не-порожність | `ArchTest.php:47-49`                                                | `arch()` на порожньому неймспейсі лишається зеленим — без guard'а `AC-19` фіктивний           |

**Головна асиметрія:** дешева частина міграції вже зроблена один раз для всіх
(L-1); дорога (фронтенд, міграції, канали) **не має правила** й
відтворюватиметься на кожному модулі, доки правило не з'явиться.

### 8.2 Послідовність

Канонічною лишається `13.7` попереднього документа. Порядок кроків **не
змінюється**; нижче до кожного додано вердикт, борг інверсії та передумову.

| Крок (`13.7`)                 | Домен(и)                                     | Вердикт    | Борг         | Передумова                           |
| ----------------------------- | -------------------------------------------- | ---------- | ------------ | ------------------------------------ |
| **0.6** _(пропоноване, нове)_ | прибрати 8 мертвих інверсних аксесорів       | —          | —            | розділ 3.3; за зразком `R-8`         |
| 3                             | **A8 UserSchedule**                          | 🟢         | +2           | —                                    |
| 4                             | A4 Identity → рішення по `User` → Core       | 🟡         | 0            | **розділ 2** — гейт усієї ініціативи |
| 5                             | `app/Actions/Pages` + переписування ArchTest | —          | —            | `R-6`, `AC-10`, `A-9`                |
| 6                             | A5 MentorProgram → A6 Marketplace            | 🟡→🟢 / 🟡 | +2 / +2      | крок 0.6; `A-4` (`User::rating`)     |
| 7                             | domain events → A2 → A3 → **A9**             | 🔴         | +2 / +1 / +1 | `R-3`, `R-4`                         |
| 8                             | A10 Payments                                 | 🟢\*       | 0            | рішення «заглушка чи відкласти»      |

**Три уточнення до `13.7`** — усі позначені як пропозиції, що потребують
підтвердження:

1. **Крок 0.6 (новий)** — видалення мертвих інверсних аксесорів. Дешево, знімає
   всі 5 двонаправлених пар модельного шару (розділ 3.3), і **має передувати**
   будь-якому виносу.
2. **Notifications вилучено з кроку 3.** `13.7` називає крок 3 «UserSchedule /
   Notifications». Notifications — supporting capability (B1), а не домен;
   модулем ставати не має. Це **перекваліфікація, не переставлення**.
3. **A9 MentorSession прикріплено до кроку 7.** У `13.7` він не згадується
   жодним кроком — це **заповнення пропуску**. Обґрунтування: його межа фізично
   не існує, доки не введено domain events, які і є передумовою кроку 7.

---

## 9. Верифікаційна дельта

Попередні документи позначали метрики як успадковані без переперевірки (`I-1`,
`G-6`) і лишали статус інструментального blast-radius невідомим (`R-D`). У цьому
проході перевірено.

### 9.1 🟢 Blast-radius: 7 із 9 місць уже закриті

| Місце (за `9.1`)         | Стан | Доказ                                                          |
| ------------------------ | ---- | -------------------------------------------------------------- |
| `composer.json` autoload | ✅   | `composer-merge-plugin` + `Modules/Chat/composer.json`         |
| `phpstan.neon` `paths`   | ✅   | `:6-12` — `- Modules`, `excludePaths: Modules/*/tests/*`       |
| `rector.php` `withPaths` | ✅   | `:23` — `__DIR__.'/Modules'`                                   |
| `ci.yml` мутаційні шарди | ✅   | `:438` — шард **F** `Modules/Chat/app`, `testsuite: 'Modules'` |
| `ci.yml` ключ кешу       | ✅   | `:465` — `hashFiles('app/**', 'Modules/**')`                   |
| `ArchTest.php`           | ✅   | `:36-58` — strict_types, не-порожність, заборона крос-імпортів |
| `AdminPanelProvider`     | ✅   | `:82` — цикл `Module::all()`                                   |
| `vite.config.js` `input` | ❌   | `:14` — лише `resources/js/app.js`                             |
| `app.js` / `ssr.js` glob | ❌   | `:18` / `:19` — `./Pages/**/*.vue`                             |

Додатково: `phpunit.xml:17-20` має окремий testsuite `Modules`; `<source>`
включає `./Modules`. **Два невиконані пункти — рівно фронтенд** (розділ 7).

### 9.2 Уточнення до попередніх тверджень

| #   | Факт                                              | Розходження                      |
| --- | ------------------------------------------------- | -------------------------------- |
| V-1 | Core→Module ребер **три**, не одне                | BA називав лише `User.php:27`    |
| V-2 | Правила `App → Modules` не існує                  | не зафіксовано ніде              |
| V-3 | `MentorSession` — у **17** файлах `app/`          | попередній документ: «лише 5»    |
| V-4 | Борг інверсії = **12 класів** / 15 методів        | «15 ребер» — помилка рахунку     |
| V-5 | Модельний шар не вимірювався жодним із документів | розділ 3                         |
| V-6 | `MentorProgram → Calendar` **існує**              | `5` попереднього документа: «ні» |
| V-7 | 8 міждоменних інверсних аксесорів **мертві**      | нове                             |
| V-8 | `Currency` — 9 файлів-посилань                    | попередній документ: 13          |
| V-9 | Chat залежить від Marketplace через `User`        | нове (розділ 2.4)                |

---

## 10. Відкриті питання та аномалії

### 10.1 Блокуючі питання

**Q-A. Який варіант для `User` — A, B, C чи D?** (`R-2`, розділ 2) Де-факто
реалізовано **A**; рекомендацією було **D**; ADR немає. Від відповіді залежить,
чи зможуть arch-тести взагалі забороняти крос-імпорти, чи модульність лишиться
косметичною (розділ 2.4). ⚠ Нове обмеження: чистий варіант B **не покриє 2
енуми** з 12 (розділ 2.3).

**Q-B. «Бекенд-only модуль» — свідомий стандарт?** (`G-1`, розділ 7) Тулінг
говорить «так» (9.1). `docs/FRONTEND_ARCHITECTURE.md` про модулі не знає.
Потрібно або підтвердити явно, або оновити документ — **до** модуля №2.

**Q-C. Історичні міграції — у модулях чи в корені?** (`R-11` проти `L-3`) `R-11`
радив не чіпати; пілот переніс усі 4. Правило потрібне до наступного модуля, бо
міжмодульні FK залежать від порядку таймстемпів (`AC-17`).

**Q-D (нове). Чи видаляємо 8 мертвих інверсних аксесорів?** (розділ 3.3) Дешево
і знімає всю двонаправленість модельного шару. Ризик: аксесор міг призначатися
для майбутньої фічі. Потрібне підтвердження власника, як і для `R-8`.

### 10.2 Аномалії

| #             | Аномалія                                                                                                   | Статус                                                                                                                                                                                                                                                                                                                                |
| ------------- | ---------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `A-2`         | `payments.currency varchar(3)` проти `currency_id` всюди                                                   | рішення не зафіксоване (розділ 4.10)                                                                                                                                                                                                                                                                                                  |
| `A-3`         | Payments — модель без маршрутів/Actions                                                                    | підтверджено, посилено (`V-4`)                                                                                                                                                                                                                                                                                                        |
| `A-4`         | `User::rating` живе в Core                                                                                 | блокер A6                                                                                                                                                                                                                                                                                                                             |
| `A-6`         | `User::activeScheduleRecords()` — `where→orWhere→where` без групування, ймовірна помилка пріоритету AND/OR | ⚠ **досі не виправлено** (`User.php:217-223`); поза межами міграції                                                                                                                                                                                                                                                                   |
| `A-7`         | `CalendarProviderEnum` імпортує 4 сервіси                                                                  | прибрати при виносі A3                                                                                                                                                                                                                                                                                                                |
| `A-8`         | `withoutScopedBindings()` на 2 маршрутах A3                                                                | врахувати в `AC-1`                                                                                                                                                                                                                                                                                                                    |
| **`A-9`** 🆕  | `routes/web.php:63-66` реєструє `profile.update` і `profile.destroy` **двічі**                             | ⚠ на `AC-1` **не впливає** — перевірено: `route:list --except-vendor` дає **71** маршрут, по одному рядку на кожен (`RouteCollection` ключується method+URI, повторна реєстрація перезаписує). **Реальна небезпека — крок 5**: одна копія переїде у файл модуля, друга лишиться, і `AC-1` цього не спіймає, бо кількість не зміниться |
| **`A-10`** 🆕 | 8 мертвих міждоменних інверсних аксесорів                                                                  | розділ 3.3                                                                                                                                                                                                                                                                                                                            |

---

## 11. Межі цього проходу

| #   | Обмеження                                                                                                                                                                                                                                                                                                                                                                                       |
| --- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| I-1 | Тіла всіх Actions/Services не читались — прочитані всі 17 моделей, 3 Observers, 6 Filters, провайдери, роути, тулінг-конфіги; межі сервісів оцінені за назвами й вибірковими файлами                                                                                                                                                                                                            |
| I-2 | «Мертвість» аксесорів (3.3) встановлена статично трьома патернами (3.3). Динамічні виклики (`$model->{$var}`) і рядки, зібрані конкатенацією, нею **не покриваються** — перед видаленням обов'язковий прогін тестів. ⚠ Перший прохід дав хибний результат саме через неповний патерн (`participants`), тому будь-яке видалення робити **окремим PR із зеленим CI**, а не в складі виносу модуля |
| I-3 | LOC по областях не перераховувались: попередні цифри успадковані **й тому в цьому документі не наводяться**                                                                                                                                                                                                                                                                                     |
| I-4 | `resources/js/Components/**` пофайлово не аналізувався — крім двох відомих доменних протікань (розділ 7)                                                                                                                                                                                                                                                                                        |

> **Оновлення:** обмеження `I-1` і `I-4` **знято** додатком A — там повний
> пофайловий інвентар усіх шарів, включно з `resources/js/**` і `tests/**`.

---

# Додаток A. Повні файлові маніфести доменів

> **Призначення:** відповісти на питання «які саме файли переїдуть, коли домен X
> стане модулем». Перелічено **кожен** файл, не вибірку.
>
> **Метод:** `find`/`grep` по поточному дереву в цьому проході — не з пам'яті.
>
> **Покриття (звірено):** `app/` — **208** файлів `.php`; `Modules/` — 49 +
> `.gitkeep`; `database/migrations` — 56; `database/factories` — 19;
> `database/seeders` — 15; `routes/` — 4; `tests/` — 190; `resources/js/` — 105.
> **Разом ≈ 646 файлів.**
>
> **Легенда:** `⚖` — **спірний/спільний** файл: належить двом доменам,
> перелічений в **обох** (реєстр — A.16). `🖥` — фронтенд: за чинною конвенцією
> «бекенд-only модуль» (розділ 7) **залишається** в загальному дереві
> `resources/js/`, навіть коли бекенд домену переїде.

---

## A.1 — A1 Chat (еталон: повний маніфест уже винесеного модуля)

**Це референс:** так виглядає домен, коли він повністю зібраний. 51 файл +
`Modules/.gitkeep`.

| Шар                   | Файли                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Actions** (10)      | `Modules/Chat/app/Actions/{ChatListUser, ChatMessages, CreateChat, DownloadChatFile, GetMessage, SendMessage, SetArchive, SetBan, SetMute, UnreadMessages}.php`                                                                                                                                                                                                                                                                                                          |
| **Actions/Pages** (1) | `Modules/Chat/app/Actions/Pages/GetChatPage.php`                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Models** (2)        | `Modules/Chat/app/Models/{Chat, ChatMessage}.php`                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Policies** (2)      | `Modules/Chat/app/Policies/{ChatPolicy, ChatMessagesPolicy}.php`                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Events** (2)        | `Modules/Chat/app/Events/{ChatMessageEvent, UnreadMessagesEvent}.php`                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Broadcasting** (1)  | `Modules/Chat/app/Broadcasting/ChatChannel.php`                                                                                                                                                                                                                                                                                                                                                                                                                          |
| **Enums** (1)         | `Modules/Chat/app/Enums/ChatStatusEnum.php`                                                                                                                                                                                                                                                                                                                                                                                                                              |
| **Requests** (1)      | `Modules/Chat/app/Http/Requests/ChatMessageRequest.php`                                                                                                                                                                                                                                                                                                                                                                                                                  |
| **Resources** (3)     | `Modules/Chat/app/Http/Resources/{ChatFileResource, ChatMessageResource, ChatUserResource}.php`                                                                                                                                                                                                                                                                                                                                                                          |
| **Providers** (2)     | `Modules/Chat/app/Providers/{ChatServiceProvider, RouteServiceProvider}.php`                                                                                                                                                                                                                                                                                                                                                                                             |
| **Routes** (1)        | `Modules/Chat/routes/web.php`                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| **Migrations** (4)    | `Modules/Chat/database/migrations/{2025_02_13_110429_create_chats_table, 2025_02_14_124231_create_chat_messages_table, 2025_11_24_212000_create_chats_table, 2025_11_24_213000_create_chat_users_table}.php` ⚠ дві `create_chats_table` (`G-3`)                                                                                                                                                                                                                          |
| **Factories** (2)     | `Modules/Chat/database/factories/{ChatFactory, ChatMessageFactory}.php`                                                                                                                                                                                                                                                                                                                                                                                                  |
| **Seeders** (2)       | `Modules/Chat/database/seeders/{ChatDatabaseSeeder, ChatMessageSeeder}.php`                                                                                                                                                                                                                                                                                                                                                                                              |
| **Tests** (13)        | `Modules/Chat/tests/Feature/{ChatAuthorizationTest, DownloadChatFileTest}.php`; `Modules/Chat/tests/Unit/Actions/{ChatListUserTest, ChatMessagesTest, CreateChatTest, GetMessageTest, SendMessageTest, SetArchiveTest, SetBanTest}.php`; `Modules/Chat/tests/Unit/Actions/Pages/ChatPageTest.php`; `Modules/Chat/tests/Unit/Events/{ChatMessageEventTest, UnreadMessagesEventTest}.php`; `Modules/Chat/tests/Unit/Policies/{ChatPolicyTest, ChatMessagesPolicyTest}.php` |
| **Конфіг** (3)        | `Modules/Chat/{module.json, composer.json, config/config.php}`                                                                                                                                                                                                                                                                                                                                                                                                           |
| 🖥 **Frontend** (8)   | `resources/js/Pages/Chat/{ChatPage.vue, useCaseChat.js, useCaseFileType.js}`; `resources/js/Pages/Chat/Blocks/{ChatList, InfoList, ListUser, MainList, TiptapInput}.vue` — **не переїхали** (`D-7`, `G-1`)                                                                                                                                                                                                                                                               |
| **Зовнішні хвости**   | ⚖ `app/Models/User.php:27,160-165`; ⚖ `app/Providers/AppServiceProvider.php:33-34,96-97`; ⚖ `database/seeders/DatabaseSeeder.php:8,27`; ⚖ `routes/channels.php:11-12`; ⚖ `tests/Feature/MorphMap/{MorphMapBackfillTest, MorphMapEnforcementTest}.php`                                                                                                                                                                                                                    |

⚠ **Урок маніфеста:** навіть «повністю винесений» домен лишив **5 хвостів** поза
модулем. Це реальна вартість виносу, якої не видно з `Modules/Chat/`.

---

## A.2 — A2 Calendar

| Шар                    | Файли                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Models** (1)         | `app/Models/CalendarEvent.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| **Actions** (4 +2⚖)    | `app/Actions/Calendar/CalendarEvent/{ConfirmCalendarEvent, DeleteCalendarEvent, EditCalendarEvent, StoreCalendarEvent}.php`; ⚖ `CreateMentorSessionForCalendarEvent.php` (A9); ⚖ `SyncCalendarEventToIntegration.php` (A3)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Actions/Pages** (5)  | `app/Actions/Pages/Calendar/{CalendarsListPage, ConfirmedCalendarEventsListPage, MentorProgramEventBookingPage, PendingCalendarEventsListPage, ShowCalendarEventPage}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Services** (8 +2⚖)   | `app/Services/Calendar/{AvailableCalendarEventsSlotsService, BookingCalendarEventsService, CheckBookingSlotService, CheckTimeSlotReservedService, DailyCalendarEventsService, MonthCalendarEventsService, SplitSlotsPerSessionDuration, WeeklyCalendarEventsService}.php`; ⚖ `ExcludeUserScheduleSchemeService.php` (A8); ⚖ `AvailableSlotOptionsForMentorProgram.php` (A5)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **DTO** (5)            | `app/DTO/Calendar/{CalendarEventData, CalendarEventDayViewData, CalendarEventMonthViewData, CalendarEventWeekViewData, CalendarUIEventData}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| **Enums** (6)          | `app/Enums/{CalendarEventColoursEnum, CalendarEventMinimumBookingTimeInMinutes, CalendarEventRoleEnum, CalendarEventStatusEnum, CalendarEventTypeEnum, CalendarViewModeEnum}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| **Casts** (1)          | `app/Casts/UtcDateTime.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Observers** (1⚖)     | ⚖ `app/Observers/CalendarEventObserver.php` — пише в A9, диспатчить у A3                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Policies** (1)       | `app/Policies/CalendarEventPolicy.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Requests** (4)       | `app/Http/Requests/Calendar/CalendarEvent/{CalendarEventRequest, ConfirmCalendarEventRequest, EditCalendarEventRequest, StoreCalendarEventRequest}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Traits** (4)         | `app/Traits/Calendar/{BuildsCalendarPayload, CalculatesCalendarMetrics, CalendarEventRequestRules, RetrievesUserPivotData}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| **Notifications** (1⚖) | ⚖ `app/Notifications/CalendarEventConfirmedNotification.php` (B1)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| **Routes**             | `routes/web.php:91-118` — група `prefix('calendar')`, 9 маршрутів `pages.calendar.*`, `calendar.confirm.booking`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| **Migrations** (7)     | `2025_07_06_205059_create_calendar_events_table`, `2025_07_06_205820_create_calendar_event_user_table`, `2025_12_09_205948_add_indexes_to_calendar_events_table`, `2025_12_10_000000_remove_duration_from_calendar_events_table`, `2025_12_19_091323_add_mentor_session_id_column_to_table_calendar_events` ⚖(A9), `2026_03_15_205538_add_session_type_to_calendar_events_table`, `2026_03_28_135305_add_unique_index_is_main_to_mentor_programs` ⚖(A5)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Factories** (1)      | `database/factories/CalendarEventFactory.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| **Seeders** (1)        | `database/seeders/CalendarEventSeeder.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Tests** (30)         | _Feature:_ `tests/Feature/Calendar/{ConcurrentBookingTest, ConfirmCalendarEventTest, DeleteCalendarEventTest, EditCalendarEventTest, StoreCalendarEventTest, UpdateWebLinkAuthorizationTest}.php`; `tests/Feature/Pages/Calendar/{CalendarsListPageTest, ConfirmedCalendarEventsListPageTest, MentorProgramEventBookingPageTest, PendingCalendarEventsListPageTest, ShowCalendarEventPageTest}.php` — _Unit:_ `tests/Unit/Actions/Calendar/{ConfirmCalendarEventTest, DeleteCalendarEventTest, EditCalendarEventTest, StoreCalendarEventTest}.php`; `tests/Unit/Actions/Pages/Calendar/{ConfirmedCalendarEventsListPageTest, ListCalendarEventTest, PendingCalendarEventsListPageTest, ShowCalendarEventPageTest}.php`; `tests/Unit/Services/Calendar/{AvailableCalendarEventsSlotsServiceTest, BookingCalendarEventsServiceTest, CheckTimeSlotReservedTest, DailyCalendarEventsServiceTest, MonthCalendarEventsServiceTest, SplitSlotsPerSessionDurationTest, WeeklyCalendarEventsServiceTest}.php`; ⚖ `tests/Unit/Services/Calendar/ExcludeUserScheduleSchemeServiceTest.php` (A8); ⚖ `tests/Unit/Services/Calendar/AvailableSlotOptionsForMentorProgramTest.php` (A5); `tests/Unit/Models/{CalendarEventTest, UserCalendarFormattingTest}.php`; `tests/Unit/Observers/CalendarEventObserverTest.php`; `tests/Unit/Policies/CalendarEventPolicyTest.php`; `tests/Unit/Requests/Calendar/{EditCalendarEventRequestTest, StoreCalendarEventRequestTest}.php`; `tests/Unit/Enums/{CalendarEventColoursEnumTest, CalendarEventMinimumBookingTimeInMinutesTest, CalendarEventRoleEnumTest, CalendarEventStatusEnumTest, CalendarEventTypeEnumTest, CalendarViewModeEnumTest}.php` |
| 🖥 **Frontend** (12)   | `resources/js/Pages/Calendar/{CalendarEventsList, CreateCalendarEvent, ListConfirmedCalendarEventsPage, ListPendingCalendarEventsPage, MentorProgramEventBookingPage, ShowEditCalendarEvent}.vue`; `resources/js/Components/Calendar/{DailyView, MonthlyView, WeeklyView}.vue`; ⚖ `Components/Calendar/ExternalIntegrationsTab.vue` (A3); `resources/js/Stores/calendar.js`, `resources/js/Stores/Calendar/helpers.js`; ⚖ `Pages/Profile/Tab/CalendarTab.vue` (A7)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |

---

## A.3 — A3 ExternalCalendar

| Шар                              | Файли                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| -------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Models** (3)                   | `app/Models/{UserCalendarIntegration, ExternalCalendarEvent, ExternalCalendarEventLog}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Actions** (9 +1⚖)              | `app/Actions/Calendar/ExternalCalendar/{ExternalCalendarConnectCallback, ExternalCalendarConnectDirect, ExternalCalendarConnectRedirect, ExternalCalendarDisconnect, ExternalCalendarRetrySync, ExternalCalendarSelectCalendar, ExternalCalendarSyncSingleEvent, RerunExternalCalendarEventSync}.php`; `app/Actions/Calendar/ExternalCalendarLog/AcknowledgeExternalCalendarEventLog.php`; ⚖ `app/Actions/Calendar/CalendarEvent/SyncCalendarEventToIntegration.php` (A2)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Actions/Pages** (1)            | `app/Actions/Pages/Profile/ExternalCalendarSettingsPage.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| **Services** (7 +2 контракти)    | `app/Services/ExternalCalendar/{AbstractGoogleExternalCalendarService, AppleCalDavExternalCalendarService, ExternalCalendarServiceFactory, ExternalCalendarSynchronizationService, GoogleAppExternalCalendarService, GoogleExternalCalendarService, OutlookExternalCalendarService}.php`; `app/Services/ExternalCalendar/Contracts/{ExternalCalendarServiceInterface, OAuthCalendarServiceInterface}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Services (XML)** (5)           | `app/Services/XmlTools/ExternalCalendar/{AbstractCalDavParser, CalDavCalendarListParser, CalDavPropfindParser, CalDavReportParser, IcsBuilder}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| **Services (crypto)** (1)        | `app/Services/Encryption/CalendarCredentialEncrypter.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Casts** (1)                    | `app/Casts/EncryptedCalendarCredential.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Jobs** (7 — **усі в проєкті**) | `app/Jobs/{CreateExternalCalendarEvent, DeleteExternalCalendarEvent, ProcessCalendarEventExternalCalendarIntegrations, ProcessDeleteExternalCalendarEvent, ProcessUpdateExternalCalendarEvent, ReEncryptCalendarCredentials, UpdateExternalCalendarEvent}.php` ⚠ `R-7`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| **DTO** (2)                      | `app/DTO/ExternalCalendar/{ExternalCalendarEventData, OAuthCallbackState}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Enums** (4)                    | `app/Enums/{CalendarProviderEnum, CalendarSyncStatusEnum, ExternalCalendarEventLogTypeEnum, ExternalCalendarEventSyncStatusEnum}.php` ⚠ `A-7`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Policies** (3)                 | `app/Policies/{UserCalendarIntegrationPolicy, ExternalCalendarEventPolicy, ExternalCalendarEventLogPolicy}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| **Requests** (8)                 | `app/Http/Requests/Calendar/ExternalCalendar/{ExternalCalendarConnectCallbackRequest, ExternalCalendarConnectDirectRequest, ExternalCalendarConnectRedirectRequest, ExternalCalendarDisconnectRequest, ExternalCalendarRequest, ExternalCalendarRetrySyncRequest, ExternalCalendarSelectCalendarRequest, ExternalCalendarSyncSingleEventRequest}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Traits** (3)                   | `app/Traits/ExternalCalendar/{EscapesText, HandlesCalendarIntegrationCleanup, XmlAppleCalendarRequests}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| **Rate limiters**                | ⚖ `app/Providers/AppServiceProvider.php:73-79` — `calendar-connect`, `calendar-retry`, `calendar-sync`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| **Routes**                       | `routes/web.php:126-159` — група `prefix('settings/external-calendar')`, 9 маршрутів; `routes/web.php:160-161` — `external-calendar.connect.callback` (поза групою) ⚠ `A-8`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Migrations** (3)               | `2026_04_04_100543_create_user_calendar_integrations_table`, `2026_04_04_110000_create_external_calendar_events_table`, `2026_04_13_100001_create_external_calendar_event_logs_table`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Factories** (3)                | `database/factories/{UserCalendarIntegrationFactory, ExternalCalendarEventFactory, ExternalCalendarEventLogFactory}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| **Tests** (32)                   | _Feature:_ `tests/Feature/Actions/Calendar/AcknowledgeExternalCalendarEventLogFeatureTest.php`; `tests/Feature/Calendar/{ExternalCalendarConnectCallbackRequestTest, ExternalCalendarConnectCallbackTest, ExternalCalendarRequestTest}.php`; `tests/Feature/Pages/Settings/ExternalCalendarSettingsPageTest.php` — _Integration:_ `tests/Integration/ExternalCalendar/{AppleCalendarIntegrationTest, GoogleCalendarIntegrationTest, OutlookCalendarIntegrationTest}.php` — _Unit:_ `tests/Unit/Actions/Calendar/{AcknowledgeExternalCalendarEventLogTest, ExternalCalendarConnectCallbackTest, ExternalCalendarConnectDirectTest, ExternalCalendarConnectRedirectTest, ExternalCalendarDisconnectTest, ExternalCalendarRetrySyncTest, ExternalCalendarSelectCalendarTest, ExternalCalendarSyncSingleEventTest, RerunExternalCalendarEventSyncTest, SyncCalendarEventToIntegrationTest}.php`; `tests/Unit/Jobs/{CreateExternalCalendarEventTest, DeleteExternalCalendarEventTest, ProcessCalendarEventExternalCalendarIntegrationsTest, ProcessDeleteExternalCalendarEventTest, ProcessUpdateExternalCalendarEventTest, ReEncryptCalendarCredentialsTest, UpdateExternalCalendarEventTest}.php`; `tests/Unit/Services/ExternalCalendar/{AppleCalDavExternalCalendarServiceTest, ExternalCalendarServiceFactoryTest, ExternalCalendarSynchronizationServiceTest, GoogleAppExternalCalendarServiceTest, GoogleExternalCalendarServiceTest, OutlookExternalCalendarServiceTest}.php`; `tests/Unit/Services/ExternalCalendar/XmlTools/{CalDavCalendarListParserTest, CalDavPropfindParserTest, CalDavReportParserTest, IcsBuilderTest}.php`; `tests/Unit/Services/Encryption/CalendarCredentialEncrypterTest.php`; `tests/Unit/Traits/ExternalCalendar/XmlAppleCalendarRequestsTest.php`; `tests/Unit/Enums/{CalendarProviderEnumTest, CalendarSyncStatusEnumTest, ExternalCalendarEventLogTypeEnumTest, ExternalCalendarEventSyncStatusEnumTest}.php` |
| 🖥 **Frontend** (4)              | `resources/js/Pages/Settings/ExternalCalendarPage.vue`; ⚖ `resources/js/Pages/Profile/Tab/ExternalCalendarTab.vue` (A7); ⚖ `resources/js/Components/Calendar/ExternalIntegrationsTab.vue` (A2); `resources/js/Composables/useExternalCalendar.js`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |

---

## A.4 — A4 Identity & Access

| Шар                 | Файли                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Models**          | ⚖ `app/Models/User.php` — **Core**, не A4 (розділ A.15)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| **Actions** (17)    | `app/Actions/Auth/{ConfirmPassword, GetConfirmPasswordPage, Logout, UpdatePassword, VerificationEmailNotification, VerificationEmailPrompt, VerifyEmail}.php`; `Login/{GetLoginPage, Login}.php`; `Register/{GetRegistrationPage, Registration}.php`; `Reset/{CreatePassword, GetCreatePasswordPage, GetResetPasswordPage, ResetPassword}.php`; `Socialite/{SocialiteCallback, SocialiteRedirect}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| **Requests** (7)    | `app/Http/Requests/Auth/{ConfirmPasswordRequest, UpdatePasswordRequest, VerifyEmailRequest}.php`; `Login/LoginRequest.php`; `Register/RegistrationRequest.php`; `Reset/{CreatePasswordRequest, ResetPasswordRequest}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| **Observers** (1⚖)  | ⚖ `app/Observers/UserObserver.php` — створює `UserProfile` (A7), призначає роль (`R-5`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| **Enums** (1 +2⚖)   | `app/Enums/SocialiteDriverEnum.php`; ⚖ `app/Enums/{RoleEnum, RoleGuardEnum}.php` (Core — потрібні всім доменам)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Routes**          | **увесь `routes/auth.php`** (77 рядків): `:24-52` група `guest` (register, login, forgot/reset password, `prefix('auth')` socialite), `:54-77` група `auth` (verify-email, confirm-password, password update, logout)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| **Migrations** (3)  | `0001_01_01_000000_create_users_table`, `2025_01_22_190238_update_users_table_change_name_to_username`, `2025_01_17_214329_create_permission_tables` (spatie); ⚖ `2025_06_12_192000_add_slug_field` (slug генерує `UserObserver`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Factories** (1⚖)  | ⚖ `database/factories/UserFactory.php` (Core)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| **Seeders** (6)     | `database/seeders/{RoleSeeder, UserSeeder, AdminSeeder, SuperAdminSeeder, MentiSeeder, CoachSeeder}.php` — усі призначають ролі через `RoleEnum`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Tests** (31)      | _Feature:_ `tests/Feature/Auth/{ConfirmPasswordTest, LoginTest, LogoutTest, RegistrationTest, UpdatePasswordTest, VerificationEmailTest, VerifyEmailTest}.php`; `tests/Feature/Auth/Reset/{GetCreatePasswordTest, ResetPasswordTest}.php`; `tests/Feature/Auth/Socialite/{SocialiteCallbackTest, SocialiteRedirectTest}.php`; `tests/Feature/Pages/Auth/ConfirmPasswordPageTest.php` — _Unit:_ `tests/Unit/Actions/Auth/{ConfimPasswordTest ⚠опечатка, LogoutTest, UpdatePasswordTest, VerificationEmailNotificationTest, VerificationEmailPromptTest, VerifyEmailTest}.php`; `tests/Unit/Actions/Auth/Login/{GetLoginPageTest, LoginTest}.php`; `tests/Unit/Actions/Auth/Register/RegistrationTest.php`; `tests/Unit/Actions/Auth/Reset/{CreatePasswordTest, GetCreatePasswordPageTest, GetResetPasswordPageTest, ResetPasswordTest}.php`; `tests/Unit/Actions/Auth/Socialite/{SocialiteCallbackTest, SocialiteRedirectTest}.php`; `tests/Unit/Http/Requests/Auth/{ConfirmPasswordRequestTest, UpdatePasswordRequestTest, VerifyEmailRequestTest}.php`; `tests/Unit/Http/Requests/Auth/Login/LogiRequestTest.php` ⚠опечатка; `tests/Unit/Http/Requests/Auth/Register/RegistrationRequestTest.php`; `tests/Unit/Http/Requests/Auth/Reset/CreatePasswordRequestTest.php`; `tests/Unit/Observers/UserObserverTest.php`; `tests/Unit/Enums/{RoleEnumTest, RoleGuardEnumTest, SocialiteDriverEnumTest}.php` |
| 🖥 **Frontend** (7) | `resources/js/Pages/Auth/{ConfirmPassword, ForgotPassword, LoginPage, RegisterPage, ResetPassword, VerifyEmail}.vue`; ⚖ `Pages/Profile/Partials/Form/UpdatePasswordForm.vue` (A7)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |

---

## A.5 — A5 MentorProgram

| Шар                   | Файли                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Models** (3)        | `app/Models/{MentorProgram, MentorProgramBlock, MentorProgramBlockProgress}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| **Actions** (5)       | `app/Actions/MentorPrograms/{DeleteMentorProgram, DeleteMentorProgramPage, SetMainMentorProgram, StoreMentorProgramPage, UpdateMentorProgramPage}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| **Actions/Pages** (3) | `app/Actions/Pages/MentorProgram/{CreateMentorProgramPage, EditMentorProgramPage, ListMentorProgramPage}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Services** (1⚖)     | ⚖ `app/Services/Calendar/AvailableSlotOptionsForMentorProgram.php` (A2)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| **Observers** (1)     | `app/Observers/MentorProgramObserver.php` — slug + `is_main`, пише лише у власну модель                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| **Policies** (1)      | `app/Policies/MentorProgramPolicy.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| **Requests** (2)      | `app/Http/Requests/MentorProgram/{StoreMentorProgramRequest, UpdateMentorProgramRequest}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Resources** (1)     | `app/Http/Resources/MentorProgramsResource.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| **Routes**            | `routes/web.php:69-89` — група `prefix('mentor-program')` + `middleware(['auth','role:mentor'])`, 7 маршрутів `mentor-program.*`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| **Migrations** (9)    | `2025_02_04_131649_create_mentor_programs_table`, `2025_03_01_073933_create_mentor_program_blocks_table`, `2025_03_01_225621_create_mentor_program_block_progresses_table`, `2025_12_06_195003_add_start_and_end_time_column_to_mentor_program_table`, `2025_12_07_214817_add_session_duration_at_mentor_programs_table`, `2026_01_04_230154_update_session_duration_column_to_mentor_programs_table`, `2026_03_13_132802_add_is_main_to_mentor_programs_table`, `2026_03_15_205553_add_session_type_options_and_need_confirmation_to_mentor_programs_table`, `2026_03_24_150008_delete_session_duration_options_column_to_mentor_programs_table` |
| **Factories** (3)     | `database/factories/{MentorProgramFactory, MentorProgramBlockFactory, MentorProgramBlockProgressFactory}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| **Seeders** (1)       | `database/seeders/MentorProgramSeeder.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Tests** (12)        | _Feature:_ `tests/Feature/MentorPrograms/{DeleteMentorProgramTest, SetMainMentorProgramTest, StoreMentorProgramTest, UpdateMentorProgramTest}.php`; `tests/Feature/Pages/MentorProgram/{CreateMentorProgramTest, EditMentorProgramTest}.php` — _Unit:_ `tests/Unit/Actions/MentorPrograms/{DeleteMentorProgramTest, SetMainMentorProgramTest, StoreMentorProgramTest, UpdateMentorProgramTest}.php`; `tests/Unit/Actions/Pages/MentorProgram/ListMentorProgramTest.php`; `tests/Unit/Observers/MentorProgramObserverTest.php`; `tests/Unit/Http/Resources/MentorProgramsResourceTest.php`                                                         |
| 🖥 **Frontend** (3)   | `resources/js/Pages/MentorProgram/{CreateOrEdit, ListPage}.vue`; ⚖ `Pages/Profile/Mentor/Blocks/AvailablePrograms.vue` (A6)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |

⚠ **Мертві аксесори до видалення (розділ 3.3):**
`MentorProgram::{calendarEvents, mentorSession, mentorProfiles}()`.

---

## A.6 — A6 Marketplace

| Шар                   | Файли                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Models** (3)        | `app/Models/{MentorProfile, MentorTag, MentorReview}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| **Actions** (1)       | `app/Actions/MentorTag/CreateMentorTag.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| **Actions/Pages** (3) | `app/Actions/Pages/Profile/{GetMentorProfilePage, GetMentorReviewPage, ListMentorProfilePage}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Filters** (6)       | `app/Filters/{ExperienceLevelFilter, ProfileRateFilter, ProgramCostFilter, RatingFilter, TagLanguagesFilter, TagStacksFilter}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Traits** (1)        | `app/Traits/ParsesNumericRange.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Enums** (1)         | `app/Enums/TagEnum.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| **Resources** (3)     | `app/Http/Resources/{MentorProfilePageResource, MentorReviewResource, SimilarMentorResource}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Core-хвіст** (1⚖)   | ⚖ `app/Models/User.php:245-250` — атрибут `rating()` (`A-4`), фільтрується `RatingFilter:28`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| **Routes**            | `routes/web.php:51,53-54` — `page.profile-programs`, `page.mentor`, `page.mentor-review` (публічні, поза групами)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Migrations** (7)    | `2025_01_26_113314_create_mentor_reviews_table`, `2025_07_20_101300_create_mentor_profiles_table`, `2025_07_23_185833_create_mentor_tags_table`, `2025_07_23_190248_create_mentor_profile_mentor_tag_table`, `2025_08_11_104549_create_mentor_profile_mentor_program_table` ⚖(A5), `2025_12_19_210757_add_session_duration_options_to_mentor_profiles_table`, `2025_12_24_161042_add_indexes_for_mentor_search_tables`                                                                                                                                                                                                                                                                                                    |
| **Factories** (3)     | `database/factories/{MentorProfileFactory, MentorTagFactory, MentorReviewFactory}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Seeders** (4)       | `database/seeders/{MentorSeeder, MentorProfileSeeder, MentorTagSeeder, MentorReviewSeeder}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| **Tests** (14)        | _Feature:_ `tests/Feature/Filters/{ProfileRateFilterIntegrationTest, ProgramCostFilterIntegrationTest}.php`; `tests/Feature/Pages/MentorProfile/ListMentorProfilePageTest.php`; `tests/Feature/Pages/Profile/{GetMentorProfilePageTest, GetMentorReviewPageTest}.php` — _Unit:_ `tests/Unit/Filters/{ExperienceLevelFilterTest, ProfileRateFilterTest, ProgramCostFilterTest, RatingFilterTest, TagLanguagesFilterTest, TagStacksFilterTest}.php`; `tests/Unit/Actions/MentorTag/CreateMentorTagTest.php`; `tests/Unit/Actions/Pages/Profile/GetMentorProfilePageTest.php`; `tests/Unit/Http/Resources/SimilarMentorResourceTest.php`; `tests/Unit/Traits/ParsesNumericRangeTest.php`; `tests/Unit/Enums/TagEnumTest.php` |
| 🖥 **Frontend** (13)  | `resources/js/Pages/Profile/MentorListPage.vue`; `Pages/Profile/Mentor/{ViewPage, ViewSave}.vue`; `Pages/Profile/Mentor/Blocks/{AboutMentor, AvailablePrograms, CalendarMentor, ContactMentor, FooterMentor, ReviewsMentor, SimilarMentors, TitleProfile}.vue`; ⚖ `Components/UI/Table/MentorsList.vue` (доменне протікання в глобальну теку)                                                                                                                                                                                                                                                                                                                                                                             |

---

## A.7 — A7 UserProfile

| Шар                   | Файли                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Models** (1)        | `app/Models/UserProfile.php` (`implements HasMedia`)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Actions** (4)       | `app/Actions/Profile/{UpdateUserProfile, DeleteUserProfile}.php`; `app/Actions/User/{UpdateUser, AddAvatar}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Actions/Pages** (1) | `app/Actions/Pages/Profile/GetProfilePage.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| **Requests** (3)      | `app/Http/Requests/User/UpdateUserRequest.php`; `app/Http/Requests/UserProfile/{DeleteUserProfileRequest, UpdateUserProfileRequest}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| **Resources** (1)     | `app/Http/Resources/UserProfileResource.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| **Observers** (1⚖)    | ⚖ `app/Observers/UserObserver.php` (A4) — створює `UserProfile`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Морф-аліас** (⚖)    | ⚖ `app/Providers/AppServiceProvider.php:95` — `'user_profile' => UserProfile::class`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Routes**            | `routes/web.php:61-67` — група `middleware('auth')`: `user.update`, `profile.edit`, `profile.update`, `profile.destroy` ⚠ `A-9` (рядки 65-66 — дублікати)                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| **Migrations** (10)   | `2025_01_19_144231_create_user_profiles_table`, `2025_02_13_092549_delete_avatar_field`, `2025_05_01_215000_update_profile_change_watsapp`, `2025_05_05_213500_update_profile_change_name_last_name`, `2025_06_08_152329_add_mentor_description_and_title_to_user_profiles_table`, `2025_07_02_144000_add_cost_per_hour_field`, `2025_07_02_150000_change_cost_type`, `2025_07_20_101000_delete_mentor_description_and_title_from_user_profiles_table`, `2025_12_09_133234_add_timezone_to_user_profiles_table` ⚖(A2), `2026_01_05_091732_add_minimum_booking_time_column_to_user_profiles_table` ⚖(A2) |
| **Factories** (1)     | `database/factories/UserProfileFactory.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Tests** (11)        | _Feature:_ `tests/Feature/Pages/Profile/{DestroyProfileTest, GetProfilePageTest, UpdateProfilePageTest, UpdateUserPageTest}.php` — _Unit:_ `tests/Unit/Actions/UserProfile/{DeleteUserProfileTest, UpdateUserProfileTest}.php`; `tests/Unit/Actions/User/{AddAvatarTest, UpdateUserTest}.php`; `tests/Unit/Actions/Pages/User/GetProfilePageTest.php`; `tests/Unit/Http/Requests/Profile/{DeleteUserProfileRequestTest, UpdateProfileRequestTest}.php`; `tests/Unit/Http/Requests/User/UpdateUserRequestTest.php`                                                                                       |
| 🖥 **Frontend** (12)  | `resources/js/Pages/Profile/{Edit, EditPage}.vue`; `Pages/Profile/Partials/Components/MobileTabSelect.vue`; `Pages/Profile/Partials/Form/{DeleteUserForm, UpdateProfileForm, UpdateUserForm}.vue`; ⚖ `Partials/Form/UpdatePasswordForm.vue` (A4); `Pages/Profile/Tab/MyAccountTab.vue`; ⚖ `Tab/{BillingTab (A10), CalendarTab (A2), ExternalCalendarTab (A3), MentorScheduleTab (A8), NotificationTab (B1)}.vue`                                                                                                                                                                                        |

⚠ **`Pages/Profile/` — 24 Vue-файли на 6 доменів** (`G-5`). Найдорожче
розчленування фронтенду.

---

## A.8 — A8 UserSchedule (🟢 ready — маніфест для наступного виносу)

| Шар                         | Файли                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Models** (1)              | `app/Models/UserSchedule.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| **Actions** (1)             | `app/Actions/UserSchedule/StoreBatchUserSchedule.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Actions/Pages** (1)       | `app/Actions/Pages/UserSchedule/UserSchedulePage.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Services** (1 +1⚖)        | `app/Services/UserSchedule/CheckUserScheduleOverlap.php`; ⚖ `app/Services/Calendar/ExcludeUserScheduleSchemeService.php` (A2 — споживач)                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Policies** (1)            | `app/Policies/UserSchedulePolicy.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| **Requests** (1)            | `app/Http/Requests/UserSchedule/StoreBatchUserScheduleRequest.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| **Resources** (1)           | `app/Http/Resources/UserSchedule/UserScheduleViewResource.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| **Enums** (1)               | `app/Enums/UserScheduleRecordType.php` ⚠ імпортується Core-`User.php:9` (розділ 2.3)                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| **Реєстрація політики** (⚖) | ⚖ `app/Providers/AppServiceProvider.php:63` — `Gate::policy(UserSchedule::class, ...)`                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| **Routes**                  | `routes/web.php:120-123` — група `middleware(['auth','verified','role:mentor'])->prefix('user-schedule')`: `user-schedule.index`, `user-schedule.batch`                                                                                                                                                                                                                                                                                                                                                                                     |
| **Migrations** (1)          | `2025_11_18_081748_create_user_schedules_table`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| **Factories** (2)           | `database/factories/{UserScheduleFactory, UserScheduleDayOffFactory}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| **Seeders** (1)             | `database/seeders/UserScheduleSeeder.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| **Tests** (7)               | _Feature:_ `tests/Feature/UserSchedule/UserScheduleTest.php` — _Unit:_ `tests/Unit/Actions/UserSchedule/StoreBatchUserScheduleTest.php`; `tests/Unit/Actions/Pages/UserSchedule/GetUserSchedulePageTest.php`; `tests/Unit/Services/UserSchedule/CheckUserScheduleOverlapTest.php`; `tests/Unit/Policies/UserSchedulePolicyTest.php`; `tests/Unit/Requests/UserSchedule/StoreBatchUserScheduleRequestTest.php`; `tests/Unit/Http/Resources/UserSchedule/UserScheduleViewResourceTest.php`; `tests/Unit/Enums/UserScheduleRecordTypeTest.php` |
| 🖥 **Frontend** (2)         | `resources/js/Pages/UserSchedule/ListPage.vue`; ⚖ `Pages/Profile/Tab/MentorScheduleTab.vue` (A7)                                                                                                                                                                                                                                                                                                                                                                                                                                            |

**Разом до виносу: 9 бекенд-файлів + 1 міграція + 2 фабрики + 1 сідер + 7 тестів
= 20 файлів**, з них **1 спірний** (`ExcludeUserScheduleSchemeService` лишається
в A2) і **2 хвости** (`AppServiceProvider:63`, `User.php:9,201-204,217-223`).
Найменший можливий модуль.

---

## A.9 — A9 MentorSession

| Шар                | Файли                                                                                                                                                                                                                                                                     |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Models** (2)     | `app/Models/{MentorSession, MentorSessionNote}.php`                                                                                                                                                                                                                       |
| **Actions** (1⚖)   | ⚖ `app/Actions/Calendar/CalendarEvent/CreateMentorSessionForCalendarEvent.php` — **фізично в теці A2**                                                                                                                                                                    |
| **Enums** (2)      | `app/Enums/{MentorSessionTypeEnum, MentorSessionDurationOptionsEnum}.php` ⚠ `MentorSessionTypeEnum` — cast у `CalendarEvent` (A2)                                                                                                                                         |
| **Observers** (1⚖) | ⚖ `app/Observers/CalendarEventObserver.php:36` (A2) — **єдина точка створення сутності**                                                                                                                                                                                  |
| **Routes**         | **немає** — 0 маршрутів                                                                                                                                                                                                                                                   |
| **Policies**       | **немає** — 0 політик                                                                                                                                                                                                                                                     |
| **Migrations** (4) | `2025_02_09_162244_create_mentor_sessions_table`, `2025_02_12_191817_create_mentor_session_notes_table`, `2026_01_04_123338_add_mentor_program_id_column_to_mentor_sessions_table` ⚖(A5), `2025_12_19_091323_add_mentor_session_id_column_to_table_calendar_events` ⚖(A2) |
| **Factories** (2)  | `database/factories/{MentorSessionFactory, MentorSessionNoteFactory}.php`                                                                                                                                                                                                 |
| **Tests** (3)      | `tests/Unit/Actions/Calendar/CreateMentorSessionForCalendarEventTest.php`; `tests/Unit/Enums/{MentorSessionTypeEnumTest, MentorSessionDurationOptionsEnumTest}.php`                                                                                                       |
| 🖥 **Frontend**    | **немає** — 0 сторінок                                                                                                                                                                                                                                                    |

⚠ **Домен без власного коду:** 2 моделі + 2 енуми — і все. Жодного Action,
маршруту, політики чи сторінки, що йому _належать_. Це підтверджує вердикт 🔴
(розділ 4.9). **Мертві аксесори:**
`MentorSession::{calendarEvent, mentorProgram, payment, mentorSessionNote}()`.

---

## A.10 — A10 Payments

| Шар                                                                               | Файли                                                                     |
| --------------------------------------------------------------------------------- | ------------------------------------------------------------------------- |
| **Models** (1)                                                                    | `app/Models/Payment.php`                                                  |
| **Migrations** (1)                                                                | `2025_02_13_144853_create_payments_table` ⚠ `A-2` (`currency varchar(3)`) |
| **Factories** (1)                                                                 | `database/factories/PaymentFactory.php`                                   |
| **Actions / Services / Policies / Requests / Resources / Enums / Routes / Tests** | **немає — жодного файлу**                                                 |
| 🖥 **Frontend** (1⚖)                                                              | ⚖ `resources/js/Pages/Profile/Tab/BillingTab.vue` (A7) — UI-заглушка      |

**Разом: 3 файли.** Найменший домен у проєкті; підтверджує «готовий і
безвартісний» (розділ 4.10).

---

## A.11 — B1 Notifications

| Шар                    | Файли                                                                                                                                                                                   |
| ---------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Actions** (3)        | `app/Actions/Notifications/{ListNotifications, MarkNotificationAsRead, MarkAllNotificationsAsRead}.php`                                                                                 |
| **Notifications** (1⚖) | ⚖ `app/Notifications/CalendarEventConfirmedNotification.php` — **належить A2**                                                                                                          |
| **Resources** (1)      | `app/Http/Resources/NotificationResource.php`                                                                                                                                           |
| **Core-хвіст** (1⚖)    | ⚖ `app/Models/User.php:232-235` — `receivesBroadcastNotificationsOn()` (`DR-4`, **не чіпати**)                                                                                          |
| **Routes**             | `routes/web.php:163-167` — група `prefix('notifications')`: `notifications.index`, `notifications.read`, `notifications.read-all`                                                       |
| **Migrations** (1)     | `2026_04_10_155231_create_notifications_table`                                                                                                                                          |
| **Tests** (4)          | `tests/Feature/Actions/Notifications/{ListNotificationsTest, MarkAllNotificationsAsReadTest, MarkNotificationAsReadTest}.php`; `tests/Unit/Http/Resources/NotificationResourceTest.php` |
| 🖥 **Frontend** (3)    | `resources/js/Components/UI/Notifications/{NotificationBell, PopUp}.vue`; ⚖ `Pages/Profile/Tab/NotificationTab.vue` (A7)                                                                |

---

## A.12 — B2 Media

| Шар                 | Файли                                                                                                                                                                  |
| ------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Support** (1)     | `app/Support/MediaLibrary/PathGenerator.php`                                                                                                                           |
| **Конфіг**          | `config/media-library.php:85` — реєстрація `PathGenerator`                                                                                                             |
| **Migrations** (2)  | `2025_02_17_085903_create_media_table`; ⚖ `2025_02_13_092549_delete_avatar_field` (A7)                                                                                 |
| **Споживачі** (3⚖)  | ⚖ `app/Models/User.php` (`HasMedia`), ⚖ `app/Models/UserProfile.php` (колекція `avatar`, конверсія `preview`), ⚖ `Modules/Chat/app/Models/ChatMessage.php` (вкладення) |
| **Tests** (1)       | `tests/Unit/Support/MediaLibrary/PathGeneratorTest.php`                                                                                                                |
| **Морф-аліаси** (⚖) | ⚖ `app/Providers/AppServiceProvider.php:94-97` — `DR-2` закритий                                                                                                       |

---

## A.13 — B3 Presence / realtime

| Шар                  | Файли                                                                                                                                                 |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Broadcasting** (1) | `app/Broadcasting/OnlineUsersChannel.php`                                                                                                             |
| **Routes**           | `routes/channels.php:8` — `App.Models.User.{id}` (⚖ B1, `DR-4`); `:9` — `presence-online-users`; `:11-12` — коментар про `Chat.{id}` у модулі (`G-4`) |
| 🖥 **Frontend** (2)  | `resources/js/echo.js`, `resources/js/bootstrap.js`                                                                                                   |
| **Tests**            | **немає**                                                                                                                                             |

---

## A.14 — B4 Admin (Filament)

| Шар                         | Файли                                                                                                                                          |
| --------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| **Resources** (6)           | `app/Filament/Resources/User/UserResource.php`; `Pages/{CreateUser, EditUser, ListUsers}.php`; `Schemas/UserForm.php`; `Tables/UsersTable.php` |
| **Providers** (1)           | `app/Providers/Filament/AdminPanelProvider.php` — 🟢 `:82` уже модуле-обізнаний (`Module::all()`)                                              |
| **Routes**                  | генеруються панеллю `supervisor` (3 маршрути `supervisor/user/users*`) — не в `routes/*.php`                                                   |
| **Tests** (3)               | `tests/Feature/Filament/{FilamentUserResourceTest, UserFormSchemaTest, UserResourceMentorProfileTest}.php`                                     |
| **Крос-доменні залежності** | ⚖ `UserForm.php:183-185` → `mentorTags` (A6); ⚖ `UserResource` → `User` (Core)                                                                 |

---

## A.15 — Core / Shared Kernel (окремий режим для `User`)

### `app/Models/User.php` — не належить жодному домену

**Це не домен, а точка розкладу.** Модель оголошує 15 relation-методів у 8
доменів (розділ 2.3) і **не може** бути віднесена до жодного з них.

| Домени, що посилаються на `User` | Через що                                                                                                      |
| -------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| A1 Chat                          | `chats()` — ⚠ **`use Modules\Chat\Models\Chat` (`:27`) — Core→Module**                                        |
| A2 Calendar                      | `calendarEvents()`, `hostedCalendarEvents()`, `participatingCalendarEvents()`; `CalendarEventRoleEnum` (`:7`) |
| A3 ExternalCalendar              | `calendarIntegrations()`                                                                                      |
| A5 MentorProgram                 | `mentorPrograms()`, `mentiProgramProgress()`                                                                  |
| A6 Marketplace                   | `mentorProfile()`, `mentorReviews()`, `reviewsByMenti()`, **атрибут `rating()`** (`:245-250`)                 |
| A7 UserProfile                   | `profile()`                                                                                                   |
| A8 UserSchedule                  | `schedules()`, `activeScheduleRecords()`; `UserScheduleRecordType` (`:9`) ⚠ `A-6`                             |
| A9 MentorSession                 | `mentorSessions()`, `mentiSessions()`                                                                         |
| B1 Notifications                 | `receivesBroadcastNotificationsOn()` (`:232-235`) — `DR-4`, **не чіпати**                                     |
| B2 Media                         | `implements HasMedia`, `InteractsWithMedia`                                                                   |
| B4 Admin                         | `implements HasName`, `getFilamentName()`                                                                     |
| A4 Identity                      | `HasRoles`, `$guard_name`, `#[ObservedBy(UserObserver::class)]`                                               |

**Константи чужої відповідальності:** `DEFAULT_MENTOR_PAGE_PAGINATION` (A6),
`NOTIFICATIONS_PER_PAGE` (B1), `MIN_PASSWORD_LENGTH` (A4).

**Рішення про розклад — `Q-A` (розділ 10.1). Цей документ його не приймає.**

### Решта Core

| Шар                               | Файли                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| --------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Models** (2)                    | `app/Models/User.php` (див. вище), `app/Models/Currency.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| **Enums** (3)                     | `app/Enums/CurrencyEnum.php`; ⚖ `app/Enums/{RoleEnum, RoleGuardEnum}.php` (A4)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| **Providers** (2)                 | `app/Providers/AppServiceProvider.php` ⚠ **містить хвости 6 доменів** (морф-мапа, 5 `Gate::policy`, 3 rate limiters); `app/Providers/TelescopeServiceProvider.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| **Http** (2)                      | `app/Http/Controllers/Controller.php` (базовий, порожній); `app/Http/Middleware/HandleInertiaRequests.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| **Migrations** (5)                | `0001_01_01_000001_create_cache_table`, `0001_01_01_000002_create_jobs_table`, `2025_02_01_131225_create_currencies_table`, `2025_10_02_084455_add_preferences_column_to_users_table`, `2026_08_04_120000_backfill_morph_map_aliases`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| **Інфраструктурні міграції** (3)  | `2025_01_21_092220_create_telescope_entries_table`, `2025_02_04_165518_create_pulse_tables`, `2025_01_17_214329_create_permission_tables` ⚖(A4)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| **Factories** (2)                 | ⚖ `database/factories/UserFactory.php`, `CurrencyFactory.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| **Seeders** (2)                   | `database/seeders/{DatabaseSeeder, CurrencySeeder}.php` ⚠ `DatabaseSeeder:8,27` — Core→Module                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| **Tests** (7)                     | `tests/Pest.php`, `tests/TestCase.php`, `tests/Unit/ArchTest.php`, `tests/Unit/Enums/CurrencyEnumTest.php`, `tests/Feature/MorphMap/{MorphMapBackfillTest, MorphMapEnforcementTest}.php` ⚖(A1), `tests/Feature/Pages/ErrorPageTest.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| **Інфраструктурні тести** (3)     | `tests/Feature/Pages/{LogViewerTest, Pulse/PulsePageTest, Telescope/TelescopeTest}.php`                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| 🖥 **Frontend — глобальний** (37) | `app.js`, `ssr.js`, `bootstrap.js`, `echo.js`; `Layouts/{AuthenticatedLayout, GuestLayout, LandingLayout}.vue`; `Components/{AlertNotification, AppDropdown, AppModal, DropdownLink, GuideCarouselModal, MainPageText, NavLink, ResponsiveNavLink}.vue`; `Components/Navigation/{AppFooter, AppPagination}.vue`, `Components/Navigation/Navbar/{AppNavbar, NavbarLogo}.vue`; `Components/UI/Button/{CloseButton, DangerButton, LinkedinButton, PrimaryButton, SecondaryButton, TelegramButton, WhatsappButton}.vue`; `Components/UI/Forms/{CheckboxInput, InputError, InputLabel, InputSuccess, PhoneNumberInput, SelectField, TextArea, TextInput}.vue`; `Components/UI/Icons/{CodeIcon, DocIcon, FileIcon, PdfIcon, PngIcon}.vue`; `Components/UI/Logo/{ApplicationLogo, GithubLogo, GoogleLogo}.vue`; `Stores/{footer, navigation}.js`; `UseCases/useCaseAlert.js`; `Pages/{DashboardPage, WelcomePage}.vue` |

---

## A.16 — `app/Actions/Pages` — наскрізний шар (мапа розчленування)

Не домен. **16 файлів**, кожен переїжджає у свій домен:

| Файл                                                                                                                                                     | Домен    |
| -------------------------------------------------------------------------------------------------------------------------------------------------------- | -------- |
| `Calendar/{CalendarsListPage, ConfirmedCalendarEventsListPage, MentorProgramEventBookingPage, PendingCalendarEventsListPage, ShowCalendarEventPage}.php` | A2 (5)   |
| `MentorProgram/{CreateMentorProgramPage, EditMentorProgramPage, ListMentorProgramPage}.php`                                                              | A5 (3)   |
| `Profile/{GetMentorProfilePage, GetMentorReviewPage, ListMentorProfilePage}.php`                                                                         | A6 (3)   |
| `Profile/GetProfilePage.php`                                                                                                                             | A7 (1)   |
| `Profile/ExternalCalendarSettingsPage.php`                                                                                                               | A3 (1)   |
| `UserSchedule/UserSchedulePage.php`                                                                                                                      | A8 (1)   |
| `DashboardPage.php`, `WelcomePage.php`                                                                                                                   | Core (2) |

⚠ Після розчленування простори `App\Actions\Pages` і `App\Actions\Pages\Profile`
перестануть існувати → `tests/Unit/ArchTest.php:30-33` стане **тихо порожнім**
(`R-6`, `AC-10`). Супутні тести:
`tests/Unit/Actions/Pages/{DashboardPageTest, WelcomePageTest}.php`.

---

## A.17 — Реєстр спірних файлів (⚖)

Файли, які **не можна** віднести до одного домену без втрати інформації про
зчеплення.

| Файл                                                                         | Домени                        | Природа зчеплення                                                    |
| ---------------------------------------------------------------------------- | ----------------------------- | -------------------------------------------------------------------- |
| `app/Models/User.php`                                                        | **11 доменів**                | shared kernel — розділ A.15                                          |
| `app/Providers/AppServiceProvider.php`                                       | Core + A2, A3, A8, A1, A7, B2 | морф-мапа + 5 `Gate::policy` + 3 rate limiters                       |
| `app/Observers/CalendarEventObserver.php`                                    | A2 + A9 + A3                  | синхронний запис у A9, dispatch у A3 (`R-4`)                         |
| `app/Observers/UserObserver.php`                                             | A4 + A7                       | створює `UserProfile` при `created()` (`R-5`)                        |
| `app/Actions/Calendar/CalendarEvent/CreateMentorSessionForCalendarEvent.php` | A2 + A9                       | тека A2, створює сутність A9                                         |
| `app/Actions/Calendar/CalendarEvent/SyncCalendarEventToIntegration.php`      | A2 + A3                       | тека A2, логіка A3 — зворотне ребро                                  |
| `app/Services/Calendar/ExcludeUserScheduleSchemeService.php`                 | A2 + A8                       | Calendar читає розклад                                               |
| `app/Services/Calendar/AvailableSlotOptionsForMentorProgram.php`             | A2 + A5                       | Calendar читає параметри програми                                    |
| `app/Notifications/CalendarEventConfirmedNotification.php`                   | A2 + B1                       | подія A2, механізм B1                                                |
| `app/Enums/{RoleEnum, RoleGuardEnum}.php`                                    | A4 + Core                     | оголошені A4, потрібні всім                                          |
| `app/Enums/MentorSessionTypeEnum.php`                                        | A9 + A2                       | cast у `CalendarEvent`                                               |
| `app/Enums/CalendarEventRoleEnum.php`                                        | A2 + Core                     | імпортується `User.php:7`                                            |
| `app/Enums/UserScheduleRecordType.php`                                       | A8 + Core                     | імпортується `User.php:9`                                            |
| `app/Filament/Resources/User/Schemas/UserForm.php`                           | B4 + A6                       | `->relationship('mentorTags', 'tag')`                                |
| `database/seeders/DatabaseSeeder.php`                                        | Core + A1                     | `use Modules\Chat\...` (`:8,27`)                                     |
| `routes/channels.php`                                                        | B3 + B1 + A1                  | 3 домени в 8 рядках                                                  |
| `routes/web.php`                                                             | **8 доменів**                 | один файл — 8 груп маршрутів                                         |
| `Modules/Chat/app/Actions/ChatListUser.php:121`                              | A1 + A6                       | `$companion->mentorProfile->mentorTags` — **приховане** (розділ 2.4) |
| `resources/js/Pages/Profile/Tab/*.vue` (6)                                   | A7 + A2, A3, A8, A10, B1      | одна тека — 6 доменів                                                |
| `resources/js/Components/UI/Table/MentorsList.vue`                           | Core-тека + A6                | доменне протікання                                                   |
| `resources/js/Components/UI/Notifications/NotificationBell.vue`              | Core-тека + B1                | доменне протікання                                                   |
| `resources/js/Components/Calendar/ExternalIntegrationsTab.vue`               | A2 + A3                       | доменне протікання                                                   |
| `tests/Feature/MorphMap/*.php` (2)                                           | Core + A1                     | тестують морф-аліаси Chat                                            |

### Осиротілі файли (не належать жодному домену)

| Файл                                                                                                               | Проблема                                                                                                                                                                      |
| ------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `database/factories/AttachmentFactory.php`                                                                         | 🆕 **`A-11`**: фабрика для моделі `Attachment`, **якої не існує**, і таблиці `attachments`, яку створено (`2025_02_07_120416`) і видалено (`2025_02_17_110200`). Мертвий файл |
| `database/migrations/{2025_02_07_120416_create_attachments_table, 2025_02_17_110200_delete_attachments_table}.php` | пара «створити+видалити»; лишаються в історії міграцій, домену не мають                                                                                                       |
| `routes/console.php`                                                                                               | інфраструктура                                                                                                                                                                |

---

## A.18 — Зведення обсягів

| Домен               | Бекенд `.php` | Міграції | Фабрики/сідери | Тести | 🖥 Frontend |
| ------------------- | ------------- | -------- | -------------- | ----- | ----------- |
| A1 Chat ✅          | 25 (у модулі) | 4        | 4              | 13    | 8           |
| A2 Calendar         | 40            | 7        | 2              | 30    | 12          |
| A3 ExternalCalendar | 54            | 3        | 3              | 32    | 4           |
| A4 Identity         | 28            | 3        | 7              | 31    | 7           |
| A5 MentorProgram    | 16            | 9        | 4              | 12    | 3           |
| A6 Marketplace      | 18            | 7        | 7              | 14    | 13          |
| A7 UserProfile      | 11            | 10       | 1              | 11    | 12          |
| A8 UserSchedule     | 9             | 1        | 3              | 7     | 2           |
| A9 MentorSession    | 5             | 4        | 2              | 3     | 0           |
| A10 Payments        | 1             | 1        | 1              | 0     | 1           |
| B1 Notifications    | 5             | 1        | 0              | 4     | 3           |
| B2 Media            | 1             | 2        | 0              | 1     | 0           |
| B3 Presence         | 1             | 0        | 0              | 0     | 2           |
| B4 Admin            | 7             | 0        | 0              | 3     | 0           |
| Core                | 11            | 8        | 4              | 10    | 37          |

> Числа включають спірні файли **в кожному** домені, де вони фігурують, тому
> сума перевищує фізичну кількість файлів. Це навмисно: колонка показує **обсяг
> роботи на домен**, а не розподіл без перетинів.

**Три найдорожчі домени — A3 (54), A2 (40), A4 (28)** — разом ≈ 60 % бекенду.
**Найдешевший реальний виніс — A8 (9 бекенд-файлів).**
