# Business Analysis: Інтеграція платіжної системи WayForPay

> Джерела: GitHub Issue #144 (Epic, priority:critical, size:XL, MVP) + підзадачі
> #165–#172; `_brief.md`. Позначки: **[#144]** issue, **[CODE]** обмеження
> кодової бази, **[GAP]** не покрито джерелами.

## Executive Summary

Будуємо обробку платежів через WayForPay: менті оплачує програми/сесії на
платформі, ментор бачить надходження, власник стягує комісію та робить
повернення.

**Phase 1:** ініціація через checkout-віджет, ідемпотентні вебхуки, облік
комісії, повні повернення (адмін через Filament), сторінки історії.

**Критично:** `app/Models/Payment.php` + міграція
`2025_02_13_144853_create_payments_table.php` вже існують, але схема **не
підтримує** вимоги — у scope обов'язковий **рефакторинг таблиці `payments`**.

---

## Functional Requirements

1. **[#144]** Менті ініціює оплату через WayForPay widget
   (`InitiatePaymentAction` #166), доступ через `PaymentPolicy`.
2. **[#144]** Асинхронна обробка вебхуків (Redis-черга `payments`) з
   **ідемпотентністю** (`HandlePaymentStatusChange` #167).
3. **[#144]** Фіксація **комісії** платформи з кожної транзакції.
4. **[#144]** **Повне** повернення тільки адміном через Filament
   (`RefundPaymentAction` #170, `PaymentResource` #171).
5. **[#144]** Перевірка статусу проти WayForPay (`CheckPaymentStatusAction`
   #170) + список/деталі у Filament.
6. **[#144]** Сторінки успіху/невдачі (#168), історія для менті та менторів
   (#169).
7. **[#144]** Суми як **integer kopiyky**. **[CODE]** Конфлікт:
   `MentorProgram.cost`/`MentorSession.cost` мають cast `float`.

## Non-Functional Requirements

- **Performance:** вебхуки в Redis-черзі `payments` (async); eager loading на
  історії (N+1 prevention) **[CODE]**.
- **Security:** верифікація HMAC-підпису вебхука; ендпоінт CSRF-exempt але
  підписаний (#172); merchant secret лише в env; refund — лише адмін.
- **Compliance:** номери карт не зберігаються (лише маскові
  `card_type`/`issue_bank_name`); аудит транзакцій **[GAP]**.

---

## User Stories (Gherkin)

### Story 1 — Менті оплачує програму/сесію

```gherkin
Feature: Ініціація оплати через WayForPay

  Scenario: Успішна ініціація
    Given автентифікований менті з правами на оплату (PaymentPolicy)
    And відома вартість та валюта програми/сесії
    When менті ініціює оплату
    Then створюється Payment зі статусом PENDING
    And генерується унікальний order_reference
    And менті перенаправляється на WayForPay checkout-віджет

  Scenario: Відмова через відсутність прав
    Given менті без прав на оплату (PaymentPolicy відхиляє)
    When менті ініціює оплату
    Then повертається 403
    And Payment не створюється
```

### Story 2 — Ідемпотентна обробка вебхука

```gherkin
Feature: Обробка WayForPay webhook

  Scenario: Успішний платіж
    Given WayForPay надсилає вебхук зі статусом Approved
    And підпис HMAC валідний
    When система обробляє вебхук
    Then Payment.status → APPROVED
    And комісія платформи зафіксована
    And MentorSession.is_paid = true

  Scenario: Дублікат вебхука
    Given Payment вже має статус APPROVED
    When надходить повторний вебхук з тим самим order_reference
    Then стан не змінюється
    And дублікат не створюється

  Scenario: Невалідний підпис
    Given WayForPay надсилає вебхук з невалідним підписом
    When система перевіряє підпис
    Then вебхук відхиляється (логується, відповідь reject)
```

### Story 3 — Результат оплати

```gherkin
Feature: Сторінки результату оплати

  Scenario: Оплата успішна
    Given WayForPay завершив оплату успішно
    When менті повертається на /payments/success
    Then відображається сторінка успіху з деталями транзакції

  Scenario: Оплата відхилена
    Given WayForPay відхилив оплату
    When менті повертається на /payments/failure
    Then відображається причина відмови (reason) та можливість повтору
```

### Story 4 — Повернення адміном

```gherkin
Feature: Повернення коштів адміністратором

  Scenario: Успішне повернення
    Given Payment зі статусом APPROVED у Filament PaymentResource
    When адмін виконує дію Refund
    Then надсилається запит повернення до WayForPay
    And Payment.status → REFUNDED
    And MentorSession.is_paid = false
    And refunded_at та refund_amount заповнені

  Scenario: Повернення недоступне
    Given Payment НЕ має статусу APPROVED
    When адмін відкриває PaymentResource
    Then дія Refund недоступна (disabled)
```

### Story 5 — Надходження ментора

```gherkin
Feature: Історія надходжень ментора

  Scenario: Перегляд історії
    Given ментор має оплачені сесії
    When відкриває сторінку /payments/history
    Then відображається список з пагінацією
    And кожен рядок містить: сума/комісія/нетто/статус/дата
    And завантаження без N+1 запитів (eager loading)
```

---

## Data Model Sketch

### Payment (рефакторинг існуючої таблиці)

| Поле                 | Тип                   | Зміна                                 | Примітка                           |
| -------------------- | --------------------- | ------------------------------------- | ---------------------------------- |
| `id`                 | bigint PK             | —                                     |                                    |
| `mentor_session_id`  | FK                    | зберегти, але **переглянути cascade** | ризик втрати фінзаписів            |
| `order_reference`    | string                | **додати UNIQUE constraint**          | критично для ідемпотентності       |
| `amount`             | integer               | —                                     | kopiyky                            |
| `currency`           | string                | —                                     | UAH/USD/EUR/GBP                    |
| `transaction_status` | **PaymentStatusEnum** | змінити з string                      | PENDING/APPROVED/DECLINED/REFUNDED |
| `reason`             | string                | **nullable**                          | зараз NOT NULL — блокує ініціацію  |
| `reason_code`        | string                | **nullable**                          |                                    |
| `payment_system`     | string                | **nullable**                          |                                    |
| `card_type`          | string                | **nullable**                          |                                    |
| `issue_bank_name`    | string                | **nullable**                          |                                    |
| `fee_amount`         | integer               | **нове**                              | комісія платформи (kopiyky)        |
| `fee_percentage`     | decimal               | **нове**                              | % комісії                          |
| `net_amount`         | integer               | **нове**                              | нетто для ментора (kopiyky)        |
| `refunded_at`        | timestamp             | **нове**                              | nullable                           |
| `refund_amount`      | integer               | **нове**                              | nullable (kopiyky)                 |

### Нова enum: PaymentStatusEnum

```php
enum PaymentStatusEnum: string {
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DECLINED = 'declined';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';
}
```

### Зв'язки

- `Payment` belongsTo `MentorSession`
- `MentorSession` hasOne `Payment` (або hasMany якщо retry)
- `MentorSession` belongsTo `MentorProgram`

---

## API Contract Sketch

| Метод    | Endpoint             | Опис                                               | Auth         |
| -------- | -------------------- | -------------------------------------------------- | ------------ |
| `POST`   | `/payments/initiate` | Ініціювати оплату → дані віджета + order_reference | менті        |
| `POST`   | `/payments/webhook`  | WayForPay вебхук (CSRF-exempt, HMAC підписаний)    | —            |
| `GET`    | `/payments/success`  | Сторінка успіху (Inertia)                          | менті        |
| `GET`    | `/payments/failure`  | Сторінка невдачі (Inertia)                         | менті        |
| `GET`    | `/payments/history`  | Історія платежів (Inertia, пагінація)              | менті/ментор |
| Filament | `PaymentResource`    | list/view + Refund + CheckStatus                   | адмін        |

### Webhook payload (WayForPay → платформа)

```json
{
  "orderReference": "string",
  "transactionStatus": "Approved|Declined|Refunded|…",
  "reasonCode": "integer",
  "reason": "string",
  "paymentSystem": "string",
  "cardType": "string",
  "issuerBankName": "string",
  "merchantSignature": "string"
}
```

---

## Edge Cases & Error Scenarios

1. **Race condition:** паралельні вебхуки для одного `order_reference` → DB-lock
   або `firstOrCreate` з перевіркою статусу.
2. **Вебхук раніше за редірект:** стан визначається вебхуком, не редіректом.
3. **Підроблений підпис:** відхилити, залогувати, відповісти `reject`.
4. **Платіж успішний при скасованій сесії:** узгодити з
   `CalendarEventStatusEnum::PENDING_PAYMENT`.
5. **Повторне/подвійне повернення:** перевіряти статус APPROVED перед запитом до
   WFP.
6. **Розбіжність валют:** UAH-орієнтований WFP vs `CurrencyEnum` (USD/EUR/GBP) —
   потрібна конвертація або обмеження.
7. **Похибка float→kopiyky:** `MentorProgram.cost`/`MentorSession.cost` є float
   — конвертація з `CurrencyConverter`.
8. **Таймаут WFP API:** retry-механізм у черзі.
9. **`cascadeOnDelete` ризик:** видалення сесії знищує фінзапис — переглянути
   constraint.

---

## Risks & Dependencies

| Ризик                                           | Рівень    | Мітигація                                     |
| ----------------------------------------------- | --------- | --------------------------------------------- |
| Несумісна схема `payments`                      | Критичний | Рефакторинг у рамках scope                    |
| `cascadeOnDelete` на `mentor_session_id`        | Високий   | Розглянути `restrictOnDelete` або soft-delete |
| kopiyky vs float (MentorProgram/Session.cost)   | Середній  | Конвертація через `CurrencyConverter`         |
| Мультивалютність vs UAH-орієнтований WFP        | Середній  | Уточнити у stakeholders                       |
| Пакет `aratkruglik/wayforpay-laravel` (нішевий) | Середній  | Context7 + тестування                         |
| Redis-черга `payments` (нова черга)             | Низький   | Конфігурація в queue.php                      |

---

## Open Questions for Stakeholders — ВИРІШЕНІ

| #   | Питання                      | Відповідь                                                                                                                                                                       |
| --- | ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1   | Момент оплати в booking-flow | **Спочатку підтвердження → потім оплата.** Flow: ментор підтверджує → CalendarEvent: `CONFIRMED` → менті отримує змогу оплатити → `PENDING_PAYMENT` → після вебхука `APPROVED`. |
| 2   | Одиниця покупки              | **Обидва варіанти — програма і сесія.** Payment потребує **polymorphic**: `payable_type` / `payable_id` замість лише `mentor_session_id`.                                       |
| 3   | Валюта                       | **Будь-яка з `CurrencyEnum` (USD/EUR/GBP/UAH).** WayForPay приймає мультивалютні платежі — зберігаємо валюту відповідно до валюти програми/сесії.                               |

**Відкрито (не уточнено):**

- Модель комісії (%, фіксована, per-mentor) та її видимість.
- Зберігання фінзаписів після видалення (cascade vs restrict vs soft-delete).

---

## Estimated Complexity

**large** — XL Epic, 8 підзадач (#165–#172), новий платіжний шлюз + async
ідемпотентні вебхуки + рефакторинг схеми + новий enum + Filament + Inertia +
безпека + мультивалютність.

---

## Key Codebase Facts for Development Phase

| Файл                                                                         | Роль     | Дія                             |
| ---------------------------------------------------------------------------- | -------- | ------------------------------- |
| `app/Models/Payment.php`                                                     | існуючий | рефакторити схему               |
| `database/migrations/2025_02_13_144853_create_payments_table.php`            | існуюча  | нова міграція для змін          |
| `app/Models/MentorSession.php`                                               | існуючий | `is_paid`, `cost` (float)       |
| `app/Models/MentorProgram.php`                                               | існуючий | `cost` (float), `currency_id`   |
| `app/Enums/CurrencyEnum.php`                                                 | існуючий | мультивалютність                |
| `app/Support/CurrencyConverter.php`                                          | існуючий | конвертація float→kopiyky       |
| `app/Enums/CalendarEventStatusEnum.php`                                      | існуючий | `PENDING_PAYMENT` зарезервовано |
| `app/Actions/Calendar/CalendarEvent/CreateMentorSessionForCalendarEvent.php` | зразок   | патерн Actions                  |
| `app/Filament/Resources/User/UserResource.php`                               | зразок   | патерн Filament v4              |

**Пакет для встановлення:** `aratkruglik/wayforpay-laravel` (або
`maksa988/laravel-wayforpay`) — ще НЕ в composer.json.
