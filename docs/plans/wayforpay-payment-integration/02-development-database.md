# Database Phase: WayForPay Payment Integration

## Migrations elaborated

- `database/migrations/2026_06_06_000001_alter_payments_table_for_wayforpay.php`
  - Dropped: `mentor_session_id` FK + column
  - Added: `payable_type` (varchar NOT NULL), `payable_id` (bigint NOT NULL)
  - Added: `fee_amount` (integer nullable), `fee_percentage` (decimal 5,4
    nullable), `net_amount` (integer nullable)
  - Added: `refunded_at` (timestamp nullable), `refund_amount` (integer
    nullable)
  - Changed to nullable: `reason`, `reason_code`, `payment_system`, `card_type`,
    `issue_bank_name`
  - Indexes: composite `(payable_type, payable_id)`, `transaction_status`,
    `created_at`
  - UNIQUE on `order_reference` was pre-existing — `->unique()->change()`
    removed to avoid duplicate constraint error on re-migrate
  - Note: no DB-level FK on polymorphic columns (standard Laravel pattern)

## Factories created/updated

- `database/factories/PaymentFactory.php`
  - Replaced `mentor_session_id` with `payable_type / payable_id` (defaults to
    `MentorSession`)
  - `order_reference` uses `fake()->uuid()` for uniqueness
  - `amount` in kopiyky range (10000–500000)
  - `transaction_status` defaults to `PaymentStatusEnum::PENDING`
  - All nullable response fields set to `null` in base state
  - States: `approved()`, `declined()`, `refunded()`

## Seeders

none

## Migration run results

- migrate: success (1 migration applied)
- rollback test: success (`down()` reverses cleanly)
- migrate (re-apply): success

## Schema verification

```
public.payments (19 columns)

Columns:
  id                  bigint, autoincrement
  order_reference     varchar(255)
  amount              integer
  currency            varchar(3)
  transaction_status  varchar(255)
  reason              varchar(255), nullable
  reason_code         varchar(255), nullable
  payment_system      varchar(255), nullable
  card_type           varchar(255), nullable
  issue_bank_name     varchar(255), nullable
  created_at          timestamp, nullable
  updated_at          timestamp, nullable
  payable_type        varchar(255)
  payable_id          bigint
  fee_amount          integer, nullable
  fee_percentage      numeric(5,4), nullable
  net_amount          integer, nullable
  refunded_at         timestamp, nullable
  refund_amount       integer, nullable

Indexes:
  payments_created_at_index               created_at               btree
  payments_order_reference_unique         order_reference          btree, unique
  payments_payable_type_payable_id_index  payable_type, payable_id btree, compound
  payments_pkey                           id                       btree, primary
  payments_transaction_status_index       transaction_status       btree
```
