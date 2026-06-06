# Frontend Implementation Plan: Інтеграція платіжної системи WayForPay

> task_slug: `wayforpay-payment-integration` | aspect: frontend Мова наративу:
> uk | Версія плану: 1.0

---

## 1. Контекст і виявлені конвенції

### Технологічний стек

| Параметр        | Значення                                                                                     |
| --------------- | -------------------------------------------------------------------------------------------- |
| Package manager | yarn (v4.10.3)                                                                               |
| Inertia         | `@inertiajs/vue3` v2.2+                                                                      |
| Vue             | v3.5                                                                                         |
| TypeScript      | так — `typescript` в devDeps; нові сторінки пишуться з `lang="ts"`                           |
| UI library      | Headless UI (`@headlessui/vue`) + `@heroicons/vue` — **жодної іншої бібліотеки** не додавати |
| Styling         | Tailwind CSS v4 (utility-first, scoped-styles відсутні)                                      |

### Конвенція layout

Усі наявні сторінки (`MentorProgram/ListPage`, `Calendar/*`,
`UserSchedule/ListPage`) використовують **шаблонну обгортку**
`<AuthenticatedLayout>...</AuthenticatedLayout>`, а не
`defineOptions({ layout })`. Цю конвенцію зберігаємо у нових сторінках.

### Конвенція flash-нотифікацій

Cторінки використовують `ref(notification)` + `showNotification()` +
`watch(page.props.flash)` / `onMounted` та компонент `PopUp` з
`resources/js/Components/UI/Notifications/PopUp.vue`. Нові сторінки дотримуються
цього шаблону.

### Конвенція TypeScript

Старіші сторінки — runtime `defineProps({})`. Нові (починаючи з
`UserSchedule/ListPage`) — `<script setup lang="ts">` + `interface Props` +
`defineProps<Props>()`. Нові сторінки пишуться у TS-стилі.

### Наявна компонента пагінації

`resources/js/Components/Navigation/AppPagination.vue` очікує **raw Laravel
paginator** форму:

```ts
{ data: T[], links: Array<{url:string|null,label:string,active:boolean}>, prev_page_url: string|null, next_page_url: string|null }
```

---

## 2. Критичні проектні рішення

### 2.1 Pagination shape — критична залежність від бекенду

Backend-план описує `payments` як `LengthAwarePaginator<Payment>`.
`AppPagination.vue` споживає **raw paginator** (`prev_page_url`,
`next_page_url`, `links[]` з `{url,label,active}`), **не** API-resource shape
(`{data, links:{prev,next}, meta:{links[]}}`).

**Рішення:** бекенд має передавати результат `->paginate()` безпосередньо в
`Inertia::render()` без обгортки в resource collection. Frontend типізує
`payments` як `LaravelPaginator<PaymentRow>` та передає об'єкт до
`<AppPagination :data="payments" />`.

**Ризик:** якщо бекенд поверне resource-collection shape — `AppPagination`
потребуватиме адаптації або History-сторінка реалізує inline-пагінацію.

### 2.2 Ініціація оплати — механізм редіректу до WayForPay

Inertia використовує XHR-запити, тому звичайний HTTP 302 на зовнішній домін WFP
не спрацює. Бекенд має повертати `Inertia::location($wfpUrl)` (HTTP 409 +
`X-Inertia-Location` header), що змусить Inertia виконати
`window.location = url` — full page visit.

**Рішення для frontend:** кнопка "Оплатити" на сторінці booking-flow викликає
`router.post(route('payments.initiate'), { payable_type, payable_id })`.
Відповідь бекенду з `Inertia::location()` обробляється Inertia автоматично.

**Ризик:** якщо бекенд поверне widget URL як звичайний JSON/prop — frontend
потребує `window.location.assign(url)`. Потрібне підтвердження від
бекенд-розробника яким методом повертається URL.

### 2.3 Форматування суми (kopiyky → displayable)

Всі `amount`/`fee_amount`/`net_amount`/`refund_amount` — цілі числа в копійках.
Проекту бракує утиліти форматування грошей. Існує лише `useExternalCalendar.js`
у `Composables/`.

**Рішення:** створити `resources/js/Composables/useMoneyFormat.ts` з функцією
`formatMoney(kopiyky: number, currency: string): string`. Це дозволить
перевикористовувати форматування в усіх трьох сторінках.

### 2.4 Місце ініціації оплати — модифікація наявної сторінки

Задача каже, що ініціація відбувається з існуючих booking-flow сторінок.
`ShowEditCalendarEvent.vue` — найбільш ймовірне місце для кнопки "Оплатити",
оскільки відображає деталі підтвердженої сесії. Кнопка видима лише коли
`calendarEvent.status === 'pending_payment'` і `permissions` включає право
оплати.

---

## 3. Файли до створення

### 3.1 Сторінки Inertia

| Файл                                      | Призначення                                            |
| ----------------------------------------- | ------------------------------------------------------ |
| `resources/js/Pages/Payments/Success.vue` | Сторінка підтвердження успішної оплати                 |
| `resources/js/Pages/Payments/Failure.vue` | Сторінка відхиленої/закінченої оплати + кнопка повтору |
| `resources/js/Pages/Payments/History.vue` | Пагінований список платежів для менті/ментора          |

### 3.2 Composable

| Файл                                         | Призначення                              |
| -------------------------------------------- | ---------------------------------------- |
| `resources/js/Composables/useMoneyFormat.ts` | `formatMoney(kopiyky, currency): string` |

---

## 4. Файли до модифікації

| Файл                                                    | Що змінюється                                                                                                                                                  |
| ------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `resources/js/Pages/Calendar/ShowEditCalendarEvent.vue` | Додати кнопку "Оплатити" — видима коли `calendarEvent.status === 'pending_payment'` і є відповідний permission; `router.post(route('payments.initiate'), ...)` |

---

## 5. TypeScript типи

```ts
type PayableType = 'mentor_session' | 'mentor_program';
type PaymentStatus =
  | 'pending'
  | 'approved'
  | 'declined'
  | 'refunded'
  | 'expired';
type Currency = 'UAH' | 'USD' | 'EUR' | 'GBP';

interface Payable {
  type: PayableType;
  id: number;
  label: string;
}

interface PaymentBase {
  order_reference: string;
  amount: number;
  currency: Currency;
  transaction_status: PaymentStatus;
  created_at: string;
}

interface PaymentSuccess extends PaymentBase {
  payment_system: string | null;
}

interface PaymentFailure extends PaymentBase {
  reason: string | null;
  reason_code: string | null;
}

interface PaymentRow extends PaymentBase {
  id: number;
  fee_amount: number | null;
  fee_percentage: number | null;
  net_amount: number | null;
  payment_system: string | null;
  refunded_at: string | null;
  refund_amount: number | null;
  payable: Payable;
}

interface LaravelPaginatorLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface LaravelPaginator<T> {
  data: T[];
  links: LaravelPaginatorLink[];
  prev_page_url: string | null;
  next_page_url: string | null;
  current_page: number;
  last_page: number;
  total: number;
}

interface SuccessPageProps {
  payment: PaymentSuccess;
  payable: Payable;
}

interface FailurePageProps {
  payment: PaymentFailure;
  payable: Payable;
  retry_url: string | null;
}

interface HistoryPageProps {
  payments: LaravelPaginator<PaymentRow>;
  filters: {
    status: PaymentStatus | null;
    from: string | null;
    to: string | null;
  };
}
```

---

## 6. Детальний опис сторінок

### 6.1 `Payments/Success.vue`

**Props:** `SuccessPageProps`

**Поведінка:**

- Відображає: `order_reference`, форматована сума (`formatMoney`), валюта,
  `payment_system`, дата, назва `payable.label` + тип
- Flash success — `PopUp` (показати якщо `page.props.flash.success`)
- Кнопка "Перейти до сесій" → `<Link :href="route('pages.calendar.confirmed')">`
  (або до payable, якщо є відповідний маршрут)
- Іконка успіху: `CheckCircleIcon` з `@heroicons/vue`

**Layout:** `<AuthenticatedLayout>`

### 6.2 `Payments/Failure.vue`

**Props:** `FailurePageProps`

**Поведінка:**

- Відображає: `order_reference`, статус, `reason` (якщо є), `reason_code`,
  форматована сума, дата
- Кнопка "Спробувати ще раз" — видима лише коли `retry_url !== null`;
  реалізована як `<Link :href="retry_url">` (full-page navigate) або
  `router.post(retry_url)` залежно від backend contract — **уточнити**: якщо
  `retry_url` — Inertia-маршрут до `payments.initiate`, тоді `router.post`
- Кнопка "Повернутись до сесій" завжди присутня
- Flash error — `PopUp`
- Іконка помилки: `XCircleIcon` з `@heroicons/vue`

**Layout:** `<AuthenticatedLayout>`

### 6.3 `Payments/History.vue`

**Props:** `HistoryPageProps`

**Поведінка:**

- Фільтри: `status` (select), `from`/`to` (date inputs) —
  `useForm({ status, from, to })` →
  `form.get(route('payments.history'), { preserveState: true })`
- Таблиця/список: `order_reference`, `payable.label`, форматована сума,
  `fee_amount`/`net_amount` (якщо є, для менторів), статус-badge, `created_at`,
  `refunded_at`
- Статусні badge: `approved` → зелений, `declined`/`expired` → червоний,
  `pending` → жовтий, `refunded` → сірий (Tailwind utility classes)
- Пагінація: `<AppPagination :data="payments" />`
- Порожній стан: повідомлення якщо `payments.data.length === 0`
- Flash — `PopUp` + `watch(page.props.flash)`

**Layout:** `<AuthenticatedLayout>`

---

## 7. Composable `useMoneyFormat.ts`

```ts
export function useMoneyFormat() {
  const formatMoney = (kopiyky: number, currency: string): string => {
    const amount = kopiyky / 100;
    return new Intl.NumberFormat('uk-UA', {
      style: 'currency',
      currency,
      minimumFractionDigits: 2,
    }).format(amount);
  };

  return { formatMoney };
}
```

---

## 8. Модифікація `ShowEditCalendarEvent.vue`

Додати умовну кнопку "Оплатити" в секцію дій (поруч із кнопкою Edit/Delete).
Видима коли:

- `props.calendarEvent.status === 'pending_payment'`
- `props.permissions` містить право ініціації (backend передає `permissions` як
  рядок або масив — перевірити поточний контракт сторінки)

```vue
<button
  v-if="calendarEvent.status === 'pending_payment' && canPay"
  type="button"
  :disabled="paymentProcessing"
  @click="initiatePayment"
>
  Оплатити
</button>
```

```ts
const paymentProcessing = ref(false);

const initiatePayment = () => {
  paymentProcessing.value = true;
  router.post(
    route('payments.initiate'),
    {
      payable_type: 'mentor_session',
      payable_id: props.calendarEvent.mentor_session_id,
    },
    {
      onFinish: () => {
        paymentProcessing.value = false;
      },
    },
  );
};
```

**Ризик:** `calendarEvent.mentor_session_id` може бути відсутнім у поточному
props contract `ShowEditCalendarEvent`. Якщо бекенд не включає його — потрібно
або розширити props, або реалізувати через `payable_type: 'mentor_session'` + id
із `calendarEvent.id` якщо CalendarEvent === MentorSession (уточнити у
бекенд-розробника).

---

## 9. Ризики та залежності

| Ризик                                                                | Рівень    | Мітигація                                                                                        |
| -------------------------------------------------------------------- | --------- | ------------------------------------------------------------------------------------------------ |
| Pagination shape mismatch (resource vs raw paginator)                | Критичний | Явна домовленість з бекендом: передавати `->paginate()` без resource wrapper                     |
| `Inertia::location()` vs JSON url у відповіді на `payments.initiate` | Високий   | Підтвердити у бекенд-плані; frontend-код готується під `Inertia::location()`                     |
| `retry_url` — тип редіректу (full-page vs Inertia)                   | Середній  | Якщо `retry_url` → `/payments/initiate` (POST) — кнопка має бути form, не `<Link>`; уточнити     |
| `calendarEvent.mentor_session_id` відсутній у поточних props         | Середній  | Перевірити поточний props contract `ShowEditCalendarEvent`; backend може потребувати додати поле |
| `canPay` визначення — `permissions` prop формат                      | Низький   | Перевірити поточний contract; якщо `permissions: string` ('view'/'edit') — розширити enum        |
| Мультивалютна Intl.NumberFormat для GBP/EUR/USD                      | Низький   | `Intl.NumberFormat` підтримує всі 4 валюти нативно                                               |

---

## 10. Порядок імплементації

```
1. resources/js/Composables/useMoneyFormat.ts
2. resources/js/Pages/Payments/Success.vue
3. resources/js/Pages/Payments/Failure.vue
4. resources/js/Pages/Payments/History.vue
5. resources/js/Pages/Calendar/ShowEditCalendarEvent.vue  (кнопка оплати)
```

---

## 11. Convention Skills для виклику при імплементації

- `vue-plugin:vue-conventions` — SFC structure, naming, scoped styles
- `vue-plugin:vue-forms` — `useForm()` для History filters
- `js-foundation:typescript-patterns` — interface definitions, generic types
