# WayForPay Payment Integration — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to
> implement this plan task-by-task.

**Goal:** Integrate WayForPay payment gateway to allow mentees to pay for mentor
sessions directly on the platform, with platform commission tracking and admin
refund capabilities.

**Architecture:** Actions pattern (`lorisleiva/laravel-actions`) throughout.
Single merchant account (platform) for Phase 1 — split payments deferred to
Phase 2. Webhook processing is async via queued jobs. All monetary values in
kopecks in `payments` table only; cost columns in other tables stay as decimal.

**Tech Stack:** Laravel 12, `aratkruglik/wayforpay-laravel` v1.2.1, Inertia.js +
Vue 3, Filament v4, PostgreSQL, Redis queues.

**GitHub Issue:** #144

---

## Scope Boundaries

### Phase 1 (this PR — in scope):

- Payment initiation: mentee clicks "Pay" → WayForPay checkout widget
- Webhook processing with idempotency guarantees
- Platform commission tracking (calculated and stored, NOT disbursed via
  WayForPay)
- Full refunds only (admin-initiated via Filament)
- Payment history pages for mentee (`/payments/history`) and mentor
  (`/payments/earnings`)
- Filament admin: list, view, refund action, check-status action

### Deferred to Phase 2:

- Split payments via WayForPay MMS marketplace API
- Mentor merchant account onboarding
- Partial refunds
- CSV export
- Automated stale payment cleanup (scheduled job)

---

## Phase 1 — Foundation

### Task 1: Install package + publish config

**Files:**

- Modify: `composer.json`
- Create: `config/payment.php`

**Step 1:** Install package

```bash
docker compose exec app composer require aratkruglik/wayforpay-laravel
docker compose exec app php artisan vendor:publish --tag=wayforpay-config
```

**Step 2:** Inspect the package source to understand actual API:

```bash
cat vendor/aratkruglik/wayforpay-laravel/src/Facades/WayForPay.php
ls vendor/aratkruglik/wayforpay-laravel/src/Http/Controllers/
cat vendor/aratkruglik/wayforpay-laravel/src/Events/
```

Note exact facade method signatures, webhook controller class name, and event
class name — adapt subsequent tasks accordingly.

**Step 3:** Create `config/payment.php`:

```php
<?php

declare(strict_types=1);

return [
    'platform_commission_percent' => (int) env('PLATFORM_COMMISSION_PERCENT', 10),
    'default_currency' => env('PAYMENT_CURRENCY', 'UAH'),
    'queue' => env('PAYMENT_QUEUE', 'payments'),
];
```

**Step 4:** Add to `.env.example`:

```
WAYFORPAY_MERCHANT_ACCOUNT=
WAYFORPAY_MERCHANT_SECRET_KEY=
WAYFORPAY_MERCHANT_DOMAIN_NAME=
PLATFORM_COMMISSION_PERCENT=10
PAYMENT_CURRENCY=UAH
PAYMENT_QUEUE=payments
```

**Step 5:** Commit

```bash
git add composer.json composer.lock config/payment.php config/wayforpay.php .env.example
git commit -m "feat: install aratkruglik/wayforpay-laravel and add payment config"
```

---

### Task 2: PaymentStatusEnum with state machine

**Files:**

- Create: `app/Enums/PaymentStatusEnum.php`

**Step 1:** Write failing test `tests/Unit/Enums/PaymentStatusEnumTest.php`:

```php
<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;

describe('PaymentStatusEnum state machine', function (): void {
    it('allows valid transitions from Pending', function (): void {
        $pending = PaymentStatusEnum::Pending;
        expect($pending->canTransitionTo(PaymentStatusEnum::Processing))->toBeTrue()
            ->and($pending->canTransitionTo(PaymentStatusEnum::Approved))->toBeTrue()
            ->and($pending->canTransitionTo(PaymentStatusEnum::Declined))->toBeTrue()
            ->and($pending->canTransitionTo(PaymentStatusEnum::Expired))->toBeTrue()
            ->and($pending->canTransitionTo(PaymentStatusEnum::Refunded))->toBeFalse();
    });

    it('allows valid transitions from Approved', function (): void {
        $approved = PaymentStatusEnum::Approved;
        expect($approved->canTransitionTo(PaymentStatusEnum::Refunded))->toBeTrue()
            ->and($approved->canTransitionTo(PaymentStatusEnum::Declined))->toBeFalse()
            ->and($approved->canTransitionTo(PaymentStatusEnum::Expired))->toBeFalse();
    });

    it('blocks all transitions from terminal states', function (): void {
        foreach ([PaymentStatusEnum::Declined, PaymentStatusEnum::Refunded, PaymentStatusEnum::Expired] as $terminal) {
            foreach (PaymentStatusEnum::cases() as $target) {
                expect($terminal->canTransitionTo($target))->toBeFalse();
            }
        }
    });
});
```

**Step 2:** Run test to verify it fails:

```bash
docker compose exec app php artisan test tests/Unit/Enums/PaymentStatusEnumTest.php
```

Expected: FAIL (class not found)

**Step 3:** Create `app/Enums/PaymentStatusEnum.php`:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatusEnum: string
{
    case Pending    = 'Pending';
    case Processing = 'Processing';
    case Approved   = 'Approved';
    case Declined   = 'Declined';
    case Refunded   = 'Refunded';
    case Expired    = 'Expired';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending    => in_array($target, [self::Processing, self::Approved, self::Declined, self::Expired], true),
            self::Processing => in_array($target, [self::Approved, self::Declined, self::Expired], true),
            self::Approved   => $target === self::Refunded,
            self::Declined,
            self::Refunded,
            self::Expired    => false,
        };
    }

    public static function fromWayForPay(string $status): self
    {
        return match ($status) {
            'Approved'                          => self::Approved,
            'Declined', 'Canceled'              => self::Declined,
            'Refunded', 'Voided'               => self::Refunded,
            'Expired'                           => self::Expired,
            'InProcessing', 'WaitingAuthComplete' => self::Processing,
            default                             => self::Pending,
        };
    }
}
```

**Step 4:** Run tests:

```bash
docker compose exec app php artisan test tests/Unit/Enums/PaymentStatusEnumTest.php
```

Expected: PASS

**Step 5:** Commit:

```bash
git add app/Enums/PaymentStatusEnum.php tests/Unit/Enums/PaymentStatusEnumTest.php
git commit -m "feat: add PaymentStatusEnum with state machine and WayForPay status mapping"
```

---

### Task 3: Database migration — alter payments table

**Files:**

- Create:
  `database/migrations/YYYY_MM_DD_HHMMSS_update_payments_table_for_wayforpay.php`

**Step 1:** Create migration:

```bash
docker compose exec app php artisan make:migration update_payments_table_for_wayforpay --table=payments
```

**Step 2:** Fill migration content:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->integer('platform_fee')->nullable()->after('amount');
            $table->string('reason')->nullable()->change();
            $table->string('reason_code')->nullable()->change();
            $table->string('payment_system')->nullable()->change();
            $table->string('card_type')->nullable()->change();
            $table->string('issue_bank_name')->nullable()->change();
            $table->unique('order_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['order_reference']);
            $table->dropColumn('platform_fee');
            $table->string('reason')->nullable(false)->change();
            $table->string('reason_code')->nullable(false)->change();
            $table->string('payment_system')->nullable(false)->change();
            $table->string('card_type')->nullable(false)->change();
            $table->string('issue_bank_name')->nullable(false)->change();
        });
    }
};
```

**Step 3:** Run migration:

```bash
docker compose exec app php artisan migrate
```

**Step 4:** Update `app/Models/Payment.php`:

- Add `'platform_fee'` to `$fillable`
- Change cast `'transaction_status'` from `'string'` to
  `PaymentStatusEnum::class`
- Add `'platform_fee' => 'int'` to casts
- Add `refundedBy` BelongsTo relationship

**Step 5:** Update `database/factories/PaymentFactory.php`:

- Replace `Currency::factory()` with `'UAH'` for currency
- Add `'platform_fee' => fake()->numberBetween(0, 50)`
- Use `PaymentStatusEnum::Pending` for `transaction_status`
- Make nullable fields nullable in factory

**Step 6:** Run Pint + PHPStan:

```bash
docker compose exec app ./vendor/bin/pint
docker compose exec app ./vendor/bin/phpstan analyse
```

**Step 7:** Commit:

```bash
git add database/migrations/ app/Models/Payment.php database/factories/PaymentFactory.php
git commit -m "feat: alter payments table — add platform_fee, nullable fields, unique order_reference"
```

---

## Phase 2 — Core Payment Flow

### Task 4: PaymentPolicy

**Files:**

- Create: `app/Policies/PaymentPolicy.php`

**Step 1:** Write failing test `tests/Unit/Policies/PaymentPolicyTest.php`:

```php
it('allows mentee to create payment for their unpaid session', fn() =>
    expect((new PaymentPolicy())->create($mentee, $session))->toBeTrue()
);
it('denies if session already paid', fn() => /* ... */);
it('denies if session is cancelled', fn() => /* ... */);
it('denies if user is not the mentee', fn() => /* ... */);
```

**Step 2:** Create `app/Policies/PaymentPolicy.php`:

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MentorSession;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function create(User $user, MentorSession $mentorSession): bool
    {
        return $user->getKey() === $mentorSession->menti_id
            && !$mentorSession->is_cancelled
            && !$mentorSession->is_paid;
    }

    public function view(User $user, Payment $payment): bool
    {
        $session = $payment->mentorSession;
        return $user->getKey() === $session->mentor_id
            || $user->getKey() === $session->menti_id;
    }
}
```

**Step 3:** Register in `app/Providers/AuthServiceProvider.php` (or use
`#[BeforeAll]` if Laravel auto-discovery).

**Step 4:** Run tests + Pint + PHPStan. Commit.

---

### Task 5: InitiatePaymentAction

**Files:**

- Create: `app/Actions/Payment/InitiatePaymentAction.php`
- Create: `app/Http/Requests/Payment/InitiatePaymentRequest.php`

**IMPORTANT:** Before writing this action, inspect the package's actual facade
API:

```bash
cat vendor/aratkruglik/wayforpay-laravel/src/Facades/WayForPay.php
cat vendor/aratkruglik/wayforpay-laravel/src/WayForPayService.php
```

Adapt the `WayForPay::purchase(...)` call to the actual signature.

**Logic:**

1. Authorize: `$this->authorize('create', [$mentorSession])` (via PaymentPolicy)
2. Expire stale payments:
   `Payment::query()->where('mentor_session_id', $mentorSession->getKey())->whereIn('transaction_status', [PaymentStatusEnum::Pending, PaymentStatusEnum::Processing])->update(['transaction_status' => PaymentStatusEnum::Expired])`
3. Generate: `$orderReference = 'MW-' . Str::ulid()`
4. Convert cost: `$amountKopecks = (int) round($mentorSession->cost * 100)`
5. Platform fee:
   `$platformFee = (int) round($amountKopecks * config('payment.platform_commission_percent') / 100)`
6. Create Payment record with status `Pending`
7. Call WayForPay facade to get checkout HTML
8. Return Inertia response with checkout HTML

**Step:** Write test, implement, run tests, Pint, PHPStan, commit.

---

### Task 6: HandlePaymentStatusChange action + events

**Files:**

- Create: `app/Actions/Payment/HandlePaymentStatusChange.php`
- Create: `app/Events/PaymentStatusChanged.php`
- Create: `app/Events/PaymentWebhookFailed.php`

**Critical implementation detail — pessimistic locking:**

```php
public function handle(string $orderReference, string $wayforpayStatus, array $webhookData): void
{
    $newStatus = PaymentStatusEnum::fromWayForPay($wayforpayStatus);

    DB::transaction(function () use ($orderReference, $newStatus, $webhookData): void {
        $payment = Payment::query()
            ->where('order_reference', $orderReference)
            ->lockForUpdate()
            ->first();

        if ($payment === null) {
            Log::warning('Payment not found for webhook', ['order_reference' => $orderReference]);
            return;
        }

        if (!$payment->transaction_status->canTransitionTo($newStatus)) {
            return; // Idempotent — already in this or later state
        }

        $payment->update([
            'transaction_status' => $newStatus,
            'reason'             => $webhookData['reason'] ?? null,
            'reason_code'        => $webhookData['reasonCode'] ?? null,
            'payment_system'     => $webhookData['paymentSystem'] ?? null,
            'card_type'          => $webhookData['cardType'] ?? null,
            'issue_bank_name'    => $webhookData['issuerBankName'] ?? null,
        ]);

        if ($newStatus === PaymentStatusEnum::Approved) {
            $payment->mentorSession()->update(['is_paid' => true]);
        }

        if ($newStatus === PaymentStatusEnum::Refunded) {
            $payment->mentorSession()->update(['is_paid' => false]);
        }

        PaymentStatusChanged::dispatch($payment);
    });
}
```

**Step:** Write test (mock DB, test state transitions), implement, run tests,
Pint, PHPStan, commit.

---

### Task 7: Webhook processing (Job + Listener)

**Files:**

- Create: `app/Jobs/ProcessPaymentWebhookJob.php`
- Create: `app/Listeners/DispatchPaymentWebhookJob.php`

**ProcessPaymentWebhookJob:**

```php
class ProcessPaymentWebhookJob implements ShouldQueue, ShouldBeUnique
{
    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly string $orderReference,
        public readonly string $transactionStatus,
        public readonly array $webhookData,
    ) {}

    public function uniqueId(): string
    {
        return $this->orderReference;
    }

    public function handle(): void
    {
        HandlePaymentStatusChange::run($this->orderReference, $this->transactionStatus, $this->webhookData);
    }

    public function failed(\Throwable $exception): void
    {
        PaymentWebhookFailed::dispatch($this->orderReference, $exception->getMessage());
    }
}
```

**DispatchPaymentWebhookJob listener:** Register in `EventServiceProvider` to
listen to the package's callback event. Inspect the package's event class to
know exact name and data structure.

**Webhook route:** After inspecting package controller:

- If package controller returns correct WayForPay acknowledgment → use it
- If not → create `app/Actions/Payment/ProcessWebhookAction.php` (AsController)
  that validates signature, dispatches job, returns acknowledgment JSON

**Step:** Implement, register in EventServiceProvider (or AppServiceProvider),
add to routes/web.php, run tests, Pint, PHPStan, commit.

---

### Task 8: Payment success/failed pages

**Files:**

- Create: `app/Actions/Pages/Payment/PaymentSuccessPage.php`
- Create: `app/Actions/Pages/Payment/PaymentFailedPage.php`
- Create: `resources/js/Pages/Payment/SuccessPage.vue`
- Create: `resources/js/Pages/Payment/FailedPage.vue`

**Routes:**

```php
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('payment/success/{payment:order_reference}', PaymentSuccessPage::class)
        ->name('payment.success');
    Route::get('payment/failed/{payment:order_reference}', PaymentFailedPage::class)
        ->name('payment.failed');
});
```

**SuccessPage.vue:** Show amount, date, program name, status badge. If status is
still Pending, show "Awaiting bank confirmation" with auto-refresh (poll every
5s for 60s).

**FailedPage.vue:** Show reason (human-readable), "Try Again" button that
re-initiates payment.

**Step:** Implement, run tests, Pint, PHPStan, commit.

---

## Phase 3 — History & Admin

### Task 9: Payment history pages

**Files:**

- Create: `app/Actions/Pages/Payment/ShowMenteePaymentHistoryPage.php`
- Create: `app/Actions/Pages/Payment/ShowMentorPaymentHistoryPage.php`
- Create: `resources/js/Pages/Payment/HistoryPage.vue`
- Create: `resources/js/Pages/Payment/EarningsPage.vue`

**Routes:**

```php
Route::middleware(['auth', 'verified'])->prefix('payments')->group(function (): void {
    Route::get('history', ShowMenteePaymentHistoryPage::class)->name('payments.history');
    Route::get('earnings', ShowMentorPaymentHistoryPage::class)->name('payments.earnings');
});
```

**Mentee query:**

```php
Payment::query()
    ->whereHas('mentorSession', fn($q) => $q->where('menti_id', Auth::id()))
    ->with(['mentorSession.mentor', 'mentorSession.mentorProgram'])
    ->latest()
    ->paginate(15)
```

**Mentor query:**

```php
Payment::query()
    ->whereHas('mentorSession', fn($q) => $q->where('mentor_id', Auth::id()))
    ->with(['mentorSession.menti', 'mentorSession.mentorProgram'])
    ->latest()
    ->paginate(15)
```

**Step:** Implement actions, Vue pages with status badges (colored), pagination,
empty states. Run tests, Pint, PHPStan, commit.

---

### Task 10: RefundPaymentAction + CheckPaymentStatusAction

**Files:**

- Create: `app/Actions/Payment/RefundPaymentAction.php`
- Create: `app/Actions/Payment/CheckPaymentStatusAction.php`

**RefundPaymentAction:**

```php
public function handle(Payment $payment, string $reason): void
{
    if ($payment->transaction_status !== PaymentStatusEnum::Approved) {
        throw new \LogicException('Only Approved payments can be refunded.');
    }

    // Call WayForPay refund API (inspect package for exact method)
    WayForPay::refund($payment->order_reference, $payment->amount / 100, 'UAH', $reason);

    DB::transaction(function () use ($payment): void {
        $payment->update(['transaction_status' => PaymentStatusEnum::Refunded]);
        $payment->mentorSession()->update(['is_paid' => false]);
        PaymentStatusChanged::dispatch($payment);
    });
}
```

**Step:** Implement, test, commit.

---

### Task 11: Filament PaymentResource

**Files:**

- Create: `app/Filament/Resources/Payment/PaymentResource.php`
- Create: `app/Filament/Resources/Payment/Tables/PaymentsTable.php`
- Create: `app/Filament/Resources/Payment/Schemas/PaymentForm.php`
- Create: `app/Filament/Resources/Payment/Pages/ListPayments.php`
- Create: `app/Filament/Resources/Payment/Pages/ViewPayment.php`

Follow existing `app/Filament/Resources/User/` pattern exactly.

**PaymentsTable columns:** order_reference, amount (formatted from kopecks:
`number_format($record->amount / 100, 2)` + ' UAH'), status (badge with color by
enum), mentee name, mentor name, created_at.

**Filters:** by PaymentStatusEnum, date range.

**ViewPayment custom actions:**

1. `CheckStatusAction` — visible when status is Pending/Processing. Calls
   `CheckPaymentStatusAction::run($payment)`. Shows success/error notification.
2. `RefundAction` — visible only when status is Approved. Opens confirmation
   modal with required reason input. Calls
   `RefundPaymentAction::run($payment, $reason)`.

**Step:** Implement, test with Livewire tests, Pint, PHPStan, commit.

---

### Task 12: Routes + EventServiceProvider registration

**Files:**

- Modify: `routes/web.php`
- Modify: `app/Providers/EventServiceProvider.php` (or `AppServiceProvider.php`)
- Modify: `bootstrap/app.php` (queue workers: add `payments` queue)

**All payment routes:**

```php
// Mentee checkout
Route::middleware(['auth', 'role:menti'])->group(function (): void {
    Route::post('payment/checkout/{mentorSession}', InitiatePaymentAction::class)
        ->name('payment.checkout');
});

// Success/failed redirect pages
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('payment/success/{payment:order_reference}', PaymentSuccessPage::class)
        ->name('payment.success');
    Route::get('payment/failed/{payment:order_reference}', PaymentFailedPage::class)
        ->name('payment.failed');
});

// History pages
Route::middleware(['auth', 'verified'])->prefix('payments')->group(function (): void {
    Route::get('history', ShowMenteePaymentHistoryPage::class)->name('payments.history');
    Route::get('earnings', ShowMentorPaymentHistoryPage::class)->name('payments.earnings');
});

// Webhook — no auth, no CSRF (add to VerifyCsrfToken exceptions)
Route::post('wayforpay/callback', /* WebhookController or ProcessWebhookAction */)->name('wayforpay.webhook');
```

**CSRF exception:** Add `wayforpay/callback` to
`app/Http/Middleware/VerifyCsrfToken.php` `$except` array.

**Step:** Add routes, register events/listeners, run full test suite, Pint,
PHPStan, commit.

---

### Task 13: Final validation

**Step 1:** Run full test suite:

```bash
docker compose exec app php artisan test
```

**Step 2:** Run PHPStan:

```bash
docker compose exec app ./vendor/bin/phpstan analyse
```

**Step 3:** Run Pint:

```bash
docker compose exec app ./vendor/bin/pint
```

**Step 4:** Verify routes registered:

```bash
docker compose exec app php artisan route:list | grep payment
```

**Step 5:** Commit any final fixes.

---

## Key Risks for Developer

1. **Package API surface** — inspect the package source after `composer require`
   before implementing Actions. The plan uses `WayForPay::purchase(...)`,
   `WayForPay::refund(...)`, `WayForPay::checkStatus(...)` as assumed method
   names. Adapt to actual signatures.
2. **Webhook acknowledgment format** — WayForPay expects
   `{"orderReference": "...", "status": "accept", "time": ..., "signature": "..."}`.
   Verify whether package controller handles this automatically.
3. **WayForPay test credentials** — without `.env` credentials, payment
   initiation will fail. Use mock/fake in tests.
4. **CSRF** — webhook endpoint must be excluded from CSRF verification.
5. **Queue worker** — ensure `payments` queue is processed (add to
   `docker-compose.yml` queue worker command or use
   `php artisan queue:work --queue=payments`).
