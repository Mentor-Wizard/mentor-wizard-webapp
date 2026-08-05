# Модульна архітектура (DDD через `nwidart/laravel-modules`)

## Огляд

Проєкт переходить від єдиного дерева `app/` до **модульного моноліту**: один
деплой, одна база даних, але бізнес-логіка розділена на самодостатні модулі за
бізнес-доменами (bounded contexts у термінах DDD). Межі забезпечує пакет
[`nwidart/laravel-modules`](https://nwidart.com/laravel-modules/) (`^13.0`,
сумісний з Laravel 13) разом із arch-тестами (`tests/Unit/ArchTest.php`).

Це рішення документує **чому** обрано саме такий підхід і **як** ним оперувати
день у день. Каталог доменів, статус готовності кожного до виносу та детальний
аналіз меж — в окремому документі:
[`docs/temp/ddd-domain-analysis.md`](temp/ddd-domain-analysis.md).

## Чому модульний моноліт, а не мікросервіси

| Критерій                      | Модульний моноліт (обрано)                        | Мікросервіси                                 |
| ----------------------------- | ------------------------------------------------- | -------------------------------------------- |
| Розгортання                   | один деплой, той самий Octane/FrankenPHP процес   | окремий деплой на кожен сервіс               |
| Транзакції між доменами       | звичайна DB-транзакція                            | розподілені транзакції / saga                |
| Мережеві виклики між доменами | немає — прямі виклики в межах процесу             | HTTP/gRPC + retry/circuit-breaker            |
| Межі домену                   | enforced статично (`ArchTest.php`), а не процесом | enforced фізично (окремий процес/база)       |
| Вартість інфраструктури       | без змін                                          | +N сервісів, service mesh, спостережуваність |

Команда невелика, а продукт — один монолітний домен користувачів (менторів і
менторі), тому фізичне розділення на сервіси додало б операційну складність без
відповідної вигоди. DDD-межі потрібні для **читаності й тестованості коду**, а
не для незалежного масштабування частин системи — модульний моноліт дає перше
без ціни другого.

## Чому `nwidart/laravel-modules`

- Підтримує Laravel 13 (`composer.json`: `"nwidart/laravel-modules": "^13.0"`).
- Генерує ті самі будівельні блоки, які проєкт вже використовує — Actions,
  Enums, Policies, міграції, фабрики, тести — через `php artisan module:*`, без
  необхідності писати власні генератори.
- Кожен модуль — окремий `composer.json` з PSR-4 автозавантаженням, що
  реєструється через `wikimedia/composer-merge-plugin` (`composer.json` →
  `extra.merge-plugin.include: ["Modules/*/composer.json"]`) — без ручного
  редагування кореневого `composer.json` на кожен новий модуль.
- Активація/деактивація модуля незалежна від видалення коду
  (`modules_statuses.json`, `FileActivator`) — корисно під час поступової
  міграції, коли частина доменів вже винесена, а частина — ще ні.
- Вже інтегрований з рештою тулінгу проєкту (див. «Що вже підключено» нижче) —
  вибір мінімізує додаткову роботу з інтеграції на кожен наступний модуль.

## Межі: bounded contexts vs supporting capabilities

Не кожна область коду стає модулем. Повний каталог із вердиктами по кожному
домену — у [`docs/temp/ddd-domain-analysis.md`](temp/ddd-domain-analysis.md).
Коротко:

- **Bounded contexts** (кандидати в `Modules/*`) — області з власними бізнес-
  правилами й моделями: Chat (вже винесено), Calendar (вже винесено),
  ExternalCalendar, Identity & Access, MentorProgram, Marketplace, UserProfile,
  UserSchedule, MentorSession, Payments.
- **Supporting capabilities** (лишаються в `app/` як Core/Shared, **не** стають
  модулями) — Notifications, Media, Presence/realtime, Admin (Filament). Це
  наскрізна інфраструктура без власних бізнес-правил, а не домен.
- **Shared Kernel** — `User`, `Currency`, спільні Enum'и (`RoleEnum`,
  `RoleGuardEnum`) і морф-мапа лишаються в Core; модулі посилаються на них, а не
  навпаки.

Правило arch-тесту: модуль **не може** імпортувати інший модуль напряму
(`tests/Unit/ArchTest.php`, перевірено для `Modules\Chat`). Кожен новий модуль
повинен додати дзеркальне правило для себе.

## Конвенції каталогу модуля

Генератор налаштований у `config/modules.php` під наявні конвенції проєкту, а не
під стандартні стаби пакета:

```
Modules/{Name}/
├── app/
│   ├── Actions/          # той самий підхід, що й app/Actions — див. ACTIONS_ARCHITECTURE.md
│   ├── Models/
│   ├── Enums/
│   ├── Policies/
│   ├── Services/         # опційно — якщо домен має сервісний шар (прецедент: Calendar)
│   ├── DTO/               # опційно — якщо домен передає структуровані дані між шарами (прецедент: Calendar)
│   ├── Traits/            # опційно — якщо домен ділить поведінку між кількома класами (прецедент: Calendar)
│   ├── Casts/              # опційно — власні Eloquent-каст-класи домену (прецедент: Calendar)
│   ├── Observers/          # опційно — Eloquent-обсервери домену (прецедент: Calendar)
│   ├── Http/Requests/
│   ├── Http/Resources/
│   ├── Events/
│   ├── Broadcasting/
│   └── Providers/{Name}ServiceProvider.php
├── config/
├── database/{migrations,factories,seeders}/
├── routes/
├── tests/{Unit,Feature}/
└── composer.json         # PSR-4: Modules\{Name}\ → app/
```

Опційні теки (`Services/`, `DTO/`, `Traits/`, `Casts/`, `Observers/`) не є
довільним вибором — вони дзеркалять `app/`-теки, для яких `config/modules.php`
(`paths.generator`) уже має записи з `generate => false`. Модуль створює лише ті
з них, які реально потрібні домену; порожні теки не створюються про запас.

Ключові рішення в `config/modules.php`, які відрізняють цей проєкт від дефолтної
конфігурації пакета:

- **`'controller' => ['generate' => false]`** — проєкт не використовує
  Controller'и; маршрутизація йде через invokable Actions
  (`lorisleiva/laravel-actions`), як і в `app/`.
- **`'inertia' => ['generate' => false]`, `'views' => ['generate' => false]`** —
  Vue/Inertia-сторінки **лишаються поза модулем**, у `resources/js/Pages/`. Це
  узгоджено з де-факто стандартом «backend-only модуль»: увесь backend-тулінг
  (Composer autoload, PHPStan, Rector, CI-шарди, `ArchTest.php`, Filament
  discovery) вказує на `Modules/`, а фронтенд-тулінг (`vite.config.js`,
  `resources/js/app.js`, `resources/js/ssr.js`) — ні. ⚠ Це узгодження ще не
  зафіксоване явним рішенням у `docs/FRONTEND_ARCHITECTURE.md` — див. «Відкриті
  питання» нижче.
- **`'composer' => ['vendor' => env('MODULE_VENDOR', 'mentor-wizard')]`** —
  модулі отримують `"name": "mentor-wizard/{module}"` у власному
  `composer.json`, а не вендорну назву пакета (`nwidart`).
- **`'migration' => ['path' => base_path('database/migrations')]`** (для
  `module:publish-migration`) — але сам модуль зберігає власні міграції в
  `Modules/{Name}/database/migrations/`; публікація в корінь — окрема, свідома
  дія, не крок за замовчуванням.
- **`'auto-discover' => ['migrations' => true]`** — міграції модуля автоматично
  підхоплюються без ручного `loadMigrationsFrom()` у сервіс-провайдері (хоча
  пілот `Chat` явно викликає `loadMigrationsFrom()` у
  `ChatServiceProvider::boot()` — обидва шляхи працюють одночасно).

## Реєстрація й активація

- **Namespace**: `Modules\{Name}\...` (`config('modules.namespace')`).
- **Активація**: файл `modules_statuses.json` у корені репозиторію
  (`{"Chat": true, "Calendar": true}`) — вмикає/вимикає модуль без видалення
  коду.
- **Сервіс-провайдер модуля**: успадковує
  `Nwidart\Modules\Support\ModuleServiceProvider` (не базовий
  `Illuminate\Support\ServiceProvider`) — реєструє команди, переклади, конфіг,
  вʼюхи й міграції автоматично; кастомна логіка модуля (Gate- політики,
  broadcast-канали, rate limiters) додається в `boot()` після `parent::boot()`,
  як у `Modules/Chat/app/Providers/ChatServiceProvider.php`.
- **Що вже підключено на весь модульний шар** (закрито один раз для всіх
  модулів, а не по одному на кожен):
  - `composer.json` → `merge-plugin.include: ["Modules/*/composer.json"]`
  - `phpstan.neon` → `paths: [..., Modules]`,
    `excludePaths: [Modules/*/tests/*]`
  - `rector.php` → `withPaths([..., __DIR__.'/Modules'])`
  - `phpunit.xml` → окремий testsuite `Modules`, `<source>` включає `./Modules`
  - `.github/workflows/ci.yml` → мутаційний шард на `Modules/Chat/app` і
    `Modules/Calendar/app`
  - `tests/Unit/ArchTest.php` → strict_types, непорожність неймспейсу, заборона
    крос-імпортів між модулями
  - `AdminPanelProvider` (Filament) → цикл по `Module::all()` для discovery

## Довідник Artisan-команд

### Створення модуля

```bash
# Стандартний модуль
php artisan module:make Blog

# Кілька модулів одразу
php artisan module:make Blog Shop Invoices

# API-only (без web-маршрутів і вʼюх)
php artisan module:make Blog --api

# Мінімальний модуль без скаффолду
php artisan module:make Blog --plain

# Створити вимкненим (потребує ручного module:enable)
php artisan module:make Blog --disabled

# Перезаписати існуючий модуль
php artisan module:make Blog --force
```

### Генерація коду всередині модуля

```bash
# Модель (+ опції генерації супутніх файлів)
php artisan module:make-model Post Blog
php artisan module:make-model Post Blog --migration --factory --seed
php artisan module:make-model Post Blog --all      # migration, factory, seeder,
                                                    # controller, request, resource, policy
php artisan module:make-model Post Blog --fillable="title,slug,body,status"

# Controller (проєкт їх не використовує — лишається для довідки;
# `config('modules.paths.generator.controller.generate')` = false)
php artisan module:make-controller PostController Blog
php artisan module:make-controller PostController Blog --api
php artisan module:make-controller PostController Blog --invokable

# Допоміжні генератори
php artisan module:make-request StorePostRequest Blog
php artisan module:make-resource PostResource Blog
php artisan module:make-policy PostPolicy Blog
php artisan module:make-observer PostObserver Blog
php artisan module:make-scope PublishedScope Blog
```

### Керування життєвим циклом модуля

```bash
php artisan module:list              # усі модулі та їхній статус
php artisan module:enable Blog
php artisan module:disable Blog
php artisan module:delete Blog

php artisan module:migrate Blog      # міграції конкретного модуля
php artisan module:seed Blog         # сідери конкретного модуля

php artisan module:publish-migration Blog   # публікація міграцій у корінь (свідома дія — див. вище)
php artisan module:publish-config Blog
php artisan module:publish-translation Blog

php artisan module:use Blog          # дефолтний модуль для сесії (усі наступні make-команди без явного {module})
php artisan module:unuse

php artisan module:dump Blog         # перегенерувати автозавантаження модуля
php artisan module:update Blog       # composer update у межах модуля
php artisan module:clear-compiled
```

> Повний список і `--help` для кожної команди: `php artisan list module` /
> `php artisan module:make --help`.

## Вирішені питання

### Модель `User` і relation-методи домену → модульний трейт

Ухвалено під час виносу `Calendar`
(`docs/plans/migrate-calendar-domain-module`): кожен домен, що додає
relation-методи на Core-модель `User`, виносить їх у власний трейт
`Modules\{Name}\Traits\Has{Domain}` і підключає його в `User` одним рядком
(`use Has{Domain};`). Прецедент — `Modules\Calendar\Traits\HasCalendarEvents`
(`calendarEvents()`, `hostedCalendarEvents()`, `participatingCalendarEvents()`).

- Публічний контракт не змінюється: `$user->calendarEvents()` резолвиться
  ідентично — трейт розкривається в тілі класу на етапі компіляції.
- Залежність стає явною й greppable в `User`:
  `use Modules\Calendar\Traits\HasCalendarEvents;` замість розмазаних методів, а
  arch-правило `*-does-not-reach-into-other-modules` продовжує забороняти лише
  `Module → Module`.
- Трейт, що викликає `$this->belongsToMany()`/`hasMany()` тощо, обов'язково
  отримує PHPDoc `@phpstan-require-extends \Illuminate\Database\Eloquent\Model`
  — інакше Larastan (level 7) не знає, що `$this` є моделлю.
- `Modules\Chat\Traits\HasChats` (ретрофіт `User::chats()` під цей самий патерн)
  — заведений борг, не зроблений цим PR (не в скоупі Calendar).

### Розташування історичних міграцій із міжмодульними FK

Ухвалено правило (застосовано при виносі `Calendar`):

> **Міграція належить модулю, що володіє таблицею, яку вона ЗМІНЮЄ
> (`Schema::table`/`Schema::create`), а не таблицею, на яку вона посилається
> зовнішнім ключем.**

Приклад: `add_mentor_session_id_column_to_table_calendar_events` робить
`ALTER TABLE calendar_events` і додає FK на `mentor_sessions` (Core) — власник
міграції є `Calendar` (бо вона змінює `calendar_events`), а не Core.

Порядок виконання при `migrate:fresh` **не залежить від фізичного шляху файлу**:
`Nwidart\Modules\Support\ModuleServiceProvider` реєструє шлях модуля через
`loadMigrationsFrom()`, і Laravel-мігратор збирає файли з усіх зареєстрованих
шляхів в один список, сортуючи його **глобально за іменем файлу**, а не за
каталогом. Тому `git mv` міграції в модуль (без зміни імені файлу) не змінює
порядок виконання відносно міграцій, від яких вона залежить через FK — важливо
лише, щоб timestamp у імені файлу вже був пізнішим за timestamp таблиці, на яку
йде посилання.

## Відкриті питання

Це рішення ще не зафіксоване ADR і впливає на кожен наступний модуль — дивись
`docs/temp/ddd-domain-analysis.md` (розділ «Відкриті питання») для повного
обґрунтування:

1. **«Backend-only модуль» як стандарт** — тулінг де-факто підтверджує це для
   бекенду, але `docs/FRONTEND_ARCHITECTURE.md` про `Modules/` не згадує.
   Потрібно або зафіксувати це рішення явно в тому документі, або переглянути
   його до наступного модуля.

## Пов'язані документи

- [`docs/ACTIONS_ARCHITECTURE.md`](ACTIONS_ARCHITECTURE.md) — конвенції Actions,
  які застосовуються однаково в `app/` і в `Modules/*/app/`.
- [`docs/FRONTEND_ARCHITECTURE.md`](FRONTEND_ARCHITECTURE.md) — канонічна
  структура `resources/js/` (потребує оновлення щодо модулів — див. «Відкриті
  питання»).
- [`.claude/rules/architecture.md`](../.claude/rules/architecture.md) — коротке
  резюме рішення в загальних архітектурних правилах проєкту.
