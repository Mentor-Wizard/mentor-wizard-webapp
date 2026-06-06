# Laravel Implementation: WayForPay Payment Integration (Backend)

> task_slug: `wayforpay-payment-integration` | phase: backend | date: 2026-06-06

---

## Files Created

### New Files — Backend

| File                                                                           | Purpose                                                                                        |
| ------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------- |
| `app/Enums/PaymentStatusEnum.php`                                              | `PENDING/APPROVED/DECLINED/REFUNDED/EXPIRED` with `isTerminal()` and `fromWayForPay()` helpers |
| `app/Contracts/Payable.php`                                                    | Interface: `markPaid()`, `markUnpaid()`, `getPayableLabel()`                                   |
| `app/Support/CurrencyConverter.php`                                            | `toKopiyky(float): int` and `fromKopiyky(int): float`                                          |
| `app/Http/Requests/Payments/InitiatePaymentRequest.php`                        | Validates `payable_type` / `payable_id`; resolves Payable model                                |
| `app/Policies/PaymentPolicy.php`                                               | `initiate()`, `view()`, `refund()` authorization                                               |
| `app/Actions/Payments/InitiatePaymentAction.php`                               | Creates PENDING Payment, builds WFP Transaction, returns HTML form                             |
| `app/Actions/Payments/PaymentSuccessPage.php`                                  | Inertia `Payments/Success` page                                                                |
| `app/Actions/Payments/PaymentFailurePage.php`                                  | Inertia `Payments/Failure` page                                                                |
| `app/Actions/Payments/ListPaymentHistoryAction.php`                            | Inertia `Payments/History` page with pagination + filters                                      |
| `app/Actions/Payments/RefundPaymentAction.php`                                 | Admin refund via `WayForPay::refund()`                                                         |
| `app/Actions/Payments/CheckPaymentStatusAction.php`                            | Admin sync via `WayForPay::checkStatus()`                                                      |
| `app/Jobs/HandlePaymentWebhookJob.php`                                         | Idempotent webhook processing on queue `payments`                                              |
| `app/Listeners/DispatchPaymentWebhookJob.php`                                  | Thin listener dispatching the Job on `WayForPayCallbackReceived`                               |
| `database/migrations/2026_06_06_000001_alter_payments_table_for_wayforpay.php` | Stub migration (artisan-specialist elaborates)                                                 |
| `routes/api.php`                                                               | NEW file — `POST /api/payments/webhook` (CSRF-exempt)                                          |
| `config/wayforpay.php`                                                         | Published package config (reads `WAYFORPAY_*` env vars)                                        |

---

## Files Modified

| File                                   | Change                                                                                                                                    |
| -------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Models/Payment.php`               | Replaced `mentor_session_id` FK with `payable` morphTo; updated fillable/casts; added `@property` for `refunded_at`                       |
| `app/Models/MentorSession.php`         | Added `implements Payable`; replaced `hasOne(Payment)` with `morphOne`; added `markPaid/markUnpaid/getPayableLabel`                       |
| `app/Models/MentorProgram.php`         | Added `implements Payable`; added `morphOne(Payment)` + no-op `markPaid/markUnpaid` + `getPayableLabel`                                   |
| `app/Providers/AppServiceProvider.php` | Registered `PaymentPolicy` via `Gate::policy()`; registered `WayForPayCallbackReceived → DispatchPaymentWebhookJob` via `Event::listen()` |
| `bootstrap/app.php`                    | Added `api: __DIR__.'/../routes/api.php'` to `withRouting()`                                                                              |
| `routes/web.php`                       | Added `/payments/*` Inertia routes under `auth+verified` middleware                                                                       |
| `composer.json` / `composer.lock`      | `aratkruglik/wayforpay-laravel ^1.2` installed                                                                                            |

---

## Key Design Decisions

### 1. `purchase()` returns HTML, not a URL

The plan mentioned `Inertia::location()` redirect, but the package generates a
self-submitting HTML form. `InitiatePaymentAction` returns
`response($html, 200)` with `Content-Type: text/html`. The frontend must call
`POST /payments/initiate` and handle the HTML response (inject into DOM or
navigate to a dedicated route that renders it). Documented in Inertia contract
below.

### 2. Polymorphic cast issue — IdeHelper mixin

`Payment::transaction_status` is cast to `PaymentStatusEnum::class` in
`casts()`, but PHPStan reads `string` from `@mixin IdeHelperPayment`. All status
comparisons use
`PaymentStatusEnum::tryFrom((string) $payment->transaction_status)` to satisfy
PHPStan at level 7. This pattern must be kept until `ide-helper:models` is
re-run by artisan-specialist.

### 3. API routing was missing

`routes/api.php` did not exist, and `bootstrap/app.php` had no `api:` key in
`withRouting()`. Both were created. The webhook lives at
`POST /api/payments/webhook` — no CSRF (API middleware stack).

### 4. Named gate `initiate-payment` for polymorphic Payable authorization

`Gate::define('initiate-payment', [PaymentPolicy::class, 'initiate'])`
registered in `AppServiceProvider::boot()`. `InitiatePaymentAction` calls
`Gate::authorize('initiate-payment', $payable)`. A named gate bypasses Laravel's
policy-class resolution (which would look for `MentorSessionPolicy::initiate()`
or `MentorProgramPolicy::initiate()`), ensuring
`PaymentPolicy::initiate(User, Payable)` is always invoked regardless of the
concrete Payable type. `Gate::policy(Payment::class, PaymentPolicy::class)`
still covers `view` and `refund` (which receive `Payment` model instances).

### 5. `MentorProgram::markPaid()` is intentional no-op

Programs have no `is_paid` column. Payment at program level (e.g., booking a
full program upfront) doesn't currently flip any field. If this changes,
artisan-specialist must add an `is_paid` column to `mentor_programs`.

---

## Lint / Static Analysis

- **Pint**: clean (0 changes after final run)
- **PHPStan level 7**: 0 errors on all new/modified files

---

## Inertia Props Contract

### `Payments/Success` — `GET /payments/success?orderReference=<ref>`

```ts
{
  payment: {
    order_reference: string,
    amount: number,         // kopiyky integer
    currency: string,       // 'UAH' | 'USD' | 'EUR' | 'GBP'
    transaction_status: string | null,  // 'approved'
    payment_system: string | null,
    created_at: string,     // ISO-8601
  } | null,
  payable: {
    type: string,           // 'MentorSession' | 'MentorProgram'
    id: number,
    label: string,          // e.g. "06.06.2026 14:00" or program name
  } | null,
}
```

### `Payments/Failure` — `GET /payments/failure?orderReference=<ref>`

```ts
{
  payment: {
    order_reference: string,
    amount: number,
    currency: string,
    transaction_status: string | null,  // 'declined' | 'expired'
    reason: string | null,
    reason_code: string | null,
    created_at: string,
  } | null,
  payable: {
    type: string,
    id: number,
    label: string,
  } | null,
  retry_url: string | null,   // route('payments.initiate') for re-attempt
}
```

### `Payments/History` — `GET /payments/history`

```ts
{
  payments: {
    data: Array<{
      id: number,
      order_reference: string,
      amount: number,
      currency: string,
      transaction_status: string | null,
      fee_amount: number | null,
      fee_percentage: number | null,
      net_amount: number | null,
      payment_system: string | null,
      refunded_at: string | null,   // ISO-8601
      refund_amount: number | null,
      created_at: string,
      payable: {
        type: string,
        id: number,
        label: string,
      } | null,
    }>,
    links: Array<{ url: string|null, label: string, active: boolean }>,
    meta: {
      current_page: number,
      last_page: number,
      per_page: number,
      total: number,
      from: number | null,
      to: number | null,
    },
  },
  filters: {
    status: string | null,   // PaymentStatusEnum value e.g. 'approved'
    from: string | null,     // date string YYYY-MM-DD
    to: string | null,
  },
}
```

> **Important for frontend:** `POST /payments/initiate` returns an HTML form
> (not JSON or Inertia redirect). The frontend must handle this response by:
>
> - Option A: Full-page navigation — open the URL in an `<iframe>` or navigate
>   to a dedicated Blade view that renders the HTML
> - Option B: Inject the HTML into the DOM via `innerHTML` (the package form
>   auto-submits via JS)
> - Option C: Backend renders a dedicated Blade page — the Laravel action could
>   be routed to a Blade page that renders the HTML inline

---

## Artisan-Specialist Instructions

### MUST DO before any other phase can continue

1. **Run migration:** `php artisan migrate`
   - Migration `2026_06_06_000001_alter_payments_table_for_wayforpay.php` drops
     `mentor_session_id` FK and adds polymorphic columns.
   - **Add these indexes** (not in stub):
     `$table->index(['payable_type', 'payable_id'])`,
     `$table->index('transaction_status')`, `$table->index('created_at')`
   - **Add `restrictOnDelete()` constraint** on the polymorphic relation — note:
     polymorphic FKs are not enforced at DB level in MySQL/PostgreSQL by
     default; use application-level protection or soft deletes on payable
     models.

2. **Regenerate IDE helpers:** `php artisan ide-helper:models -W`
   - This fixes the `IdeHelperPayment` mixin that currently makes PHPStan see
     `transaction_status` as `string` instead of `PaymentStatusEnum`. Once
     regenerated, `PaymentStatusEnum::tryFrom()` workarounds in Actions may be
     simplified to direct `->value` access.

3. **Update `PaymentFactory`** (in `database/factories/PaymentFactory.php`):
   - Replace `mentor_session_id` with `payable_type` + `payable_id` (morphTo
     factory pattern)
   - Use `PaymentStatusEnum::PENDING` for `transaction_status`
   - Add `fee_amount`, `fee_percentage`, `net_amount`, `refunded_at`,
     `refund_amount` to factory

4. **Queue `payments`:** No `config/queue.php` changes needed —
   `onQueue('payments')` dispatches to the default Redis connection on the
   `payments` queue name. Ensure the queue worker runs with
   `php artisan queue:work --queue=payments`.

5. **Env variables required** (document in `.env.example`):

   ```
   WAYFORPAY_MERCHANT_ACCOUNT=
   WAYFORPAY_SECRET_KEY=
   WAYFORPAY_MERCHANT_DOMAIN=
   WAYFORPAY_TIMEOUT=30
   ```

6. **Filament `PaymentResource`** — out of scope for this backend phase. The
   `filament` agent should implement the admin resource using
   `RefundPaymentAction` and `CheckPaymentStatusAction` which are already in
   `app/Actions/Payments/`.

---

## Deviations from Plan

| #   | Plan stated                                                     | Actual implementation                                                    | Reason                                                                                                                     |
| --- | --------------------------------------------------------------- | ------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------- |
| 1   | `Inertia::location()` redirect from `InitiatePaymentAction`     | Returns plain `response($html)` with HTML                                | Package `purchase()` returns auto-submit HTML form, not a URL. The plan did not anticipate this.                           |
| 2   | `routes/api.php` assumed to exist                               | Created `routes/api.php` AND wired it in `bootstrap/app.php`             | File was absent; Laravel 12 requires explicit `api:` key in `withRouting()`                                                |
| 3   | `CurrencyConverter.php` described as "not on disk"              | Created fresh (git show confirmed NOT_STAGED)                            | File was staged in a prior commit but not on the working tree — created from scratch matching the described API            |
| 4   | `HandlePaymentWebhookAction.php` planned as thin HTTP handler   | Used package's built-in `WebhookController` in `routes/api.php` directly | Package ships a `WebhookController` that handles HMAC + dispatches event; no wrapper needed                                |
| 5   | `MentorProgram::markPaid()` described as "sessions()->update()" | Implemented as no-op                                                     | Updating all sessions' `is_paid` from a program-level payment would be incorrect if retry is attempted on a single session |

---

## Open Blockers / Questions

1. **Frontend HTML form handling:** The `Inertia::location()` approach from the
   plan is not viable because WFP returns HTML. Frontend architect needs to
   decide how to present the payment form (iframe, innerHTML injection,
   dedicated Blade route).

2. **`returnUrl` GET redirect confirmed:** WayForPay redirects the browser back
   to `returnUrl` via GET with `orderReference` in the query string (the
   `serviceUrl` receives the server-side POST webhook separately).
   Success/Failure page routes are GET routes reading `?orderReference` — this
   is correct.

3. **Commission/fee calculation:** `fee_amount`, `fee_percentage`, `net_amount`
   columns exist but `InitiatePaymentAction` sets them to `null`. The fee model
   (% vs fixed) is not defined per BA open questions. Tester should treat fee
   fields as optional.
