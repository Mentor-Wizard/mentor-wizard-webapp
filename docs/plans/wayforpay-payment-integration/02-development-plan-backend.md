# Laravel Implementation Plan: Інтеграція платіжної системи WayForPay (Backend)

> task_slug: `wayforpay-payment-integration` | aspect: backend Мова наративу: uk
> | Версія плану: 1.0

---

## 1. Контекст і обмеження

### Ключові факти кодової бази

| Файл                                                              | Стан                          | Що потрібно                                                                              |
| ----------------------------------------------------------------- | ----------------------------- | ---------------------------------------------------------------------------------------- |
| `app/Models/Payment.php`                                          | існує                         | рефакторинг fillable/casts/relations                                                     |
| `database/migrations/2025_02_13_144853_create_payments_table.php` | існує                         | **не чіпати** — нова міграція для змін                                                   |
| `app/Enums/CurrencyEnum.php`                                      | існує                         | values = `₴ $ € £`, names = `UAH USD EUR GBP` — **використовувати `->name`** для WFP API |
| `app/Support/CurrencyConverter.php`                               | у git staged (A), не на диску | створити при імплементації float→kopiyky                                                 |
| `app/Observers/CurrencyObserver.php`                              | у git staged (A)              | враховувати при eager loading                                                            |
| `app/Models/MentorProgram.php`                                    | існує                         | `cost` cast float, немає `is_paid`                                                       |
| `app/Models/MentorSession.php`                                    | існує                         | `cost` cast float, є `is_paid` boolean                                                   |
| `app/Policies/CalendarEventPolicy.php`                            | зразок                        | `final class`, без реєстрації в AuthServiceProvider (auto-discovery)                     |

### Встановлений пакет

`aratkruglik/wayforpay-laravel` — обрано за:

- Native Laravel integration, strict typing
- Підтримує: `purchase()`, `checkStatus()`, `refund()`, вбудована
  HMAC-верифікація вебхуків
- 69 code snippets у Context7, Medium reputation

API пакету:

```php
WayForPay::purchase($transaction, returnUrl: '...', serviceUrl: '...');   // initiate
WayForPay::checkStatus('ORDER_REF');                                        // check
WayForPay::refund('ORDER_REF', 50.00, 'UAH', 'reason');                   // refund
// вебхук: пакет верифікує HMAC, диспатчить WayForPayCallbackReceived event
```

---

## 2. Критичні проектні рішення

### 2.1 Polymorphic `payable` замість `mentor_session_id` FK

Відповідно до Q&A stakeholders (#2): Payment підтримує оплату як
`MentorSession`, так і `MentorProgram`.

```
payments.payable_type = 'App\Models\MentorSession' | 'App\Models\MentorProgram'
payments.payable_id   = <FK>
```

**Міграція:** видалити `mentor_session_id` FK, додати `payable_type` +
`payable_id` + polymorphic index.

Стара колонка `mentor_session_id` присутня в Payment fillable та migration —
обов'язково видалити FK-constraint до дропу.

### 2.2 Позначення "оплачено" — `Payable` interface

`MentorSession.is_paid` існує, `MentorProgram` — немає. Вводимо
`Contracts\Payable` interface:

```php
interface Payable
{
    public function markPaid(): void;
    public function markUnpaid(): void;
}
```

`MentorSession` implements через `is_paid = true`. `MentorProgram` implements
через `sessions()->update(['is_paid' => true])` або no-op — уточнити при
імплементації. За замовчуванням — no-op для програми (оплата стосується
конкретної сесії всередині програми).

### 2.3 CurrencyEnum values vs names

`CurrencyEnum::UAH->value === '₴'` — непридатне для WFP API.
`CurrencyEnum::UAH->name === 'UAH'` — ISO-код, потрібен для WFP.

У всіх місцях передачі валюти до WFP: **`$currency->name`**, не
`$currency->value`.

### 2.4 Каскад vs Restrict при видаленні

BA залишив відкритим. Рішення: **`restrictOnDelete()`** для `payable`
polymorphic relation. Фінансові записи не мають автоматично зникати при
видаленні сесії/програми. Документується для artisan-specialist.

### 2.5 Асинхронна обробка вебхуків

Пакет диспатчить `WayForPayCallbackReceived` event синхронно (з
HMAC-верифікацією). Тонкий `EventListener` → диспатчить
`HandlePaymentWebhookJob` (`ShouldQueue`, `onQueue('payments')`).
Ідемпотентність в Job: lookup by `order_reference` (UNIQUE constraint), skip if
terminal status. DB-lock:
`Payment::query()->where('order_reference', $ref)->lockForUpdate()->first()`
всередині транзакції.

### 2.6 CalendarEvent статус-машина

```
PENDING_MENTOR_CONFIRMATION
    → (ментор підтверджує) → CONFIRMED (вже реалізовано)
    → (менті ініціює оплату) → PENDING_PAYMENT
    → (вебхук APPROVED) → CONFIRMED (або новий статус PAID — але CONFIRMED реалізований)
    → (вебхук DECLINED) → CONFIRMED (повертається назад, menті може повторити)
    → (адмін Refund) → CONFIRMED (is_paid скидається)
```

Рішення: після APPROVED — CalendarEvent.status залишається CONFIRMED,
`MentorSession.is_paid = true`. `PENDING_PAYMENT` enum вже є в
`CalendarEventStatusEnum` — використовуємо його.

### 2.7 Вебхук route — CSRF-exempt

Вебхук WFP реєструється в `routes/api.php` (CSRF-exempt за замовчуванням в
Laravel 12). **Не** в `routes/web.php`.

---

## 3. Схема бази даних — зміни

### Нова міграція: `alter_payments_table_for_wayforpay`

```
Видалити: mentor_session_id FK + column
Додати:
  - payable_type string
  - payable_id unsignedBigInt
  - polymorphicIndex(payable_type, payable_id)
  - order_reference: додати unique()
  - reason: nullable()
  - reason_code: nullable()
  - payment_system: nullable()
  - card_type: nullable()
  - issue_bank_name: nullable()
  - transaction_status: змінити на enum/string (PaymentStatusEnum values)
  - fee_amount integer nullable
  - fee_percentage decimal(5,4) nullable
  - net_amount integer nullable
  - refunded_at timestamp nullable
  - refund_amount integer nullable
```

**artisan-specialist** додає: indexes on `payable_type`+`payable_id`,
`order_reference` UNIQUE, `transaction_status`, `created_at`, nullable backfill
logic, factories/seeders update.

---

## 4. Файли до створення

### 4.1 Enum

| Файл                              | Призначення                                  |
| --------------------------------- | -------------------------------------------- |
| `app/Enums/PaymentStatusEnum.php` | `PENDING/APPROVED/DECLINED/REFUNDED/EXPIRED` |

### 4.2 Contract

| Файл                        | Призначення                                        |
| --------------------------- | -------------------------------------------------- |
| `app/Contracts/Payable.php` | Interface `markPaid(): void`, `markUnpaid(): void` |

### 4.3 Model

| Файл                     | Зміни                                                                                |
| ------------------------ | ------------------------------------------------------------------------------------ |
| `app/Models/Payment.php` | **modify** — polymorphic `payable()`, новий fillable/casts, `PaymentStatusEnum` cast |

### 4.4 Actions

| Файл                                                  | Призначення                                                                                            |
| ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| `app/Actions/Payments/InitiatePaymentAction.php`      | POST `/payments/initiate` — створює Payment(PENDING), генерує order_reference, повертає WFP widget URL |
| `app/Actions/Payments/HandlePaymentWebhookAction.php` | Тонкий HTTP handler — делегує до Job                                                                   |
| `app/Actions/Payments/CheckPaymentStatusAction.php`   | Filament/адмін — sync-перевірка статусу через `WayForPay::checkStatus()`                               |
| `app/Actions/Payments/RefundPaymentAction.php`        | Filament-адмін — `WayForPay::refund()`, оновлює Payment+payable                                        |
| `app/Actions/Payments/ListPaymentHistoryAction.php`   | GET `/payments/history` — Inertia page для менті/ментора                                               |
| `app/Actions/Payments/PaymentSuccessPage.php`         | GET `/payments/success` — Inertia page                                                                 |
| `app/Actions/Payments/PaymentFailurePage.php`         | GET `/payments/failure` — Inertia page                                                                 |

### 4.5 Jobs

| Файл                                   | Призначення                                                                  |
| -------------------------------------- | ---------------------------------------------------------------------------- |
| `app/Jobs/HandlePaymentWebhookJob.php` | `ShouldQueue`, `onQueue('payments')` — ідемпотентна обробка вебхука, DB-lock |

### 4.6 Listeners

| Файл                                          | Призначення                                                              |
| --------------------------------------------- | ------------------------------------------------------------------------ |
| `app/Listeners/DispatchPaymentWebhookJob.php` | Слухає `WayForPayCallbackReceived`, диспатчить `HandlePaymentWebhookJob` |

### 4.7 Form Requests

| Файл                                                    | Призначення                             |
| ------------------------------------------------------- | --------------------------------------- |
| `app/Http/Requests/Payments/InitiatePaymentRequest.php` | Валідація: `payable_type`, `payable_id` |

### 4.8 Policy

| Файл                             | Призначення                                                                   |
| -------------------------------- | ----------------------------------------------------------------------------- |
| `app/Policies/PaymentPolicy.php` | `initiate(User, Payable)` — лише менті з підтвердженою сесією може ініціювати |

### 4.9 Міграція

| Файл                                                                    | Призначення                               |
| ----------------------------------------------------------------------- | ----------------------------------------- |
| `database/migrations/YYYY_MM_DD_alter_payments_table_for_wayforpay.php` | Схема-зміни (stub для artisan-specialist) |

### 4.10 Support

| Файл                                | Призначення                                                            |
| ----------------------------------- | ---------------------------------------------------------------------- |
| `app/Support/CurrencyConverter.php` | float→kopiyky (integer). Метод: `static toKopiyky(float $amount): int` |

---

## 5. Файли до модифікації

| Файл                                                                    | Що змінюється                                                      |
| ----------------------------------------------------------------------- | ------------------------------------------------------------------ |
| `app/Models/Payment.php`                                                | polymorphic relation, новий fillable, enum casts                   |
| `app/Models/MentorSession.php`                                          | implements `Payable` interface                                     |
| `app/Models/MentorProgram.php`                                          | implements `Payable` interface (no-op for `markPaid`)              |
| `routes/web.php`                                                        | додати маршрути `/payments/*` (Inertia pages)                      |
| `routes/api.php`                                                        | додати `POST /payments/webhook` (CSRF-exempt)                      |
| `app/Providers/EventServiceProvider.php` (або `AppServiceProvider.php`) | реєстрація `WayForPayCallbackReceived → DispatchPaymentWebhookJob` |
| `config/queue.php`                                                      | додати `payments` queue у Redis connections (якщо немає)           |

> **Примітка**: `PaymentPolicy` — auto-discovered by Laravel 12 (`Payment` model
> → `PaymentPolicy`). Реєстрація в `AuthServiceProvider` не потрібна.

---

## 6. Порядок імплементації та залежності

```
1. composer require aratkruglik/wayforpay-laravel
   php artisan vendor:publish --tag=wayforpay-config

2. app/Enums/PaymentStatusEnum.php
   app/Contracts/Payable.php

3. app/Support/CurrencyConverter.php

4. database/migrations/YYYY_alter_payments_table.php  (stub)
   → artisan-specialist розширює

5. app/Models/Payment.php (рефакторинг з polymorphic)
   app/Models/MentorSession.php (implements Payable)
   app/Models/MentorProgram.php (implements Payable)

6. app/Http/Requests/Payments/InitiatePaymentRequest.php
   app/Policies/PaymentPolicy.php

7. app/Actions/Payments/InitiatePaymentAction.php
   app/Actions/Payments/PaymentSuccessPage.php
   app/Actions/Payments/PaymentFailurePage.php
   app/Actions/Payments/ListPaymentHistoryAction.php

8. app/Jobs/HandlePaymentWebhookJob.php
   app/Listeners/DispatchPaymentWebhookJob.php
   app/Actions/Payments/HandlePaymentWebhookAction.php  (тонкий HTTP handler)

9. app/Actions/Payments/RefundPaymentAction.php
   app/Actions/Payments/CheckPaymentStatusAction.php
   (Filament PaymentResource — окремий filament agent)

10. routes/web.php + routes/api.php

11. EventServiceProvider: listener registration
    config/queue.php: payments queue
```

---

## 7. Inertia Props Contract

### `Payments/Success` — GET `/payments/success`

```ts
{
  payment: {
    order_reference: string,
    amount: number,          // kopiyky
    currency: string,        // 'UAH' | 'USD' | 'EUR' | 'GBP'
    transaction_status: string,  // 'approved'
    payment_system: string | null,
    created_at: string,      // ISO-8601
  },
  payable: {
    type: 'mentor_session' | 'mentor_program',
    id: number,
    label: string,           // назва програми або дата сесії
  }
}
```

### `Payments/Failure` — GET `/payments/failure`

```ts
{
  payment: {
    order_reference: string,
    amount: number,
    currency: string,
    transaction_status: string,  // 'declined' | 'expired'
    reason: string | null,
    reason_code: string | null,
    created_at: string,
  },
  payable: {
    type: 'mentor_session' | 'mentor_program',
    id: number,
    label: string,
  },
  retry_url: string | null,    // route для повторної ініціації
}
```

### `Payments/History` — GET `/payments/history`

```ts
{
  payments: {
    data: Array<{
      id: number,
      order_reference: string,
      amount: number,
      currency: string,
      transaction_status: string,
      fee_amount: number | null,
      fee_percentage: number | null,
      net_amount: number | null,
      payment_system: string | null,
      refunded_at: string | null,
      refund_amount: number | null,
      created_at: string,
      payable: {
        type: 'mentor_session' | 'mentor_program',
        id: number,
        label: string,
      }
    }>,
    links: PaginationLinks,    // Laravel paginator links
    meta: PaginationMeta,
  },
  filters: {
    status: string | null,
    from: string | null,
    to: string | null,
  }
}
```

> Shared Inertia props (з `HandleInertiaRequests`): `auth.user`, `flash`
> (success/error).

---

## 8. Ризики та edge cases

| Ризик                                                   | Рівень    | Мітигація в плані                                             |
| ------------------------------------------------------- | --------- | ------------------------------------------------------------- |
| Race condition вебхуків                                 | Критичний | DB `lockForUpdate()` в транзакції в `HandlePaymentWebhookJob` |
| CurrencyEnum value vs name                              | Високий   | Явно `->name` у всіх WFP-викликах; документовано в §2.3       |
| Polymorphic "markPaid" — MentorProgram не має `is_paid` | Середній  | `Payable` interface, no-op у MentorProgram; §2.2              |
| cascadeOnDelete на `mentor_session_id`                  | Критичний | restrictOnDelete у новій схемі; §2.4                          |
| float→kopiyky похибка                                   | Середній  | `CurrencyConverter::toKopiyky()` — ціла математика            |
| WFP refund amount float vs int                          | Середній  | kopiyky / 100 для WFP API (float), зворотна конвертація       |
| Дублікат вебхука                                        | Високий   | UNIQUE order_reference + перевірка terminal status            |
| Підроблений HMAC                                        | Критичний | Пакет верифікує автоматично; логування при відмові            |
| Vебхук до редіректу                                     | Низький   | Стан визначається вебхуком; success/failure page читає з DB   |
| CalendarEvent на скасованій сесії                       | Середній  | Перевірка статусу в `InitiatePaymentAction`                   |

---

## 9. Convention Skills для виклику при імплементації

- `laravel-plugin:laravel-conventions` — Actions, Form Requests, Policies,
  Routes
- `laravel-plugin:eloquent-patterns` — polymorphic relations, scopes, eager
  loading
- `php-foundation:php-conventions` — `declare(strict_types=1)`, type hints,
  trailing commas
- `php-foundation:composer-tooling` — `composer require`, vendor:publish
- `php-foundation:php-testing` — Pest PHP patterns для Unit/Feature тестів
  (qa-agent)

---

## 10. Передача artisan-specialist

- Нова міграція є stub — потрібно: indexes (`payable_type`+`payable_id`,
  `order_reference` unique, `transaction_status`), `restrictOnDelete()` на
  polymorphic, backfill `payable_*` з існуючого `mentor_session_id` якщо є дані
- `config/queue.php`: додати Redis connection для черги `payments` (driver:
  redis)
- Оновити `PaymentFactory` під нову схему (polymorphic, PaymentStatusEnum)
- Оновити seeder якщо використовується
