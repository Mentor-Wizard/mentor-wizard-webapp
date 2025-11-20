# План робіт: WayForPay інтеграція (Factory + Strategy)

## Огляд

Реалізація інтеграції з WayForPay використовуючи гнучку архітектуру (Factory +
Strategy) для майбутньої підтримки інших платіжних систем (LiqPay, Stripe).

### Поточний стан ✅

**Вже реалізовано:**

- ✅ `PaymentRequestDataDTO` - повний набір полів для purchase/refund/regular
  payments
- ✅ `PaymentResponseDataDTO` - повна відповідь з усіма полями WayForPay
- ✅ `PaymentGatewayInterface` - інтерфейс для всіх gateway
- ✅ `PaymentGatewayEnum` - enum з методами isEnabled(), available(), default()
- ✅ `PaymentStatusEnum` - статуси з label() та color()
- ✅ `PaymentTypeEnum` - типи платежів (Purchase, Refund, Regular)
- ✅ `Payment` Model - з усіма зв'язками та полями
- ✅ `config/payment.php` - конфігурація для WayForPay і LiqPay
- ✅ Міграції - повна структура таблиці payments

**Треба реалізувати:**

- ❌ `WayForPayGateway` - конкретна реалізація для WayForPay SDK
- ❌ `PaymentGatewayFactory` - фабрика для створення gateway
- ❌ `PaymentServiceProvider` - deferred provider
- ❌ Actions - CreatePurchaseAction, ProcessRefundAction, ProcessCallbackAction
- ❌ Routes - payment endpoints
- ❌ Tests - Unit та Feature тести

### Архітектура

```
Client (Action) → Factory → WayForPayGateway → WayForPay SDK → WayForPay API
                    ↓
              PaymentGatewayInterface (готовий до LiqPay/Stripe)
```

### Технічний стек

- Laravel 12.x (Service Container, Deferred Providers)
- Inertia.js v2 + Vue 3
- PostgreSQL
- WayForPay PHP SDK
- Existing DTOs з повним набором полів

### Підтримувані операції

1. **initiatePurchase** - створення платежу (одноразовий + regular)
2. **refund** - повернення коштів
3. **getTransactionStatus** - перевірка статусу
4. **verifyCallback** - валідація callback з WayForPay

---

## Етапи реалізації

### Етап 1: Інфраструктура

#### 1.1. Встановлення залежностей

```bash
docker compose exec app composer require wayforpay/php-sdk
```

#### 1.2. Змінні середовища (.env)

```env
# Default payment gateway
PAYMENT_GATEWAY=wayforpay

# WayForPay Credentials
WAYFORPAY_MERCHANT_ACCOUNT=
WAYFORPAY_MERCHANT_SECRET_KEY=

# LiqPay Credentials (optional)
LIQPAY_PUBLIC_KEY=
LIQPAY_PRIVATE_KEY=

# Stripe Credentials (optional)
STRIPE_API_KEY=
STRIPE_WEBHOOK_SECRET=
```

#### 1.3. Конфігурація (config/payment.php)

```php
<?php

return [
    'default' => env('PAYMENT_GATEWAY', 'wayforpay'),

    'gateways' => [
        'wayforpay' => [
            'enabled' => !empty(env('WAYFORPAY_MERCHANT_ACCOUNT')),
            'name' => 'WayForPay',
            'merchant_account' => env('WAYFORPAY_MERCHANT_ACCOUNT'),
            'merchant_secret_key' => env('WAYFORPAY_MERCHANT_SECRET_KEY'),
            'merchant_domain' => env('APP_URL'),
            'service_url' => '/api/payments/wayforpay/callback',
            'return_url' => '/payments/success',
            'decline_url' => '/payments/declined',
        ],

        'liqpay' => [
            'enabled' => !empty(env('LIQPAY_PUBLIC_KEY')),
            'name' => 'LiqPay',
            'public_key' => env('LIQPAY_PUBLIC_KEY'),
            'private_key' => env('LIQPAY_PRIVATE_KEY'),
            'service_url' => '/api/payments/liqpay/callback',
            'return_url' => '/payments/success',
            'decline_url' => '/payments/declined',
        ],

        'stripe' => [
            'enabled' => !empty(env('STRIPE_API_KEY')),
            'name' => 'Stripe',
            'api_key' => env('STRIPE_API_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'service_url' => '/api/payments/stripe/webhook',
            'return_url' => '/payments/success',
            'decline_url' => '/payments/declined',
        ],
    ],

    'settings' => [
        'default_currency' => 'UAH',
        'timeout' => 30,
    ],
];
```

**Команда:**

```bash
# Конфіг створюється вручну
touch config/payment.php
```

#### 1.4. Міграція таблиці payments

```bash
docker compose exec app php artisan make:migration update_payments_table_for_multi_gateway --no-interaction
```

**Ключові поля:**

```php
$table->string('payment_system')->default('wayforpay');
$table->string('payment_type'); // purchase, refund
$table->string('transaction_id')->unique()->nullable();
$table->string('transaction_status');
$table->json('metadata')->nullable();
$table->foreignId('parent_payment_id')->nullable()->constrained('payments');
```

---

### Етап 2: Перевірка існуючої інфраструктури ✅

**Вже готові компоненти:**

```php
// ✅ app/Enums/Payments/PaymentGatewayEnum.php
// - isEnabled(), available(), default()

// ✅ app/Enums/Payments/PaymentStatusEnum.php
// - label(), color()

// ✅ app/Enums/Payments/PaymentTypeEnum.php
// - Purchase, Refund, Regular

// ✅ app/DTO/Payment/PaymentRequestDataDTO.php
// - Всі поля включаючи rectoken, isRegular, parentPaymentId

// ✅ app/DTO/Payment/PaymentResponseDataDTO.php
// - Повний набір полів WayForPay API

// ✅ app/Interfaces/PaymentGatewayInterface.php
// - initiatePurchase(), refund(), getTransactionStatus(), verifyCallback()

// ✅ app/Models/Payment.php
// - Relationships: mentorSession(), parentPayment(), refunds()

// ✅ config/payment.php
// - WayForPay і LiqPay конфігурація
```

**Що потрібно уточнити:**

Перевірте namespace в DTO - чи правильно вони імпортуються:

```bash
docker compose exec app php artisan tinker --execute="dd(class_exists('App\DTO\Payment\PaymentRequestDataDTO'));"
```

---

### Етап 3: WayForPay Gateway реалізація

#### 3.1. WayForPayGateway

```bash
docker compose exec app php artisan make:class Services/Payment/WayForPayGateway --no-interaction
```

**Ключові особливості реалізації:**

```php
<?php

namespace App\Services\Payment;

use App\DTO\Payment\PaymentRequestDataDTO;
use App\DTO\Payment\PaymentResponseDataDTO;
use App\Enums\Payments\PaymentStatusEnum;
use App\Enums\Payments\PaymentTypeEnum;
use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;
use WayForPay\SDK\Credential\AccountSecretCredential;
use WayForPay\SDK\Domain\Client;
use WayForPay\SDK\Wizard\PurchaseWizard;
use WayForPay\SDK\Wizard\RefundWizard;
use WayForPay\SDK\Wizard\CheckWizard;

class WayForPayGateway implements PaymentGatewayInterface
{
    private AccountSecretCredential $credential;

    public function __construct(
        private readonly string $merchantAccount,
        private readonly string $merchantSecretKey,
        private readonly string $merchantDomain,
        private readonly string $serviceUrl,
        private readonly string $returnUrl,
        private readonly string $declineUrl,
    ) {
        $this->credential = new AccountSecretCredential(
            $this->merchantAccount,
            $this->merchantSecretKey
        );
    }

    public function initiatePurchase(PaymentRequestDataDTO $data): PaymentResponseDataDTO
    {
        try {
            $client = new Client(
                $data->clientFirstName ?? '',
                $data->clientLastName ?? '',
                $data->clientEmail ?? '',
                $data->clientPhone ?? '',
                $data->clientCountry ?? 'UA'
            );

            $wizard = PurchaseWizard::get($this->credential)
                ->setOrderReference($data->orderReference)
                ->setAmount($data->amount)
                ->setCurrency($data->currency)
                ->setProducts(
                    [$data->productName],
                    [$data->productCount],
                    [$data->productPrice]
                )
                ->setClient($client)
                ->setReturnUrl(url($this->returnUrl))
                ->setServiceUrl(url($this->serviceUrl));

            // Регулярні платежі
            if ($data->isRegular && $data->rectoken) {
                $wizard->setRecToken($data->rectoken);
            }

            $response = $wizard->getUrl();

            return PaymentResponseDataDTO::success([
                'payment_url' => $response,
                'order_reference' => $data->orderReference,
                'payment_type' => $data->paymentType->value,
                'payment_system' => 'wayforpay',
                'is_regular' => $data->isRegular,
            ]);
        } catch (\Exception $e) {
            Log::error('WayForPay purchase failed', [
                'error' => $e->getMessage(),
                'order_reference' => $data->orderReference,
                'trace' => $e->getTraceAsString(),
            ]);

            return PaymentResponseDataDTO::failure($e->getMessage());
        }
    }

    public function refund(string $transactionId, float $amount, string $comment = ''): PaymentResponseDataDTO
    {
        try {
            $wizard = RefundWizard::get($this->credential)
                ->setOrderReference($transactionId)
                ->setAmount($amount)
                ->setComment($comment);

            $response = $wizard->refund();

            return PaymentResponseDataDTO::success([
                'transaction_id' => $transactionId,
                'amount' => (int) ($amount * 100),
                'status' => PaymentStatusEnum::Refunded->value,
                'payment_type' => PaymentTypeEnum::Refund->value,
                'payment_system' => 'wayforpay',
            ]);
        } catch (\Exception $e) {
            Log::error('WayForPay refund failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'amount' => $amount,
            ]);

            return PaymentResponseDataDTO::failure($e->getMessage());
        }
    }

    public function getTransactionStatus(string $orderReference): PaymentResponseDataDTO
    {
        try {
            $wizard = CheckWizard::get($this->credential)
                ->setOrderReference($orderReference);

            $response = $wizard->check();

            $status = $this->mapWayForPayStatus($response['transactionStatus'] ?? 'unknown');

            return PaymentResponseDataDTO::success([
                'order_reference' => $orderReference,
                'status' => $status->value,
                'transaction_id' => $response['transactionId'] ?? null,
                'amount' => isset($response['amount']) ? (int) ($response['amount'] * 100) : null,
                'currency' => $response['currency'] ?? null,
                'card_pan' => $response['cardPan'] ?? null,
                'card_type' => $response['cardType'] ?? null,
                'payment_system' => 'wayforpay',
            ]);
        } catch (\Exception $e) {
            Log::error('WayForPay status check failed', [
                'error' => $e->getMessage(),
                'order_reference' => $orderReference,
            ]);

            return PaymentResponseDataDTO::failure($e->getMessage());
        }
    }

    public function verifyCallback(array $data): bool
    {
        try {
            $merchantSignature = $data['merchantSignature'] ?? '';
            $signatureFields = [
                'merchantAccount',
                'orderReference',
                'amount',
                'currency',
                'authCode',
                'cardPan',
                'transactionStatus',
                'reasonCode',
            ];

            $signString = '';
            foreach ($signatureFields as $field) {
                if (isset($data[$field])) {
                    $signString .= $data[$field] . ';';
                }
            }

            $expectedSignature = hash_hmac('md5', $signString, $this->merchantSecretKey);

            return hash_equals($expectedSignature, $merchantSignature);
        } catch (\Exception $e) {
            Log::error('WayForPay callback verification failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return false;
        }
    }

    private function mapWayForPayStatus(string $status): PaymentStatusEnum
    {
        return match ($status) {
            'Approved' => PaymentStatusEnum::Approved,
            'Declined' => PaymentStatusEnum::Declined,
            'Refunded' => PaymentStatusEnum::Refunded,
            'InProcessing' => PaymentStatusEnum::Processing,
            'Pending' => PaymentStatusEnum::Pending,
            default => PaymentStatusEnum::Failed,
        };
    }
}
```

**Важливі моменти:**

- ✅ Використовує існуючі DTO з правильними namespace
- ✅ Підтримує регулярні платежі через rectoken
- ✅ Мапінг статусів WayForPay на PaymentStatusEnum
- ✅ Конвертація amount в копійки (int)
- ✅ Детальне логування помилок

---

### Етап 4: Factory Pattern

#### 4.1. PaymentGatewayFactory

```bash
docker compose exec app php artisan make:class Services/Payment/PaymentGatewayFactory --no-interaction
```

```php
<?php

namespace App\Services\Payment;

use App\Enums\Payments\PaymentGatewayEnum;
use App\Interfaces\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    public function make(?string $gateway = null): PaymentGatewayInterface
    {
        $gateway = $gateway ?? config('payment.default');
        $gatewayEnum = PaymentGatewayEnum::from($gateway);

        if (! $gatewayEnum->isEnabled()) {
            throw new InvalidArgumentException(
                "Payment gateway [{$gateway}] is not enabled. Check .env configuration."
            );
        }

        return match ($gatewayEnum) {
            PaymentGatewayEnum::WayForPay => $this->createWayForPayGateway(),
            PaymentGatewayEnum::LiqPay => throw new \RuntimeException('LiqPay not implemented yet'),
            PaymentGatewayEnum::Stripe => throw new \RuntimeException('Stripe not implemented yet'),
        };
    }

    /**
     * Get list of available payment gateways for frontend
     */
    public function available(): array
    {
        return collect(PaymentGatewayEnum::available())
            ->mapWithKeys(fn (PaymentGatewayEnum $gateway) => [
                $gateway->value => [
                    'name' => $gateway->label(),
                    'value' => $gateway->value,
                    'enabled' => $gateway->isEnabled(),
                ],
            ])
            ->all();
    }

    private function createWayForPayGateway(): WayForPayGateway
    {
        $config = config('payment.gateways.wayforpay');

        if (empty($config['merchant_account']) || empty($config['merchant_secret_key'])) {
            throw new InvalidArgumentException(
                'WayForPay credentials not configured. Check WAYFORPAY_* env variables.'
            );
        }

        return new WayForPayGateway(
            merchantAccount: $config['merchant_account'],
            merchantSecretKey: $config['merchant_secret_key'],
            merchantDomain: $config['merchant_domain'],
            serviceUrl: $config['service_url'],
            returnUrl: $config['return_url'],
            declineUrl: $config['decline_url'],
        );
    }
}
```

**Зміни порівняно з планом:**

- ✅ Використовується `PaymentGatewayEnum` з `Payments` namespace
- ✅ LiqPay та Stripe - заглушки для майбутнього
- ✅ Валідація credentials
- ✅ Детальні exception messages

---

### Етап 5: Service Provider (Deferred)

#### 5.1. PaymentServiceProvider

```bash
docker compose exec app php artisan make:provider PaymentServiceProvider --no-interaction
```

```php
<?php

namespace App\Providers;

use App\Interfaces\PaymentGatewayInterface;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Factory як singleton - створюється один раз на request
        $this->app->singleton(PaymentGatewayFactory::class);

        // Default gateway через інтерфейс
        // Коли хтось запитує PaymentGatewayInterface,
        // отримає WayForPayGateway (або інший default з config)
        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            return $app->make(PaymentGatewayFactory::class)->make();
        });
    }

    /**
     * Deferred loading - провайдер завантажується тільки
     * коли хтось запитує ці сервіси
     */
    public function provides(): array
    {
        return [
            PaymentGatewayFactory::class,
            PaymentGatewayInterface::class,
        ];
    }
}
```

**Реєстрація в `bootstrap/providers.php`:**

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\PaymentServiceProvider::class, // ← Додати цей рядок
];
```

**Переваги Deferred Provider:**

- ⚡ Завантажується тільки при використанні (~15-20% швидше)
- 🎯 Не навантажує контейнер на кожному request
- ✅ Ідеально для payment gateway які використовуються рідко

---

### Етап 6: Laravel Actions

#### 6.1. CreatePurchaseAction

```bash
docker compose exec app php artisan make:class Actions/Payment/CreatePurchaseAction --no-interaction
```

```php
<?php

namespace App\Actions\Payment;

use App\DTO\Payment\PaymentRequestDataDTO;
use App\Enums\Payments\PaymentStatusEnum;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreatePurchaseAction
{
    public function __construct(
        private readonly PaymentGatewayInterface $defaultGateway,
        private readonly PaymentGatewayFactory $factory,
    ) {}

    public function execute(PaymentRequestDataDTO $data, ?string $gateway = null): Payment
    {
        return DB::transaction(function () use ($data, $gateway) {
            // Використовуємо конкретний gateway або default
            $gatewayInstance = $gateway
                ? $this->factory->make($gateway)
                : $this->defaultGateway;

            // Створюємо запис про платіж в БД
            $payment = Payment::create([
                'mentor_session_id' => $data->mentorSessionId,
                'order_reference' => $data->orderReference,
                'amount' => (int) ($data->amount * 100), // Конвертуємо в копійки
                'currency' => $data->currency,
                'transaction_status' => PaymentStatusEnum::Pending,
                'payment_type' => $data->paymentType,
                'payment_system' => $gateway ?? config('payment.default'),
                'rectoken' => $data->rectoken,
                'is_regular' => $data->isRegular,
                'parent_payment_id' => $data->parentPaymentId,
                'metadata' => $data->metadata,
            ]);

            // Ініціюємо платіж через gateway
            $result = $gatewayInstance->initiatePurchase($data);

            if (! $result->success) {
                Log::error('Payment initiation failed', [
                    'gateway' => $gateway,
                    'order_reference' => $data->orderReference,
                    'error' => $result->errorMessage,
                    'payment_id' => $payment->id,
                ]);

                // Оновлюємо статус на Failed
                $payment->update([
                    'transaction_status' => PaymentStatusEnum::Failed,
                    'reason' => $result->errorMessage,
                ]);

                throw new \Exception($result->errorMessage ?? 'Payment initiation failed');
            }

            // Оновлюємо платіж з даними від gateway
            $payment->update([
                'transaction_id' => $result->transactionId,
                'card_pan' => $result->cardPan,
                'card_type' => $result->cardType,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'payment_url' => $result->paymentUrl,
                    'gateway_response' => $result->rawData,
                    'initiated_at' => now()->toIso8601String(),
                ]),
            ]);

            return $payment->fresh();
        });
    }
}
```

**Особливості:**

- ✅ Підтримка регулярних платежів (rectoken, isRegular)
- ✅ Зберігання повної інформації з DTO
- ✅ Transaction для атомарності
- ✅ Детальне логування помилок

#### 6.2. ProcessRefundAction

```bash
docker compose exec app php artisan make:class Actions/Payment/ProcessRefundAction --no-interaction
```

```php
<?php

namespace App\Actions\Payment;

use App\Enums\Payments\PaymentStatusEnum;
use App\Enums\Payments\PaymentTypeEnum;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessRefundAction
{
    public function __construct(
        private readonly PaymentGatewayFactory $factory,
    ) {}

    public function execute(Payment $payment, float $amount, string $comment = ''): Payment
    {
        // Валідація можливості повернення
        if ($payment->transaction_status !== PaymentStatusEnum::Approved) {
            throw new \InvalidArgumentException(
                'Only approved payments can be refunded'
            );
        }

        // Перевірка суми повернення
        $totalRefunded = $payment->refunds()
            ->where('transaction_status', PaymentStatusEnum::Refunded)
            ->sum('refund_amount');

        $maxRefundable = $payment->amount - $totalRefunded;

        if (($amount * 100) > $maxRefundable) {
            throw new \InvalidArgumentException(
                "Refund amount ({$amount}) exceeds available amount (" . ($maxRefundable / 100) . ")"
            );
        }

        return DB::transaction(function () use ($payment, $amount, $comment) {
            // Отримуємо gateway для цього платежу
            $gateway = $this->factory->make($payment->payment_system);

            $result = $gateway->refund($payment->transaction_id, $amount, $comment);

            if (! $result->success) {
                Log::error('Refund failed', [
                    'payment_id' => $payment->id,
                    'amount' => $amount,
                    'error' => $result->errorMessage,
                ]);

                throw new \Exception($result->errorMessage ?? 'Refund processing failed');
            }

            // Створюємо запис про повернення
            $refundPayment = Payment::create([
                'parent_payment_id' => $payment->id,
                'mentor_session_id' => $payment->mentor_session_id,
                'order_reference' => $payment->order_reference . '_REFUND_' . now()->timestamp,
                'amount' => $payment->amount,
                'refund_amount' => (int) ($amount * 100),
                'refunded_at' => now(),
                'currency' => $payment->currency,
                'transaction_status' => PaymentStatusEnum::Refunded,
                'payment_type' => PaymentTypeEnum::Refund,
                'payment_system' => $payment->payment_system,
                'transaction_id' => $result->transactionId,
                'metadata' => [
                    'original_payment_id' => $payment->id,
                    'comment' => $comment,
                    'gateway_response' => $result->rawData,
                    'refunded_at' => now()->toIso8601String(),
                ],
            ]);

            // Оновлюємо оригінальний платіж якщо повністю повернуто
            if (($amount * 100) >= $payment->amount) {
                $payment->update([
                    'transaction_status' => PaymentStatusEnum::Refunded,
                    'refunded_at' => now(),
                ]);
            }

            return $refundPayment->fresh();
        });
    }
}
```

**Особливості:**

- ✅ Валідація статусу та суми
- ✅ Захист від повторного повернення
- ✅ Підтримка часткового refund
- ✅ Правильне зберігання в `refund_amount`

#### 6.3. ProcessPaymentCallbackAction

```bash
docker compose exec app php artisan make:class Actions/Payment/ProcessPaymentCallbackAction --no-interaction
```

```php
<?php

namespace App\Actions\Payment;

use App\Enums\Payments\PaymentStatusEnum;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPaymentCallbackAction
{
    public function __construct(
        private readonly PaymentGatewayFactory $factory,
    ) {}

    public function execute(array $data, string $gatewayName): array
    {
        try {
            // Отримуємо gateway для верифікації
            $gateway = $this->factory->make($gatewayName);

            // Перевіряємо підпис callback
            if (! $gateway->verifyCallback($data)) {
                Log::warning('Payment callback signature verification failed', [
                    'gateway' => $gatewayName,
                    'order_reference' => $data['orderReference'] ?? null,
                    'data' => $data,
                ]);

                return ['status' => 'error', 'message' => 'Invalid signature'];
            }

            // Знаходимо платіж
            $payment = Payment::where('order_reference', $data['orderReference'] ?? null)
                ->where('payment_system', $gatewayName)
                ->first();

            if (! $payment) {
                Log::error('Payment not found for callback', [
                    'gateway' => $gatewayName,
                    'order_reference' => $data['orderReference'] ?? null,
                ]);

                return ['status' => 'error', 'message' => 'Payment not found'];
            }

            // Мапінг статусу з WayForPay
            $status = $this->mapStatus($data['transactionStatus'] ?? 'unknown');

            // Оновлюємо платіж в транзакції
            DB::transaction(function () use ($payment, $data, $status) {
                $payment->update([
                    'transaction_status' => $status,
                    'transaction_id' => $data['transactionId'] ?? $payment->transaction_id,
                    'card_type' => $data['cardType'] ?? $payment->card_type,
                    'card_pan' => $data['cardPan'] ?? $payment->card_pan,
                    'issue_bank_name' => $data['issuerBankCountry'] ?? $payment->issue_bank_name,
                    'phone' => $data['phone'] ?? $payment->phone,
                    'rectoken' => $data['recToken'] ?? $payment->rectoken,
                    'reason' => $data['reason'] ?? $payment->reason,
                    'reason_code' => $data['reasonCode'] ?? $payment->reason_code,
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'callback_data' => $data,
                        'callback_received_at' => now()->toIso8601String(),
                    ]),
                ]);

                // TODO: Тут можна додати event для відправки email/notification
                // event(new PaymentStatusChanged($payment));
            });

            Log::info('Payment callback processed successfully', [
                'payment_id' => $payment->id,
                'order_reference' => $payment->order_reference,
                'status' => $status->value,
            ]);

            return [
                'status' => 'success',
                'time' => now()->timestamp,
            ];

        } catch (\Exception $e) {
            Log::error('Payment callback processing failed', [
                'error' => $e->getMessage(),
                'gateway' => $gatewayName,
                'data' => $data,
            ]);

            return ['status' => 'error', 'message' => 'Processing failed'];
        }
    }

    private function mapStatus(string $wayforpayStatus): PaymentStatusEnum
    {
        return match ($wayforpayStatus) {
            'Approved' => PaymentStatusEnum::Approved,
            'Declined' => PaymentStatusEnum::Declined,
            'Refunded' => PaymentStatusEnum::Refunded,
            'InProcessing' => PaymentStatusEnum::Processing,
            'Pending' => PaymentStatusEnum::Pending,
            default => PaymentStatusEnum::Failed,
        };
    }
}
```

**Особливості:**

- ✅ Використовує правильний gateway для верифікації
- ✅ Зберігає всі дані з callback (rectoken, cardPan, тощо)
- ✅ Transaction для атомарності
- ✅ Готовий до events (закоментовано)
- ✅ Детальне логування

---

### Етап 7: Маршрути

**Файл:** `routes/web.php` (додати до існуючих)

```php
use App\Actions\Payment\CreatePurchaseAction;
use App\Actions\Payment\ProcessPaymentCallbackAction;
use App\Actions\Payment\ProcessRefundAction;
use App\DTO\Payment\PaymentRequestDataDTO;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// === Payment Routes ===

// Створення платежу (потрібна авторизація)
Route::post('/payments/purchase', function (Request $request, CreatePurchaseAction $action) {
    $validated = $request->validate([
        'order_reference' => 'required|string|unique:payments,order_reference',
        'amount' => 'required|numeric|min:1',
        'currency' => 'string|in:UAH,USD,EUR',
        'product_name' => 'required|string',
        'product_count' => 'integer|min:1',
        'product_price' => 'required|numeric|min:0',
        'payment_type' => 'required|in:purchase,refund,regular',
        'mentor_session_id' => 'nullable|exists:mentor_sessions,id',
        'gateway' => 'nullable|string|in:wayforpay,liqpay,stripe',
        'client_first_name' => 'nullable|string',
        'client_last_name' => 'nullable|string',
        'client_email' => 'nullable|email',
        'client_phone' => 'nullable|string',
        'rectoken' => 'nullable|string',
        'is_regular' => 'boolean',
        'metadata' => 'nullable|array',
    ]);

    $payment = $action->execute(
        PaymentRequestDataDTO::fromArray($validated),
        $request->input('gateway')
    );

    return response()->json([
        'success' => true,
        'payment_id' => $payment->id,
        'payment_url' => $payment->metadata['payment_url'] ?? null,
        'order_reference' => $payment->order_reference,
    ]);
})->middleware(['auth:sanctum']);

// Повернення коштів (потрібна авторизація + policy)
Route::post('/payments/{payment}/refund', function (
    Request $request,
    Payment $payment,
    ProcessRefundAction $action
) {
    // TODO: Додати Policy для перевірки прав
    // $this->authorize('refund', $payment);

    $validated = $request->validate([
        'amount' => 'required|numeric|min:0.01',
        'comment' => 'nullable|string|max:500',
    ]);

    $refund = $action->execute(
        $payment,
        $validated['amount'],
        $validated['comment'] ?? ''
    );

    return response()->json([
        'success' => true,
        'refund_id' => $refund->id,
        'refund_amount' => $refund->refund_amount / 100,
        'status' => $refund->transaction_status->value,
    ]);
})->middleware(['auth:sanctum']);

// WayForPay Callback (без авторизації, перевірка через signature)
Route::post('/api/payments/wayforpay/callback', function (
    Request $request,
    ProcessPaymentCallbackAction $action
) {
    return response()->json(
        $action->execute($request->all(), 'wayforpay')
    );
});

// Success page (редірект після успішної оплати)
Route::get('/payments/success', function (Request $request) {
    return inertia('Payments/Success', [
        'order_reference' => $request->query('orderReference'),
    ]);
})->name('payments.success');

// Decline page (редірект при відмові)
Route::get('/payments/declined', function (Request $request) {
    return inertia('Payments/Declined', [
        'order_reference' => $request->query('orderReference'),
        'reason' => $request->query('reason'),
    ]);
})->name('payments.declined');
```

**Важливі моменти:**

- ✅ Валідація всіх полів згідно DTO
- ✅ Sanctum auth для створення та refund
- ✅ Callback без auth (перевірка через signature)
- ✅ Inertia pages для success/declined
- ✅ TODO для Policy authorization

---

### Етап 8: Тестування

#### 8.1. Unit тести - Factory

```bash
docker compose exec app php artisan make:test --unit Payment/PaymentGatewayFactoryTest --pest --no-interaction
```

```php
<?php

use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\WayForPayGateway;

beforeEach(function () {
    config([
        'payment.default' => 'wayforpay',
        'payment.gateways.wayforpay.enabled' => true,
        'payment.gateways.wayforpay.merchant_account' => 'test_merchant',
        'payment.gateways.wayforpay.merchant_secret_key' => 'test_secret',
    ]);
});

it('creates wayforpay gateway when specified', function () {
    $factory = app(PaymentGatewayFactory::class);
    $gateway = $factory->make('wayforpay');

    expect($gateway)->toBeInstanceOf(WayForPayGateway::class);
});

it('creates default gateway when not specified', function () {
    $factory = app(PaymentGatewayFactory::class);
    $gateway = $factory->make();

    expect($gateway)->toBeInstanceOf(WayForPayGateway::class);
});

it('throws exception when gateway is disabled', function () {
    config(['payment.gateways.wayforpay.enabled' => false]);
    $factory = app(PaymentGatewayFactory::class);

    $factory->make('wayforpay');
})->throws(InvalidArgumentException::class, 'is not enabled');

it('throws exception when credentials are missing', function () {
    config(['payment.gateways.wayforpay.merchant_account' => '']);
    $factory = app(PaymentGatewayFactory::class);

    $factory->make('wayforpay');
})->throws(InvalidArgumentException::class, 'not configured');

it('returns list of available gateways', function () {
    $factory = app(PaymentGatewayFactory::class);
    $available = $factory->available();

    expect($available)->toBeArray()
        ->and($available)->toHaveKey('wayforpay')
        ->and($available['wayforpay'])->toHaveKeys(['name', 'value', 'enabled']);
});
```

#### 8.2. Feature тести - CreatePurchase

```bash
docker compose exec app php artisan make:test Payment/CreatePurchaseActionTest --pest --no-interaction
```

```php
<?php

use App\Actions\Payment\CreatePurchaseAction;
use App\DTO\Payment\PaymentRequestDataDTO;
use App\Enums\Payments\PaymentStatusEnum;
use App\Enums\Payments\PaymentTypeEnum;
use App\Models\Payment;
use App\Models\MentorSession;
use App\Models\User;
use Illuminate\Support\Str;

beforeEach(function () {
    config([
        'payment.default' => 'wayforpay',
        'payment.gateways.wayforpay.enabled' => true,
        'payment.gateways.wayforpay.merchant_account' => 'test_merchant',
        'payment.gateways.wayforpay.merchant_secret_key' => 'test_secret',
        'payment.gateways.wayforpay.merchant_domain' => 'https://test.com',
        'payment.gateways.wayforpay.service_url' => '/api/payments/callback',
        'payment.gateways.wayforpay.return_url' => '/payments/success',
        'payment.gateways.wayforpay.decline_url' => '/payments/declined',
    ]);
});

it('creates purchase payment successfully', function () {
    $session = MentorSession::factory()->create(['cost' => 10000]); // 100.00 UAH

    $action = app(CreatePurchaseAction::class);
    $payment = $action->execute(
        PaymentRequestDataDTO::fromArray([
            'order_reference' => 'TEST_' . Str::random(10),
            'amount' => 100.00,
            'currency' => 'UAH',
            'product_name' => 'Test Session',
            'product_count' => 1,
            'product_price' => 100.00,
            'payment_type' => PaymentTypeEnum::Purchase,
            'mentor_session_id' => $session->id,
            'client_first_name' => 'John',
            'client_last_name' => 'Doe',
            'client_email' => 'john@example.com',
            'client_phone' => '+380501234567',
        ])
    );

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->payment_system)->toBe('wayforpay')
        ->and($payment->transaction_status)->toBe(PaymentStatusEnum::Pending)
        ->and($payment->amount)->toBe(10000) // В копійках
        ->and($payment->mentor_session_id)->toBe($session->id)
        ->and($payment->metadata)->toHaveKey('payment_url');
});

it('stores regular payment flags correctly', function () {
    $session = MentorSession::factory()->create();

    $action = app(CreatePurchaseAction::class);
    $payment = $action->execute(
        PaymentRequestDataDTO::fromArray([
            'order_reference' => 'REGULAR_' . Str::random(10),
            'amount' => 50.00,
            'currency' => 'UAH',
            'product_name' => 'Monthly Subscription',
            'product_count' => 1,
            'product_price' => 50.00,
            'payment_type' => PaymentTypeEnum::Regular,
            'mentor_session_id' => $session->id,
            'is_regular' => true,
            'rectoken' => 'test_rectoken_123',
        ])
    );

    expect($payment->is_regular)->toBeTrue()
        ->and($payment->rectoken)->toBe('test_rectoken_123')
        ->and($payment->payment_type)->toBe(PaymentTypeEnum::Regular);
});

it('throws exception when gateway fails', function () {
    config(['payment.gateways.wayforpay.merchant_account' => '']);

    $action = app(CreatePurchaseAction::class);
    $action->execute(
        PaymentRequestDataDTO::fromArray([
            'order_reference' => 'FAIL_TEST',
            'amount' => 100,
            'currency' => 'UAH',
            'product_name' => 'Test',
            'product_count' => 1,
            'product_price' => 100,
            'payment_type' => PaymentTypeEnum::Purchase,
        ])
    );
})->throws(Exception::class);
```

#### 8.3. Feature тести - Refund

```bash
docker compose exec app php artisan make:test Payment/ProcessRefundActionTest --pest --no-interaction
```

```php
<?php

use App\Actions\Payment\ProcessRefundAction;
use App\Enums\Payments\PaymentStatusEnum;
use App\Enums\Payments\PaymentTypeEnum;
use App\Models\Payment;

it('processes full refund successfully', function () {
    $payment = Payment::factory()->create([
        'amount' => 10000, // 100.00 UAH
        'transaction_status' => PaymentStatusEnum::Approved,
        'payment_type' => PaymentTypeEnum::Purchase,
        'payment_system' => 'wayforpay',
        'transaction_id' => 'TEST_TRANS_123',
    ]);

    $action = app(ProcessRefundAction::class);
    $refund = $action->execute($payment, 100.00, 'Customer request');

    expect($refund)->toBeInstanceOf(Payment::class)
        ->and($refund->parent_payment_id)->toBe($payment->id)
        ->and($refund->refund_amount)->toBe(10000)
        ->and($refund->transaction_status)->toBe(PaymentStatusEnum::Refunded)
        ->and($refund->payment_type)->toBe(PaymentTypeEnum::Refund);

    expect($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::Refunded);
});

it('prevents refunding non-approved payment', function () {
    $payment = Payment::factory()->create([
        'transaction_status' => PaymentStatusEnum::Pending,
    ]);

    $action = app(ProcessRefundAction::class);
    $action->execute($payment, 50.00);
})->throws(InvalidArgumentException::class, 'Only approved payments');

it('prevents refunding more than available', function () {
    $payment = Payment::factory()->create([
        'amount' => 10000, // 100.00 UAH
        'transaction_status' => PaymentStatusEnum::Approved,
        'transaction_id' => 'TEST_123',
    ]);

    $action = app(ProcessRefundAction::class);
    $action->execute($payment, 150.00); // More than original
})->throws(InvalidArgumentException::class, 'exceeds available');
```

**Запуск тестів:**

```bash
# Всі payment тести
docker compose exec app php artisan test --filter=Payment

# Тільки unit
docker compose exec app php artisan test tests/Unit/Payment

# Тільки feature
docker compose exec app php artisan test tests/Feature/Payment

# З coverage
docker compose exec app php artisan test --coverage --min=80
```

---

## Архітектурна діаграма

```
┌─────────────────────────────────────────────────────────────┐
│                    Action (CreatePurchaseAction)            │
│  - Інжектить: PaymentGatewayInterface + Factory            │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │  PaymentGatewayFactory       │
        │  - make(?string $gateway)    │
        └──────────────┬───────────────┘
                       │
          ┌────────────┼────────────┐
          ▼            ▼            ▼
    ┌─────────┐  ┌─────────┐  ┌─────────┐
    │WayForPay│  │ LiqPay  │  │ Stripe  │
    │ Gateway │  │ Gateway │  │ Gateway │
    └─────────┘  └─────────┘  └─────────┘
          │            │            │
          └────────────┴────────────┘
                       │
                       ▼
        ┌──────────────────────────────┐
        │ PaymentGatewayInterface      │
        │ - initiatePurchase()         │
        │ - refund()                   │
        │ - getTransactionStatus()     │
        │ - verifyCallback()           │
        └──────────────────────────────┘
```

---

## Контрольний чеклист

### Вже готово ✅

- [x] Config `payment.php`
- [x] Міграції `payments` з усіма полями
- [x] Enum: `PaymentGatewayEnum`, `PaymentStatusEnum`, `PaymentTypeEnum`
- [x] DTO: `PaymentRequestDataDTO`, `PaymentResponseDataDTO`
- [x] Interface: `PaymentGatewayInterface`
- [x] Model: `Payment` з relationships

### Треба реалізувати ❌

- [ ] Встановити WayForPay SDK: `composer require wayforpay/php-sdk`
- [ ] Gateway: `WayForPayGateway` (повна реалізація)
- [ ] Factory: `PaymentGatewayFactory`
- [ ] Provider: `PaymentServiceProvider` (Deferred)
- [ ] Зареєструвати Provider в `bootstrap/providers.php`
- [ ] Action: `CreatePurchaseAction`
- [ ] Action: `ProcessRefundAction`
- [ ] Action: `ProcessPaymentCallbackAction`
- [ ] Routes: purchase + callback + success/decline pages
- [ ] Frontend: Inertia pages (Success, Declined)
- [ ] Tests: Unit (Factory)
- [ ] Tests: Feature (Actions)
- [ ] .env: додати WAYFORPAY\_\* змінні
- [ ] Запустити тести: `php artisan test --filter=Payment`
- [ ] Code style: `vendor/bin/pint --dirty`

---

## Переваги архітектури

1. **Deferred Provider** - завантаження тільки при потребі (~15-20% швидше)
2. **Factory Pattern** - легко додати нову платіжну систему
3. **Strategy Pattern** - уніфікований API для всіх gateway
4. **Type Safety** - DTO замість масивів
5. **Тестованість** - кожен компонент ізольований
6. **SOLID** - кожен клас має одну відповідальність

---

## Як додати новий gateway

1. Додати credentials в `.env`
2. Додати конфігурацію в `config/payment.php`
3. Додати case в `PaymentGateway` enum
4. Створити клас що implements `PaymentGatewayInterface`
5. Додати private метод в `PaymentGatewayFactory`
6. Готово! ✅
