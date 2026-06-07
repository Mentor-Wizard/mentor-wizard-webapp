# Security Review: WayForPay Payment Integration

> task_slug: `wayforpay-payment-integration` | reviewer: security-scanner |
> date: 2026-06-07

## Summary

- Critical: 0
- High: 0
- Medium: 6 (5 documented as recommendations, 1 fixed inline)
- Out of scope (Low/Info): 2

No live Critical/High exploit path was found. The integration is fundamentally
sound:

- **Amount is server-authoritative** —
  `InitiatePaymentAction::resolvePayableDetails()` reads `cost` from the
  DB-loaded `Payable` model; the client only supplies `payable_type` +
  `payable_id`. A user cannot tamper with the charged amount.
- **HMAC webhook signature is enforced** — the package
  `WayForPayService::handleWebhook()` validates `merchantSignature` with
  `hash_equals()` (timing-safe) before dispatching `WayForPayCallbackReceived`.
  An invalid signature returns HTTP 403 and never reaches
  `HandlePaymentWebhookJob`. Cannot be bypassed from application code.
- **Webhook idempotency is correct** —
  `HandlePaymentWebhookJob::processWebhook()` uses `lockForUpdate()` inside a DB
  transaction and short-circuits on `isTerminal()`. A replayed/duplicate webhook
  cannot re-trigger a state transition or double-mark a payable.
- **Merchant credentials** (`WAYFORPAY_MERCHANT_ACCOUNT`,
  `WAYFORPAY_SECRET_KEY`) are read only from `env()` via `config/wayforpay.php`;
  not hardcoded, not echoed to the client.
- **order_reference** is `Str::uuid()` (UUIDv4) — unpredictable, not enumerable.
- **CSRF placement is correct** — the webhook lives in `routes/api.php`
  (CSRF-exempt by design, protected by HMAC instead); all state-changing browser
  routes (`payments.initiate`) are in `routes/web.php` under the default `web`
  middleware group (CSRF active). The frontend retry uses `axios.post` which
  sends the `X-XSRF-TOKEN` cookie.
- **Gate + listener wiring verified** in `AppServiceProvider::boot()`:
  `Gate::define('initiate-payment', ...)`, `Gate::policy(Payment::class, ...)`,
  and `Event::listen(WayForPayCallbackReceived::class, ...)` are all present.
- **Card data** — only `card_type` and `issue_bank_name` are persisted
  (`HandlePaymentWebhookJob`). No PAN / CVV stored.
- **Frontend Blob retry** — the package `generateAutoSubmitForm()` emits a
  self-contained page with `htmlspecialchars(ENT_QUOTES)`-escaped inputs and a
  single inline `form.submit()` script; no external/cross-origin script loads.
  The Blob URL approach (`URL.createObjectURL` + `window.location.assign`)
  avoids `document.write`/`v-html`. No XSS vector introduced. No `v-html`
  anywhere in the new Vue pages.
- **returnUrl open redirect** — `returnUrl`/`serviceUrl` are set server-side to
  `route(...)` constants, never from user input. The package additionally
  validates scheme via `validateUrl()`. No open-redirect path.
- **Mass assignment** — every touched model (`Payment`, `MentorSession`,
  `MentorProgram`) declares an explicit `$fillable`. No `$guarded = []`.

---

## Critical findings (FIXED)

None.

## High findings (FIXED)

None.

---

## Medium findings

### 1. Sensitive webhook payload written to logs — FIXED — `app/Jobs/HandlePaymentWebhookJob.php:37`

**Issue (A09 Logging Failures):** On a missing `orderReference`, the job logged
the entire raw webhook payload (`['data' => $this->data]`). WayForPay webhook
payloads contain `merchantSignature` (an HMAC derived from the secret key) and
may contain `cardPan` (masked PAN). Writing these to application logs exposes
them to anyone with log access and weakens the secret over time.

**Fix applied:** Replaced the payload dump with
`['keys' => array_keys($this->data)]` — enough to diagnose a malformed request
without leaking signature or card data.

### 2. Refund / status-sync authorization is unwired — `app/Actions/Payments/RefundPaymentAction.php`, `app/Actions/Payments/CheckPaymentStatusAction.php`

**Issue (A01 Broken Access Control):** Answering the task's "is refund truly
admin-only?" — **not by itself.** `PaymentPolicy::refund()` (admin / super-admin
only) exists but is **never invoked anywhere in the codebase**. Both
`RefundPaymentAction::handle()` and `CheckPaymentStatusAction::handle()` perform
privileged operations with **no `Gate::authorize`** inside them.

**Why not Critical/High:** No route currently exposes either action — they are
intended for the Filament `PaymentResource` (deferred per backend phase). So
there is no live exploit path today.

**Recommended fix:** The Filament layer (or any future route) that exposes
refund/status MUST enforce `Gate::authorize('refund', $payment)`. Better: move
the authorization inside the actions themselves
(`Gate::authorize('refund', $payment)` at the top of
`RefundPaymentAction::handle()`) so the guarantee travels with the action
regardless of caller. Do not rely on the controller/Filament layer alone.

### 3. Payment Success/Failure pages have no ownership check — `app/Actions/Payments/PaymentSuccessPage.php:21`, `app/Actions/Payments/PaymentFailurePage.php:21`

**Issue (A01):** Both pages look up a `Payment` by `?orderReference=` from the
query string and render its details (amount, currency, reason, payable label)
with no `Gate::authorize('view', $payment)`. Any authenticated user who learns
another user's `order_reference` can view that payment.

**Why Medium, not higher:** `order_reference` is a UUIDv4 — not
enumerable/guessable, so practical exploitability is low. The exposed data is
non-sensitive (no card data, no PII beyond a session label).

**Why deferred (not fixed inline):** `PaymentPolicy::view()` currently returns
`false` for any payable that is not a `MentorSession` (no `MentorProgram`
branch). Naively wiring `Gate::authorize('view', ...)` onto these pages would
403 every legitimate **program**-payment success page. A correct fix requires
updating the policy AND both pages together — out of scope for an inline patch.
Recommended fix: add a `MentorProgram` branch to `PaymentPolicy::view()` (e.g.
payer/mentor match), then call `Gate::authorize('view', $payment)` in both page
actions.

### 4. No rate limiting on payment initiation — `routes/web.php:199`

**Issue (A04 Insecure Design):** `POST payments/initiate` has no `throttle`
middleware. Each call creates a PENDING `Payment` row and an outbound WayForPay
form. An authenticated user can spam the endpoint, flooding the `payments` table
and the retry path. Other sensitive routes in this app already use named
throttles (`chat-send`, `calendar-connect`, etc.).

**Recommended fix:** Add a dedicated limiter, e.g.
`RateLimiter::for('payments-initiate', fn ($r) => Limit::perMinute(10)->by($r->user()?->getKey()))`
in `AppServiceProvider`, and `->middleware('throttle:payments-initiate')` on the
route.

### 5. No guard against re-paying an already-paid payable — `app/Actions/Payments/InitiatePaymentAction.php:38`

**Issue (A04 Insecure Design):** `InitiatePaymentAction` always creates a fresh
PENDING `Payment` regardless of whether the `Payable` already has an APPROVED
payment. A user could initiate (and complete) payment twice for the same
`MentorSession`. Not a privilege/integrity break on its own, but it enables
accidental double charges and complicates reconciliation/refunds.

**Recommended fix:** Before creating the Payment, check for an existing
non-terminal-or-approved payment on the payable (e.g.
`$payable->payment()->whereIn('transaction_status', [PENDING, APPROVED])->exists()`)
and short-circuit / reuse. Pair with the `PaymentPolicy::initiate()` check.

### 6. `WAYFORPAY_DEBUG` enables request/response logging — `config/wayforpay.php:40`

**Issue (A05 Security Misconfiguration):** The package config exposes a `debug`
flag that "logs requests and responses." If enabled in production it would write
merchant requests (containing signatures) and responses to logs.

**Recommended fix:** Ensure `WAYFORPAY_DEBUG` is unset / `false` in production
`.env`, and document this in `.env.example`. Consider asserting it is false when
`app()->isProduction()`.

---

## Observations (not findings)

- **`PaymentPolicy::initiate()` asymmetry** — for `MentorSession` it strictly
  checks `menti_id === user` plus a CONFIRMED/PENDING_PAYMENT calendar-event
  status; for `MentorProgram` it returns
  `$payable->mentor_id !== $user->getKey()` (anyone except the program's own
  mentor may buy it). This is the **intended** open-marketplace behaviour for
  program purchases — do NOT change `!==` to `===`, which would break all
  program purchases. Noted only because the two branches enforce very different
  strictness; confirm with BA that program purchase needs no further eligibility
  checks.
- **EXPIRED webhook does not mark payable unpaid** —
  `HandlePaymentWebhookJob::syncPayableStatus()` only acts on APPROVED/DECLINED.
  Note that `PaymentStatusEnum::fromWayForPay()` maps WayForPay `Expired` to
  `DECLINED` anyway, so an expired transaction does flip to unpaid; the
  `EXPIRED` enum case is effectively unreachable from webhooks. Behavioural, not
  a security issue.

---

## Out of scope (Low / Info)

- **Polymorphic FK has no DB-level integrity** (noted by backend phase) —
  morphTo columns are not enforced by PostgreSQL. Application-level only.
  Hardening recommendation, no exploit path.
- **`InitiatePaymentRequest::authorize()` returns `true`** — acceptable because
  the real check is `Gate::authorize('initiate-payment', $payable)` inside the
  action after the model is resolved. Info only. </content> </invoke>
