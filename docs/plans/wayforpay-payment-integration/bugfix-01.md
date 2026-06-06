# Bugfix Report: WayForPay Payment Integration

> task_slug: `wayforpay-payment-integration` | phase: bugfix-01 | date:
> 2026-06-06

---

## BUG #1 — CRITICAL: PHP TypeError on transaction_status cast

**Root cause:** Eloquent casts `transaction_status` to `PaymentStatusEnum` via
`casts()`. Backed enum instances cannot be cast to string with `(string)` in PHP
8.1+ — this throws
`Error: Object of class App\Enums\PaymentStatusEnum could not be converted to string`.
The original code used
`PaymentStatusEnum::tryFrom((string) $payment->transaction_status)`, which is
both redundant (cast already returns the enum) and broken.

**Additional root cause:** PHPStan Level 7 was satisfied by the old pattern
because `@mixin IdeHelperPayment` typed `transaction_status` as `string`. After
removing the `tryFrom((string) ...)` wrapper, PHPStan began complaining that
`string` doesn't have an `isTerminal()` method or `->value` property. Fixed by
adding `@property PaymentStatusEnum|null $transaction_status` to `Payment` model
docblock to override the IdeHelper mixin's incorrect type.

### Files fixed

| File                                                | Change                                                                                                                                                          |
| --------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Models/Payment.php`                            | Added `@property PaymentStatusEnum\|null $transaction_status` to docblock                                                                                       |
| `app/Jobs/HandlePaymentWebhookJob.php`              | `PaymentStatusEnum::tryFrom((string) $payment->transaction_status)` → `$payment->transaction_status`                                                            |
| `app/Actions/Payments/RefundPaymentAction.php`      | Same replacement                                                                                                                                                |
| `app/Actions/Payments/CheckPaymentStatusAction.php` | Same replacement                                                                                                                                                |
| `app/Actions/Payments/PaymentSuccessPage.php`       | `PaymentStatusEnum::tryFrom((string) $payment->transaction_status)?->value` → `$payment->transaction_status?->value`; removed unused `PaymentStatusEnum` import |
| `app/Actions/Payments/PaymentFailurePage.php`       | Same replacement; removed unused `PaymentStatusEnum` import                                                                                                     |
| `app/Actions/Payments/ListPaymentHistoryAction.php` | Same `->value` replacement in `formatPayment()` (import kept — still used in `applyFilters()`)                                                                  |

---

## BUG #2 — HIGH: PaymentPolicy::initiate returns false for MentorProgram

**Root cause:** `PaymentPolicy::initiate()` only handled `MentorSession` and
returned `false` for all other `Payable` types, including `MentorProgram`. The
BA spec (Story 1) states that a menti can initiate payment for both programs and
sessions.

**Fix:** Added `MentorProgram` branch to `PaymentPolicy::initiate()`. Since
`MentorProgram` has no menti relationship (the buyer is any authenticated user
purchasing from a mentor), the policy allows initiation for any user who is not
the program's own mentor. This prevents mentors from paying themselves.

### File fixed

| File                             | Change                                                                                                                                          |
| -------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Policies/PaymentPolicy.php` | Added `use App\Models\MentorProgram`; added `if ($payable instanceof MentorProgram)` branch returning `$payable->mentor_id !== $user->getKey()` |

---

## Lint / Static Analysis

- **Pint**: clean (7 files + Payment model — 0 changes)
- **PHPStan level 7**: 0 errors after adding `@property` annotation to `Payment`

## Test Results

```
Tests: 137 passed (441 assertions)
Duration: 23.58s
```

All 39 payment-specific tests pass (CanaryTest, HandlePaymentWebhookJobTest,
PaymentPagesTest, PaymentStatusEnumTest, PaymentPolicyTest).
