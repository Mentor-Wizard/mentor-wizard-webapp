# План робіт: Інтеграція платіжної системи WayForPay

## Огляд проєкту

Інтеграція платіжної системи WayForPay для підтримки всіх типів платежів у
додатку MentorWizard з використанням пакету
[wayforpay/php-sdk](https://github.com/wayforpay/php-sdk).

**ВАЖЛИВО - Оновлення в версії 1.5:**

- ✅ Видалено методи для регулярних платежів з інтерфейсу (SDK не підтримує
  повністю)
- ✅ Додано кастомну реалізацію Account2Card (поповнення картки)
- ✅ Додано кастомну реалізацію Account2Account (переказ на рахунок)
- ✅ Видалено Changelog для спрощення документу
- ✅ Оновлено контракт PaymentGatewayInterface

### Технічний стек

- Laravel 12.x
- Inertia.js v2
- Vue 3
- PostgreSQL
- WayForPay PHP SDK

### Підтримувані операції

1. **Purchase (Invoice)** - створення invoice для прийняття платежів
2. **Refund** - повернення/відміна платежів
3. **Check** - перевірка статусу транзакції
4. **ServiceUrl/ReturnUrl** - обробка callback від WayForPay
5. **Account2Card** - поповнення карт (кастомна реалізація)
6. **Account2Account** - переказ на рахунок (кастомна реалізація)

---

## Критерії виконання

✅ WayForPay підключений до основної системи  
✅ Платіжна система має загальний інтерфейс та його реалізацію  
✅ Платіжна система підключається через інтерфейс, використовуючи
ServiceProvider  
✅ Всі транзакції зберігаються в таблиці `payments`  
✅ SDK використовується правильно відповідно до офіційної документації

---

## Етапи реалізації

### Етап 1: Підготовка інфраструктури

#### 1.1. Встановлення пакету WayForPay SDK

```bash
composer require wayforpay/php-sdk
```

#### 1.2. Оновлення міграції таблиці `payments`

**Поточна структура таблиці:**

```
payments:
  - id (bigint)
  - mentor_session_id (bigint, FK)
  - order_reference (varchar)
  - amount (int)
  - currency (varchar)
  - transaction_status (varchar)
  - reason (varchar)
  - reason_code (varchar)
  - payment_system (varchar)
  - card_type (varchar)
  - issue_bank_name (varchar)
  - created_at (timestamp)
  - updated_at (timestamp)
```

**Необхідні зміни:**

- Додати `transaction_id` (varchar, унікальний) - ID транзакції від WFP
- Додати `payment_type` (varchar) - тип операції: purchase, refund,
  account2card, account2account, regular
- Додати `refund_amount` (int, nullable) - сума повернення
- Додати `refunded_at` (timestamp, nullable) - дата повернення
- Додати `card_pan` (varchar, nullable) - маскована картка
- Додати `phone` (varchar, nullable) - телефон для деяких операцій
- Додати `account_number` (varchar, nullable) - номер рахунку для
  account2account
- Додати `rectoken` (varchar, nullable) - токен для регулярних платежів
- Додати `is_regular` (boolean, default false) - чи є регулярним
- Додати `parent_payment_id` (bigint, nullable, FK) - зв'язок з батьківськимЮ
  платежем (для refund)
- Додати `metadata` (json, nullable) - додаткові дані
- Змінити `mentor_session_id` на nullable - для платежів без сесій

**Команда створення міграції:**

```bash
php artisan make:migration update_payments_table_for_wayforpay --no-interaction
```

#### 1.3. Створення конфігураційного файлу

**Файл:** `config/payment.php`

```php
<?php

return [
    'default' => env('PAYMENT_GATEWAY', 'wayforpay'),

    'wayforpay' => [
        'merchant_account' => env('WAYFORPAY_MERCHANT_ACCOUNT'),
        'merchant_secret_key' => env('WAYFORPAY_MERCHANT_SECRET_KEY'),
        'merchant_domain' => env('WAYFORPAY_MERCHANT_DOMAIN', env('APP_URL')),
        'service_url' => env('WAYFORPAY_SERVICE_URL'),
        'return_url' => env('WAYFORPAY_RETURN_URL', '/payments/success'),
        'decline_url' => env('WAYFORPAY_DECLINE_URL', '/payments/declined'),
    ],
];
```

**Змінні середовища (.env):**

```env
PAYMENT_GATEWAY=wayforpay
WAYFORPAY_MERCHANT_ACCOUNT=test_merchant
WAYFORPAY_MERCHANT_SECRET_KEY=test_secret_key
WAYFORPAY_MERCHANT_DOMAIN="${APP_URL}"
WAYFORPAY_SERVICE_URL="${APP_URL}/api/payments/callback"
WAYFORPAY_RETURN_URL=/payments/success
WAYFORPAY_DECLINE_URL=/payments/declined
```

---

### Етап 2: Створення архітектури платіжної системи

#### 2.1. Створення інтерфейсу платіжного шлюзу

**Файл:** `app/Contracts/PaymentGatewayInterface.php`

```php
<?php

namespace App\Contracts;

use App\DataTransferObjects\PaymentData;
use App\DataTransferObjects\PaymentResult;

interface PaymentGatewayInterface
{
    /**
     * Ініціювати платіж (Purchase)
     */
    public function initiatePurchase(PaymentData $data): PaymentResult;

    /**
     * Повернути платіж (Refund)
     */
    public function refund(string $transactionId, float $amount, string $comment = ''): PaymentResult;

    /**
     * Отримати статус транзакції
     */
    public function getTransactionStatus(string $orderReference): PaymentResult;

    /**
     * Перевірити підпис callback
     */
    public function verifyCallback(array $data): bool;
}
```

**Команда створення:**

```bash
php artisan make:class Contracts/PaymentGatewayInterface --no-interaction
```

#### 2.2. Створення Data Transfer Objects

**Файл:** `app/DataTransferObjects/PaymentData.php`

```php
<?php

namespace App\DataTransferObjects;

readonly class PaymentData
{
    public function __construct(
        public string $orderReference,
        public float $amount,
        public string $currency,
        public string $productName,
        public int $productCount,
        public float $productPrice,
        public ?int $mentorSessionId = null,
        public ?string $clientFirstName = null,
        public ?string $clientLastName = null,
        public ?string $clientEmail = null,
        public ?string $clientPhone = null,
        public ?string $clientCountry = null,
        public ?string $clientCity = null,
        public ?string $clientAddress = null,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            orderReference: $data['order_reference'],
            amount: $data['amount'],
            currency: $data['currency'] ?? 'UAH',
            productName: $data['product_name'],
            productCount: $data['product_count'] ?? 1,
            productPrice: $data['product_price'],
            mentorSessionId: $data['mentor_session_id'] ?? null,
            clientFirstName: $data['client_first_name'] ?? null,
            clientLastName: $data['client_last_name'] ?? null,
            clientEmail: $data['client_email'] ?? null,
            clientPhone: $data['client_phone'] ?? null,
            clientCountry: $data['client_country'] ?? 'UA',
            clientCity: $data['client_city'] ?? null,
            clientAddress: $data['client_address'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
```

**Файл:** `app/DataTransferObjects/PaymentResult.php`

```php
<?php

namespace App\DataTransferObjects;

readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public ?string $transactionId = null,
        public ?string $orderReference = null,
        public ?string $status = null,
        public ?float $amount = null,
        public ?string $currency = null,
        public ?string $paymentUrl = null,
        public ?string $rectoken = null,
        public ?string $cardPan = null,
        public ?string $cardType = null,
        public ?string $issuerBankName = null,
        public ?string $reason = null,
        public ?string $reasonCode = null,
        public ?array $rawData = null,
        public ?string $errorMessage = null,
    ) {}

    public static function success(array $data): self
    {
        return new self(
            success: true,
            transactionId: $data['transaction_id'] ?? null,
            orderReference: $data['order_reference'] ?? null,
            status: $data['status'] ?? null,
            amount: $data['amount'] ?? null,
            currency: $data['currency'] ?? null,
            paymentUrl: $data['payment_url'] ?? null,
            rectoken: $data['rectoken'] ?? null,
            cardPan: $data['card_pan'] ?? null,
            cardType: $data['card_type'] ?? null,
            issuerBankName: $data['issuer_bank_name'] ?? null,
            reason: $data['reason'] ?? null,
            reasonCode: $data['reason_code'] ?? null,
            rawData: $data,
        );
    }

    public static function failure(string $errorMessage, ?array $rawData = null): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            rawData: $rawData,
        );
    }
}
```

**Команди створення:**

```bash
php artisan make:class DataTransferObjects/PaymentData --no-interaction
php artisan make:class DataTransferObjects/PaymentResult --no-interaction
```

#### 2.3. Створення Enum для статусів платежів

**Файл:** `app/Enums/PaymentStatus.php`

```php
<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Processing = 'processing';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Очікується',
            self::Approved => 'Успішно',
            self::Declined => 'Відхилено',
            self::Refunded => 'Повернено',
            self::PartiallyRefunded => 'Частково повернено',
            self::Processing => 'Обробляється',
            self::Failed => 'Помилка',
            self::Expired => 'Прострочено',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Declined => 'danger',
            self::Refunded, self::PartiallyRefunded => 'info',
            self::Processing => 'primary',
            self::Failed, self::Expired => 'danger',
        };
    }
}
```

**Файл:** `app/Enums/PaymentType.php`

```php
<?php

namespace App\Enums;

enum PaymentType: string
{
    case Purchase = 'purchase';
    case Refund = 'refund';
    case Account2card = 'account2card';
    case Account2account = 'account2account';
    case Regular = 'regular';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Оплата',
            self::Refund => 'Повернення',
            self::Account2card => 'Переказ на карту',
            self::Account2account => 'Переказ на рахунок',
            self::Regular => 'Регулярний платіж',
        };
    }
}
```

**Команди створення:**

```bash
php artisan make:enum PaymentStatus --no-interaction
php artisan make:enum PaymentType --no-interaction
```

---

### Етап 3: Реалізація WayForPay Gateway

#### 3.1. Створення WayForPayGateway класу

**Файл:** `app/Services/Payment/WayForPayGateway.php`

Цей клас буде реалізовувати `PaymentGatewayInterface` та використовувати
WayForPay SDK.

**Основні методи:**

- `__construct()` - ініціалізація з конфігурацією
- `initiatePurchase()` - створення форми оплати
- `refund()` - повернення коштів
- `transferToCard()` - переказ на картку
- `transferToAccount()` - переказ на рахунок
- `initiateRegularPayment()` - ініціація регулярного платежу
- `chargeRegularPayment()` - списання за токеном
- `getTransactionStatus()` - перевірка статусу
- `verifyCallback()` - валідація підпису callback

**Команда створення:**

```bash
php artisan make:class Services/Payment/WayForPayGateway --no-interaction
```

#### 3.2. Структура класу WayForPayGateway

```php
<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\DataTransferObjects\PaymentData;
use App\DataTransferObjects\PaymentResult;
use Illuminate\Support\Facades\Log;
use WayForPay\SDK\Credential\AccountSecretCredential;
use WayForPay\SDK\Domain\Client;
use WayForPay\SDK\Domain\Product;
use WayForPay\SDK\Domain\CardToken;
use WayForPay\SDK\Collection\ProductCollection;
use WayForPay\SDK\Wizard\InvoiceWizard;
use WayForPay\SDK\Wizard\RefundWizard;
use WayForPay\SDK\Wizard\ChargeWizard;
use WayForPay\SDK\Wizard\CheckWizard;
use WayForPay\SDK\Handler\ServiceUrlHandler;
use WayForPay\SDK\Exception\ApiException;
use WayForPay\SDK\Exception\WayForPaySDKException;
use DateTime;

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

    public function initiatePurchase(PaymentData $data): PaymentResult
    {
        try {
            // Створення клієнта
            $client = new Client(
                $data->clientFirstName ?? '',
                $data->clientLastName ?? '',
                $data->clientEmail ?? '',
                $data->clientPhone ?? '',
                $data->clientCountry ?? 'UA'
            );

            // Створення товарів
            $products = new ProductCollection([
                new Product(
                    $data->productName,
                    $data->productPrice,
                    $data->productCount
                )
            ]);

            // InvoiceWizard створює Invoice через API
            // Повертає InvoiceResponse з методом getInvoiceUrl()
            $response = InvoiceWizard::get($this->credential)
                ->setOrderReference($data->orderReference)
                ->setAmount($data->amount)
                ->setCurrency($data->currency)
                ->setOrderDate(new DateTime())
                ->setMerchantDomainName($this->merchantDomain)
                ->setClient($client)
                ->setProducts($products)
                ->setServiceUrl($this->serviceUrl)
                ->setReturnUrl($this->returnUrl)
                ->getRequest()  // Повертає InvoiceRequest
                ->send();       // Виконує API запит, повертає InvoiceResponse

            return PaymentResult::success([
                'payment_url' => $response->getInvoiceUrl(),  // URL для оплати
                'order_reference' => $data->orderReference,
                'amount' => $data->amount,
                'currency' => $data->currency,
            ]);
        } catch (ApiException $e) {
            Log::error('WayForPay initiatePurchase error', [
                'message' => $e->getMessage(),
                'order_reference' => $data->orderReference,
            ]);
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function processRefund(string $orderReference, float $amount, string $currency, ?string $comment = null): PaymentResult
    {
        try {
            // RefundWizard для повернення коштів
            $response = RefundWizard::get($this->credential)
                ->setOrderReference($orderReference)
                ->setAmount($amount)
                ->setCurrency($currency)
                ->setComment($comment ?? 'Refund')
                ->getRequest()  // Повертає RefundRequest
                ->send();       // Виконує API запит, повертає RufundResponse

            if ($response->getReason()->isOK()) {
                return PaymentResult::success([
                    'order_reference' => $orderReference,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => $response->getTransaction()->getStatus(),
                    'reason' => $response->getReason()->getMessage(),
                    'reason_code' => $response->getReason()->getCode(),
                ]);
            }

            return PaymentResult::failure($response->getReason()->getMessage());
        } catch (ApiException $e) {
            Log::error('WayForPay processRefund error', [
                'message' => $e->getMessage(),
                'order_reference' => $orderReference,
            ]);
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function chargeRegularPayment(PaymentData $data, string $rectoken): PaymentResult
    {
        try {
            $client = new Client(
                $data->clientFirstName ?? '',
                $data->clientLastName ?? '',
                $data->clientEmail ?? '',
                $data->clientPhone ?? '',
                $data->clientCountry ?? 'UA'
            );

            $products = new ProductCollection([
                new Product(
                    $data->productName,
                    $data->productPrice,
                    $data->productCount
                )
            ]);

            // ChargeWizard для списання за токеном
            $response = ChargeWizard::get($this->credential)
                ->setCardToken(new CardToken($rectoken))
                ->setOrderReference($data->orderReference)
                ->setAmount($data->amount)
                ->setCurrency($data->currency)
                ->setOrderDate(new DateTime())
                ->setMerchantDomainName($this->merchantDomain)
                ->setClient($client)
                ->setProducts($products)
                ->getRequest()  // Повертає ChargeRequest
                ->send();       // Виконує API запит, повертає ChargeResponse

            return PaymentResult::success([
                'transaction_id' => $response->getTransaction()->getTransactionId() ?? null,
                'order_reference' => $data->orderReference,
                'status' => $response->getTransaction()->getStatus(),
                'amount' => $data->amount,
                'currency' => $data->currency,
            ]);
        } catch (ApiException $e) {
            Log::error('WayForPay chargeRegularPayment error', [
                'message' => $e->getMessage(),
                'order_reference' => $data->orderReference,
            ]);
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function getTransactionStatus(string $orderReference): PaymentResult
    {
        try {
            // CheckWizard для перевірки статусу транзакції
            $response = CheckWizard::get($this->credential)
                ->setOrderReference($orderReference)
                ->getRequest()  // Повертає CheckRequest
                ->send();       // Виконує API запит, повертає CheckResponse

            if ($response->getReason()->isOK()) {
                $order = $response->getOrder();  // CheckResponse::getOrder()

                return PaymentResult::success([
                    'order_reference' => $orderReference,
                    'status' => $order->getStatus(),
                    'amount' => $order->getAmount(),
                    'currency' => $order->getCurrency(),
                    'reason' => $response->getReason()->getMessage(),
                    'reason_code' => $response->getReason()->getCode(),
                ]);
            }

            return PaymentResult::failure($response->getReason()->getMessage());
        } catch (ApiException $e) {
            Log::error('WayForPay getTransactionStatus error', [
                'message' => $e->getMessage(),
                'order_reference' => $orderReference,
            ]);
            return PaymentResult::failure($e->getMessage());
        }
    }

    public function verifyCallback(array $data): bool
    {
        try {
            // ServiceUrlHandler для обробки callback від WayForPay
            $handler = new ServiceUrlHandler($this->credential);

            // parseRequestFromPostRaw() валідує підпис і парсить дані
            $response = $handler->parseRequestFromPostRaw();

            return $response->getReason()->isOK();
        } catch (WayForPaySDKException $e) {
            Log::error('WayForPay verifyCallback error', [
                'message' => $e->getMessage(),
                'data' => $data,
            ]);
            return false;
        }
    }

    /**
     * Кастомна реалізація Account2Card (поповнення картки)
     * SDK не надає готового Wizard для цієї операції
     */
    public function transferToCard(
        string $cardNumber,
        float $amount,
        string $orderReference,
        string $description
    ): PaymentResult {
        try {
            // Формуємо дані для запиту
            $requestData = [
                'transactionType' => 'ACCOUNT_2_CARD',
                'merchantAccount' => $this->merchantAccount,
                'orderReference' => $orderReference,
                'orderDate' => time(),
                'amount' => $amount,
                'currency' => 'UAH',
                'cardBeneficiary' => $cardNumber,
                'merchantSignature' => '', // Буде розраховано нижче
            ];

            // Розрахунок підпису
            $signatureString = implode(';', [
                $requestData['merchantAccount'],
                $requestData['orderReference'],
                $requestData['amount'],
                $requestData['currency'],
                $requestData['cardBeneficiary'],
                $requestData['orderDate'],
            ]);

            $requestData['merchantSignature'] = hash_hmac(
                'md5',
                $signatureString,
                $this->merchantSecretKey
            );

            // HTTP запит до WayForPay API
            $response = Http::post('https://api.wayforpay.com/api', $requestData);

            if (!$response->successful()) {
                throw new \Exception('WayForPay API error: ' . $response->status());
            }

            $data = $response->json();

            // Перевірка відповіді
            if (isset($data['reasonCode']) && $data['reasonCode'] == 1100) {
                return PaymentResult::success([
                    'transaction_id' => $data['transactionId'] ?? null,
                    'order_reference' => $orderReference,
                    'status' => $data['transactionStatus'] ?? 'Approved',
                    'amount' => $amount,
                    'currency' => 'UAH',
                    'card_pan' => $cardNumber,
                    'reason' => $data['reason'] ?? 'OK',
                    'reason_code' => $data['reasonCode'],
                ]);
            }

            return PaymentResult::failure($data['reason'] ?? 'Unknown error');

        } catch (\Exception $e) {
            Log::error('WayForPay transferToCard error', [
                'message' => $e->getMessage(),
                'card_number' => substr($cardNumber, 0, 6) . '******' . substr($cardNumber, -4),
                'order_reference' => $orderReference,
            ]);
            return PaymentResult::failure($e->getMessage());
        }
    }

    /**
     * Кастомна реалізація Account2Account (переказ на рахунок)
     * SDK не надає готового Wizard для цієї операції
     */
    public function transferToAccount(
        string $accountNumber,
        string $bankCode,
        float $amount,
        string $orderReference,
        string $description,
        array $beneficiaryData = []
    ): PaymentResult {
        try {
            // Формуємо дані для запиту
            $requestData = [
                'transactionType' => 'ACCOUNT_2_ACCOUNT',
                'merchantAccount' => $this->merchantAccount,
                'orderReference' => $orderReference,
                'orderDate' => time(),
                'amount' => $amount,
                'currency' => 'UAH',
                'accountBeneficiary' => $accountNumber,
                'bankCodeBeneficiary' => $bankCode,
                'purposeOfPayment' => $description,
                'merchantSignature' => '', // Буде розраховано нижче
            ];

            // Додаткові дані бенефіціара (якщо потрібно)
            if (!empty($beneficiaryData)) {
                $requestData = array_merge($requestData, [
                    'beneficiaryName' => $beneficiaryData['name'] ?? '',
                    'beneficiaryCode' => $beneficiaryData['code'] ?? '',
                    'beneficiaryAddress' => $beneficiaryData['address'] ?? '',
                ]);
            }

            // Розрахунок підпису
            $signatureString = implode(';', [
                $requestData['merchantAccount'],
                $requestData['orderReference'],
                $requestData['amount'],
                $requestData['currency'],
                $requestData['accountBeneficiary'],
                $requestData['bankCodeBeneficiary'],
                $requestData['orderDate'],
            ]);

            $requestData['merchantSignature'] = hash_hmac(
                'md5',
                $signatureString,
                $this->merchantSecretKey
            );

            // HTTP запит до WayForPay API
            $response = Http::post('https://api.wayforpay.com/api', $requestData);

            if (!$response->successful()) {
                throw new \Exception('WayForPay API error: ' . $response->status());
            }

            $data = $response->json();

            // Перевірка відповіді
            if (isset($data['reasonCode']) && $data['reasonCode'] == 1100) {
                return PaymentResult::success([
                    'transaction_id' => $data['transactionId'] ?? null,
                    'order_reference' => $orderReference,
                    'status' => $data['transactionStatus'] ?? 'Approved',
                    'amount' => $amount,
                    'currency' => 'UAH',
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode,
                    'reason' => $data['reason'] ?? 'OK',
                    'reason_code' => $data['reasonCode'],
                ]);
            }

            return PaymentResult::failure($data['reason'] ?? 'Unknown error');

        } catch (\Exception $e) {
            Log::error('WayForPay transferToAccount error', [
                'message' => $e->getMessage(),
                'account_number' => substr($accountNumber, 0, 4) . '******' . substr($accountNumber, -4),
                'order_reference' => $orderReference,
            ]);
            return PaymentResult::failure($e->getMessage());
        }
    }
}
```

**Важливі примітки щодо кастомних методів:**

1. **Account2Card і Account2Account** не мають готових Wizard класів у SDK
2. Реалізовано через прямі HTTP запити до WayForPay API
3. Підпис розраховується вручну згідно з документацією WayForPay
4. Використовується `Illuminate\Support\Facades\Http` для запитів
5. Логування помилок з масканням чутливих даних (номери карт/рахунків)
6. Повертається стандартний `PaymentResult` для уніфікованої обробки

**Додати до use statements у класі:**

```php
use Illuminate\Support\Facades\Http;
```

````

---

### Етап 4: Реєстрація в Service Container

#### 4.1. Створення PaymentServiceProvider

**Файл:** `app/Providers/PaymentServiceProvider.php`

```php
<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\WayForPayGateway;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayInterface::class, function ($app) {
            return new WayForPayGateway(
                merchantAccount: config('payment.wayforpay.merchant_account'),
                merchantSecretKey: config('payment.wayforpay.merchant_secret_key'),
                merchantDomain: config('payment.wayforpay.merchant_domain'),
                serviceUrl: config('payment.wayforpay.service_url'),
                returnUrl: config('payment.wayforpay.return_url'),
                declineUrl: config('payment.wayforpay.decline_url'),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
````

**Команда створення:**

```bash
php artisan make:provider PaymentServiceProvider --no-interaction
```

#### 4.2. Реєстрація в bootstrap/providers.php

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\PaymentServiceProvider::class, // Додати цей рядок
];
```

---

### Етап 5: Модель Payment

#### 5.1. Оновлення моделі Payment

**Файл:** `app/Models/Payment.php`

```php
<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentor_session_id',
        'transaction_id',
        'order_reference',
        'amount',
        'currency',
        'transaction_status',
        'payment_type',
        'refund_amount',
        'refunded_at',
        'reason',
        'reason_code',
        'payment_system',
        'card_type',
        'card_pan',
        'issue_bank_name',
        'phone',
        'account_number',
        'rectoken',
        'is_regular',
        'parent_payment_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'refund_amount' => 'integer',
            'refunded_at' => 'datetime',
            'is_regular' => 'boolean',
            'metadata' => 'array',
            'transaction_status' => PaymentStatus::class,
            'payment_type' => PaymentType::class,
        ];
    }

    public function mentorSession(): BelongsTo
    {
        return $this->belongsTo(MentorSession::class);
    }

    public function parentPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'parent_payment_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Payment::class, 'parent_payment_id');
    }

    public function isApproved(): bool
    {
        return $this->transaction_status === PaymentStatus::Approved;
    }

    public function isRefundable(): bool
    {
        return $this->isApproved()
            && $this->payment_type === PaymentType::Purchase
            && ($this->refund_amount === null || $this->refund_amount < $this->amount);
    }
}
```

---

### Етап 6: Actions для бізнес-логіки

#### 6.1. Створення Actions

**Структура Actions:**

- `CreatePurchaseAction` - створення платежу
- `ProcessRefundAction` - обробка повернення
- `TransferToCardAction` - переказ на карту (кастомна реалізація)
- `TransferToAccountAction` - переказ на рахунок (кастомна реалізація)
- `ProcessPaymentCallbackAction` - обробка callback від WFP
- `ShowPaymentSuccessAction` - відображення успішного платежу
- `ShowPaymentDeclinedAction` - відображення відхиленого платежу
- `ShowCheckoutAction` - відображення сторінки оплати
- `HandlePurchaseRequestAction` - обробка запиту на створення платежу
- `HandleRefundRequestAction` - обробка запиту на повернення

**Команди створення:**

```bash
php artisan make:class Actions/Payment/CreatePurchaseAction --no-interaction
php artisan make:class Actions/Payment/ProcessRefundAction --no-interaction
php artisan make:class Actions/Payment/TransferToCardAction --no-interaction
php artisan make:class Actions/Payment/TransferToAccountAction --no-interaction
php artisan make:class Actions/Payment/ProcessPaymentCallbackAction --no-interaction
php artisan make:class Actions/Payment/ShowPaymentSuccessAction --no-interaction
php artisan make:class Actions/Payment/ShowPaymentDeclinedAction --no-interaction
php artisan make:class Actions/Payment/ShowCheckoutAction --no-interaction
php artisan make:class Actions/Payment/HandlePurchaseRequestAction --no-interaction
php artisan make:class Actions/Payment/HandleRefundRequestAction --no-interaction
```

#### 6.2. Приклад Action - CreatePurchaseAction

```php
<?php

namespace App\Actions\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\DataTransferObjects\PaymentData;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreatePurchaseAction
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
    ) {}

    public function execute(PaymentData $data): Payment
    {
        // Створити запис у БД
        $payment = Payment::create([
            'mentor_session_id' => $data->mentorSessionId,
            'order_reference' => $data->orderReference,
            'amount' => (int) ($data->amount * 100), // Конвертація в копійки
            'currency' => $data->currency,
            'transaction_status' => PaymentStatus::Pending,
            'payment_type' => PaymentType::Purchase,
            'payment_system' => 'WayForPay',
            'metadata' => $data->metadata,
        ]);

        // Ініціювати платіж через gateway
        $result = $this->gateway->initiatePurchase($data);

        if (! $result->success) {
            Log::error('Payment initiation failed', [
                'order_reference' => $data->orderReference,
                'error' => $result->errorMessage,
            ]);

            $payment->update([
                'transaction_status' => PaymentStatus::Failed,
                'reason' => $result->errorMessage,
            ]);

            throw new \Exception($result->errorMessage ?? 'Failed to initiate payment');
        }

        // Оновити запис даними від WFP
        $payment->update([
            'transaction_id' => $result->transactionId,
        ]);

        return $payment;
    }
}
```

#### 6.3. Приклад Action - TransferToCardAction

**Файл:** `app/Actions/Payment/TransferToCardAction.php`

```php
<?php

namespace App\Actions\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class TransferToCardAction
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
    ) {}

    public function execute(array $data): Payment
    {
        // Створити запис платежу
        $payment = Payment::create([
            'order_reference' => $data['order_reference'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'UAH',
            'payment_type' => PaymentType::Account2Card,
            'transaction_status' => PaymentStatus::Pending,
            'card_pan' => substr($data['card_number'], 0, 6) . '******' . substr($data['card_number'], -4),
            'metadata' => [
                'description' => $data['description'] ?? '',
            ],
        ]);

        // Викликати WFP кастомний метод
        $result = $this->gateway->transferToCard(
            cardNumber: $data['card_number'],
            amount: $data['amount'],
            orderReference: $data['order_reference'],
            description: $data['description'] ?? 'Transfer to card'
        );

        if ($result->success) {
            $payment->update([
                'transaction_id' => $result->transactionId,
                'transaction_status' => PaymentStatus::Approved,
                'reason' => $result->reason,
                'reason_code' => $result->reasonCode,
            ]);

            Log::info('Transfer to card successful', [
                'order_reference' => $payment->order_reference,
                'transaction_id' => $result->transactionId,
            ]);
        } else {
            $payment->update([
                'transaction_status' => PaymentStatus::Failed,
                'reason' => $result->errorMessage,
            ]);

            Log::error('Transfer to card failed', [
                'order_reference' => $payment->order_reference,
                'error' => $result->errorMessage,
            ]);
        }

        return $payment;
    }
}
```

#### 6.4. Приклад Action - TransferToAccountAction

**Файл:** `app/Actions/Payment/TransferToAccountAction.php`

```php
<?php

namespace App\Actions\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class TransferToAccountAction
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
    ) {}

    public function execute(array $data): Payment
    {
        // Створити запис платежу
        $payment = Payment::create([
            'order_reference' => $data['order_reference'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'UAH',
            'payment_type' => PaymentType::Account2Account,
            'transaction_status' => PaymentStatus::Pending,
            'account_number' => substr($data['account_number'], 0, 4) . '******' . substr($data['account_number'], -4),
            'metadata' => [
                'bank_code' => $data['bank_code'],
                'description' => $data['description'] ?? '',
                'beneficiary' => $data['beneficiary'] ?? [],
            ],
        ]);

        // Викликати WFP кастомний метод
        $result = $this->gateway->transferToAccount(
            accountNumber: $data['account_number'],
            bankCode: $data['bank_code'],
            amount: $data['amount'],
            orderReference: $data['order_reference'],
            description: $data['description'] ?? 'Transfer to account',
            beneficiaryData: $data['beneficiary'] ?? []
        );

        if ($result->success) {
            $payment->update([
                'transaction_id' => $result->transactionId,
                'transaction_status' => PaymentStatus::Approved,
                'reason' => $result->reason,
                'reason_code' => $result->reasonCode,
            ]);

            Log::info('Transfer to account successful', [
                'order_reference' => $payment->order_reference,
                'transaction_id' => $result->transactionId,
            ]);
        } else {
            $payment->update([
                'transaction_status' => PaymentStatus::Failed,
                'reason' => $result->errorMessage,
            ]);

            Log::error('Transfer to account failed', [
                'order_reference' => $payment->order_reference,
                'error' => $result->errorMessage,
            ]);
        }

        return $payment;
    }
}
```

---

### Етап 7: Маршрути та інтеграція Actions

#### 7.1. Створення Form Requests для валідації

**Команди створення:**

```bash
php artisan make:request Payment/CreatePurchaseRequest --no-interaction
php artisan make:request Payment/RefundPaymentRequest --no-interaction
php artisan make:request Payment/TransferToCardRequest --no-interaction
php artisan make:request Payment/TransferToAccountRequest --no-interaction
```

#### 7.2. Додаткові Actions для роботи з маршрутами

**Структура додаткових Actions:**

- `ShowPaymentSuccessAction` - відображення успішного платежу
- `ShowPaymentDeclinedAction` - відображення відхиленого платежу
- `ShowCheckoutAction` - відображення сторінки оплати
- `HandlePurchaseRequestAction` - обробка запиту на створення платежу
- `HandleRefundRequestAction` - обробка запиту на повернення

**Команди створення:**

```bash
php artisan make:class Actions/Payment/ShowPaymentSuccessAction --no-interaction
php artisan make:class Actions/Payment/ShowPaymentDeclinedAction --no-interaction
php artisan make:class Actions/Payment/ShowCheckoutAction --no-interaction
php artisan make:class Actions/Payment/HandlePurchaseRequestAction --no-interaction
php artisan make:class Actions/Payment/HandleRefundRequestAction --no-interaction
```

**Файл:** `app/Actions/Payment/ShowCheckoutAction.php`

```php
<?php

namespace App\Actions\Payment;

use App\Models\MentorSession;
use Inertia\Response;

class ShowCheckoutAction
{
    public function execute(MentorSession $mentorSession): Response
    {
        return inertia('MentorSessions/Checkout', [
            'session' => [
                'id' => $mentorSession->id,
                'mentor' => $mentorSession->mentor->only(['id', 'username', 'email']),
                'cost' => $mentorSession->cost,
                'date' => $mentorSession->date,
            ],
        ]);
    }
}
```

**Файл:** `app/Actions/Payment/HandlePurchaseRequestAction.php`

```php
<?php

namespace App\Actions\Payment;

use App\DataTransferObjects\PaymentData;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class HandlePurchaseRequestAction
{
    public function __construct(
        private readonly CreatePurchaseAction $createPurchaseAction,
    ) {}

    public function execute(PaymentData $data): JsonResponse
    {
        $payment = $this->createPurchaseAction->execute($data);

        return response()->json([
            'success' => true,
            'payment' => [
                'id' => $payment->id,
                'order_reference' => $payment->order_reference,
                'status' => $payment->transaction_status->value,
            ],
            'redirect_url' => $payment->metadata['payment_url'] ?? null,
        ]);
    }
}
```

**Файл:** `app/Actions/Payment/HandleRefundRequestAction.php`

```php
<?php

namespace App\Actions\Payment;

use App\Models\Payment;
use Illuminate\Http\RedirectResponse;

class HandleRefundRequestAction
{
    public function __construct(
        private readonly ProcessRefundAction $processRefundAction,
    ) {}

    public function execute(Payment $payment, array $data): RedirectResponse
    {
        $this->processRefundAction->execute($payment, $data);

        return back()->with('success', 'Платіж успішно повернено');
    }
}
```

**Файл:** `app/Actions/Payment/ShowPaymentSuccessAction.php`

```php
<?php

namespace App\Actions\Payment;

use Illuminate\Http\Request;
use Inertia\Response;

class ShowPaymentSuccessAction
{
    public function execute(Request $request): Response
    {
        return inertia('Payments/Success', [
            'orderReference' => $request->query('orderReference'),
            'transactionId' => $request->query('transactionId'),
        ]);
    }
}
```

**Файл:** `app/Actions/Payment/ShowPaymentDeclinedAction.php`

```php
<?php

namespace App\Actions\Payment;

use Illuminate\Http\Request;
use Inertia\Response;

class ShowPaymentDeclinedAction
{
    public function execute(Request $request): Response
    {
        return inertia('Payments/Declined', [
            'reason' => $request->query('reason'),
            'orderReference' => $request->query('orderReference'),
        ]);
    }
}
```

#### 7.3. Маршрути (без бізнес-логіки)

**Файл:** `routes/web.php`

```php
use App\Actions\Payment\ShowPaymentSuccessAction;
use App\Actions\Payment\ShowPaymentDeclinedAction;
use App\Actions\Payment\ShowCheckoutAction;
use App\Actions\Payment\HandlePurchaseRequestAction;
use App\Actions\Payment\HandleRefundRequestAction;
use App\Actions\Payment\ProcessPaymentCallbackAction;
use App\Http\Requests\Payment\CreatePurchaseRequest;
use App\Http\Requests\Payment\RefundPaymentRequest;
use App\DataTransferObjects\PaymentData;
use App\Models\MentorSession;
use App\Models\Payment;
use Illuminate\Http\Request;

Route::middleware(['auth'])->group(function () {
    // Сторінки платежів
    Route::get('/payments/success', fn(Request $request, ShowPaymentSuccessAction $action) =>
        $action->execute($request)
    )->name('payments.success');

    Route::get('/payments/declined', fn(Request $request, ShowPaymentDeclinedAction $action) =>
        $action->execute($request)
    )->name('payments.declined');

    Route::get('/mentor-sessions/{mentorSession}/checkout', fn(MentorSession $mentorSession, ShowCheckoutAction $action) =>
        $action->execute($mentorSession)
    )->name('mentor-sessions.checkout');

    // API для платежів
    Route::post('/payments/purchase', fn(CreatePurchaseRequest $request, HandlePurchaseRequestAction $action) =>
        $action->execute(PaymentData::fromArray($request->validated()))
    )->name('payments.purchase');

    Route::post('/payments/refund/{payment}', fn(Payment $payment, RefundPaymentRequest $request, HandleRefundRequestAction $action) =>
        $action->execute($payment, $request->validated())
    )->name('payments.refund');
});

// Callback (без авторизації)
Route::post('/api/payments/callback', fn(Request $request, ProcessPaymentCallbackAction $action) =>
    response()->json($action->execute($request->all()))
)->name('payments.callback');
```

#### 7.4. Створення Vue 3 компонентів для Inertia

**Структура Vue компонентів:**

```
resources/js/Pages/
├── Payments/
│   ├── Success.vue
│   ├── Declined.vue
│   └── Components/
│       └── PaymentForm.vue
└── MentorSessions/
    └── Checkout.vue
```

**Файл:** `resources/js/Pages/MentorSessions/Checkout.vue`

```vue
<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    session: Object,
});

const processing = ref(false);
const error = ref(null);

const handlePayment = async () => {
    processing.value = true;
    error.value = null;

    try {
        const response = await fetch(route('payments.purchase'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
            },
            body: JSON.stringify({
                order_reference: `SESSION_${props.session.id}_${Date.now()}`,
                amount: props.session.cost,
                currency: 'UAH',
                product_name: `Mentor Session with ${props.session.mentor.username}`,
                product_count: 1,
                product_price: props.session.cost,
                mentor_session_id: props.session.id,
            }),
        });

        const data = await response.json();

        if (data.success && data.redirect_url) {
            // Редірект на WayForPay
            window.location.href = data.redirect_url;
        } else {
            error.value = 'Помилка створення платежу';
        }
    } catch (err) {
        error.value = "Помилка з'єднання з сервером";
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <AppLayout title="Оплата сесії">
        <div class="mx-auto max-w-2xl px-4 py-8">
            <div class="rounded-lg bg-white p-6 shadow-md">
                <h1 class="mb-6 text-2xl font-bold">
                    Оплата менторської сесії
                </h1>

                <div class="mb-6">
                    <div class="mb-2 flex justify-between">
                        <span class="text-gray-600">Ментор:</span>
                        <span class="font-semibold">{{
                            session.mentor.username
                        }}</span>
                    </div>
                    <div class="mb-2 flex justify-between">
                        <span class="text-gray-600">Дата:</span>
                        <span class="font-semibold">{{
                            new Date(session.date).toLocaleString('uk-UA')
                        }}</span>
                    </div>
                    <div class="mb-2 flex justify-between">
                        <span class="text-gray-600">Вартість:</span>
                        <span class="text-xl font-semibold"
                            >{{ session.cost }} грн</span
                        >
                    </div>
                </div>

                <div
                    v-if="error"
                    class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700"
                >
                    {{ error }}
                </div>

                <button
                    @click="handlePayment"
                    :disabled="processing"
                    class="w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white transition-colors hover:bg-blue-700 disabled:bg-gray-400"
                >
                    <span v-if="processing">Обробка...</span>
                    <span v-else>Оплатити {{ session.cost }} грн</span>
                </button>

                <div class="mt-4 text-center text-sm text-gray-500">
                    <p>Безпечний платіж через WayForPay</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
```

**Файл:** `resources/js/Pages/Payments/Success.vue`

```vue
<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    orderReference: String,
    transactionId: String,
});

const goToDashboard = () => {
    router.visit(route('dashboard'));
};
</script>

<template>
    <AppLayout title="Платіж успішний">
        <div class="mx-auto max-w-md px-4 py-12">
            <div class="rounded-lg bg-white p-8 text-center shadow-md">
                <div class="mb-6">
                    <svg
                        class="mx-auto h-20 w-20 text-green-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>

                <h1 class="mb-4 text-3xl font-bold text-gray-900">
                    Платіж успішний!
                </h1>
                <p class="mb-6 text-gray-600">Ваш платіж успішно оброблено</p>

                <div class="mb-6 rounded-lg bg-gray-50 p-4 text-left">
                    <div class="mb-1 text-sm text-gray-600">
                        Номер замовлення
                    </div>
                    <div class="font-mono text-sm">{{ orderReference }}</div>

                    <div v-if="transactionId" class="mt-3">
                        <div class="mb-1 text-sm text-gray-600">
                            ID транзакції
                        </div>
                        <div class="font-mono text-sm">{{ transactionId }}</div>
                    </div>
                </div>

                <button
                    @click="goToDashboard"
                    class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-blue-700"
                >
                    Повернутися на головну
                </button>
            </div>
        </div>
    </AppLayout>
</template>
```

**Файл:** `resources/js/Pages/Payments/Declined.vue`

```vue
<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    reason: String,
    orderReference: String,
});

const tryAgain = () => {
    router.visit(route('dashboard'));
};
</script>

<template>
    <AppLayout title="Платіж відхилено">
        <div class="mx-auto max-w-md px-4 py-12">
            <div class="rounded-lg bg-white p-8 text-center shadow-md">
                <div class="mb-6">
                    <svg
                        class="mx-auto h-20 w-20 text-red-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </div>

                <h1 class="mb-4 text-3xl font-bold text-gray-900">
                    Платіж відхилено
                </h1>
                <p class="mb-2 text-gray-600">
                    На жаль, платіж не вдалося обробити
                </p>

                <div
                    v-if="reason"
                    class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4"
                >
                    <p class="text-sm text-red-700">{{ reason }}</p>
                </div>

                <div
                    v-if="orderReference"
                    class="mb-6 rounded-lg bg-gray-50 p-4 text-left"
                >
                    <div class="mb-1 text-sm text-gray-600">
                        Номер замовлення
                    </div>
                    <div class="font-mono text-sm">{{ orderReference }}</div>
                </div>

                <button
                    @click="tryAgain"
                    class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-blue-700"
                >
                    Спробувати ще раз
                </button>
            </div>
        </div>
    </AppLayout>
</template>
```

#### 7.5. Композабл для роботи з платежами (опціонально)

**Файл:** `resources/js/Composables/usePayment.js`

```js
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function usePayment() {
    const processing = ref(false);
    const error = ref(null);

    const createPayment = async (paymentData) => {
        processing.value = true;
        error.value = null;

        try {
            const response = await fetch(route('payments.purchase'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector(
                        'meta[name="csrf-token"]',
                    ).content,
                },
                body: JSON.stringify(paymentData),
            });

            const data = await response.json();

            if (data.success && data.redirect_url) {
                window.location.href = data.redirect_url;
                return true;
            } else {
                error.value = data.message || 'Помилка створення платежу';
                return false;
            }
        } catch (err) {
            error.value = "Помилка з'єднання з сервером";
            return false;
        } finally {
            processing.value = false;
        }
    };

    const refundPayment = async (paymentId, amount, comment) => {
        processing.value = true;
        error.value = null;

        try {
            router.post(
                route('payments.refund', paymentId),
                { amount, comment },
                {
                    onSuccess: () => {
                        processing.value = false;
                    },
                    onError: (errors) => {
                        error.value = Object.values(errors).flat()[0];
                        processing.value = false;
                    },
                },
            );
        } catch (err) {
            error.value = 'Помилка обробки повернення';
            processing.value = false;
        }
    };

    return {
        processing,
        error,
        createPayment,
        refundPayment,
    };
}
```

---

### Етап 8: Тестування

#### 8.1. Створення Unit тестів

**Команди:**

```bash
php artisan make:test --unit Payment/WayForPayGatewayTest --pest --no-interaction
php artisan make:test --unit Payment/PaymentDataTest --pest --no-interaction
php artisan make:test --unit Payment/PaymentResultTest --pest --no-interaction
```

#### 8.2. Створення Feature тестів

**Команди:**

```bash
php artisan make:test Payment/CreatePurchaseActionTest --pest --no-interaction
php artisan make:test Payment/ProcessRefundActionTest --pest --no-interaction
php artisan make:test Payment/TransferToCardActionTest --pest --no-interaction
php artisan make:test Payment/TransferToAccountActionTest --pest --no-interaction
php artisan make:test Payment/RegularPaymentActionTest --pest --no-interaction
php artisan make:test Payment/PaymentCallbackActionTest --pest --no-interaction
```

#### 8.3. Scope тестів

**Команди:**

```bash
php artisan make:test Payment/CreatePurchaseActionTest --pest --no-interaction
php artisan make:test Payment/ProcessRefundActionTest --pest --no-interaction
php artisan make:test Payment/TransferToCardActionTest --pest --no-interaction
php artisan make:test Payment/TransferToAccountActionTest --pest --no-interaction
php artisan make:test Payment/RegularPaymentActionTest --pest --no-interaction
php artisan make:test Payment/PaymentCallbackActionTest --pest --no-interaction
```

#### 8.3. Scope тестів

**Unit тести:**

- Валідація PaymentData DTO
- Валідація PaymentResult DTO
- Перевірка підпису WFP
- Мокування WFP SDK

**Feature тести:**

- Тестування Actions у ізоляції з мокуванням Gateway
- Створення purchase платежу через CreatePurchaseAction
- Повернення коштів через ProcessRefundAction
- Переказ на карту через TransferToCardAction
- Переказ на рахунок через TransferToAccountAction
- Регулярні платежі через відповідні Actions
- Обробка callback через ProcessPaymentCallbackAction
- Валідація запитів через Form Requests
- Перевірка збереження даних у БД
- Тестування Events та Listeners

---

### Етап 9: Додаткові функції

#### 9.1. Events & Listeners

**Events:**

- `PaymentCreated` - платіж створено
- `PaymentApproved` - платіж підтверджено
- `PaymentDeclined` - платіж відхилено
- `PaymentRefunded` - платіж повернено
- `RegularPaymentCharged` - регулярний платіж списано

**Listeners:**

- `SendPaymentConfirmationEmail` - відправка підтвердження
- `UpdateMentorSessionPaymentStatus` - оновлення статусу сесії
- `NotifyMentorAboutPayment` - сповіщення ментора
- `LogPaymentActivity` - логування активності

**Команди:**

```bash
php artisan make:event Payment/PaymentCreated --no-interaction
php artisan make:event Payment/PaymentApproved --no-interaction
php artisan make:event Payment/PaymentDeclined --no-interaction
php artisan make:event Payment/PaymentRefunded --no-interaction
php artisan make:listener Payment/SendPaymentConfirmationEmail --no-interaction
php artisan make:listener Payment/UpdateMentorSessionPaymentStatus --no-interaction
```

#### 9.2. Notifications

**Команди:**

```bash
php artisan make:notification Payment/PaymentSuccessNotification --no-interaction
php artisan make:notification Payment/PaymentFailedNotification --no-interaction
php artisan make:notification Payment/RefundProcessedNotification --no-interaction
```

#### 9.3. Jobs для регулярних платежів

**Команди:**

```bash
php artisan make:job Payment/ProcessScheduledPayments --no-interaction
php artisan make:job Payment/CheckPendingPayments --no-interaction
```

#### 9.4. Policies

**Команда:**

```bash
php artisan make:policy PaymentPolicy --model=Payment --no-interaction
```

---

## Структура файлів проєкту

```
app/
├── Actions/
│   └── Payment/
│       ├── CreatePurchaseAction.php
│       ├── ProcessRefundAction.php
│       ├── TransferToCardAction.php
│       ├── TransferToAccountAction.php
│       ├── ProcessPaymentCallbackAction.php
│       ├── ShowPaymentSuccessAction.php
│       ├── ShowPaymentDeclinedAction.php
│       ├── ShowCheckoutAction.php
│       ├── HandlePurchaseRequestAction.php
│       └── HandleRefundRequestAction.php
├── Contracts/
│   └── PaymentGatewayInterface.php
├── DataTransferObjects/
│   ├── PaymentData.php
│   └── PaymentResult.php
├── Enums/
│   ├── PaymentStatus.php
│   └── PaymentType.php
├── Events/
│   └── Payment/
│       ├── PaymentCreated.php
│       ├── PaymentApproved.php
│       ├── PaymentDeclined.php
│       └── PaymentRefunded.php
├── Http/
│   └── Requests/
│       └── Payment/
│           ├── CreatePurchaseRequest.php
│           ├── RefundPaymentRequest.php
│           ├── TransferToCardRequest.php
│           └── TransferToAccountRequest.php
├── Jobs/
│   └── Payment/
│       ├── ProcessScheduledPayments.php
│       └── CheckPendingPayments.php
├── Listeners/
│   └── Payment/
│       ├── SendPaymentConfirmationEmail.php
│       └── UpdateMentorSessionPaymentStatus.php
├── Models/
│   └── Payment.php (оновлена)
├── Notifications/
│   └── Payment/
│       ├── PaymentSuccessNotification.php
│       ├── PaymentFailedNotification.php
│       └── RefundProcessedNotification.php
├── Policies/
│   └── PaymentPolicy.php
├── Providers/
│   └── PaymentServiceProvider.php
└── Services/
    └── Payment/
        └── WayForPayGateway.php

config/
└── payment.php

database/
└── migrations/
    └── XXXX_XX_XX_XXXXXX_update_payments_table_for_wayforpay.php

routes/
└── web.php (оновити)

resources/
└── js/
    ├── Pages/
    │   ├── Payments/
    │   │   ├── Success.vue
    │   │   ├── Declined.vue
    │   │   └── Components/
    │   │       └── PaymentForm.vue
    │   └── MentorSessions/
    │       └── Checkout.vue
    └── Composables/
        └── usePayment.js

tests/
├── Feature/
│   └── Payment/
│       ├── CreatePurchaseActionTest.php
│       ├── ProcessRefundActionTest.php
│       ├── TransferToCardActionTest.php
│       ├── TransferToAccountActionTest.php
│       ├── RegularPaymentActionTest.php
│       └── PaymentCallbackActionTest.php
└── Unit/
    └── Payment/
        ├── WayForPayGatewayTest.php
        ├── PaymentDataTest.php
        └── PaymentResultTest.php
```

---

## Чеклист виконання

### Фаза 1: Підготовка (1-2 дні)

- [ ] Встановити WayForPay SDK
- [ ] Створити міграцію для оновлення таблиці `payments`
- [ ] Створити конфігураційний файл `config/payment.php`
- [ ] Додати змінні в `.env`
- [ ] Запустити міграції

### Фаза 2: Архітектура (2-3 дні)

- [ ] Створити інтерфейс `PaymentGatewayInterface`
- [ ] Створити DTO: `PaymentData`, `PaymentResult`
- [ ] Створити Enums: `PaymentStatus`, `PaymentType`
- [ ] Оновити модель `Payment`

### Фаза 3: Імплементація WFP (3-4 дні)

- [ ] Створити клас `WayForPayGateway`
- [ ] Реалізувати метод `initiatePurchase()`
- [ ] Реалізувати метод `refund()`
- [ ] Реалізувати метод `transferToCard()`
- [ ] Реалізувати метод `transferToAccount()`
- [ ] Реалізувати метод `initiateRegularPayment()`
- [ ] Реалізувати метод `chargeRegularPayment()`
- [ ] Реалізувати метод `getTransactionStatus()`
- [ ] Реалізувати метод `verifyCallback()`

### Фаза 4: Service Provider (0.5 дня)

- [ ] Створити `PaymentServiceProvider`
- [ ] Зареєструвати в `bootstrap/providers.php`

### Фаза 5: Actions (3-4 дні)

- [ ] Створити всі 12 Actions
- [ ] Імплементувати бізнес-логіку
- [ ] Додати обробку помилок
- [ ] Додати логування
- [ ] Відокремити логіку відображення від бізнес-логіки

### Фаза 6: Маршрути та інтеграція (1-2 дні)

- [ ] Створити Form Requests
- [ ] Додати маршрути в web.php
- [ ] Створити Inertia сторінки (Success, Declined, Checkout)
- [ ] Реалізувати валідацію

### Фаза 7: Vue 3 компоненти (1-2 дні)

- [ ] Створити компоненти для оплати (Checkout.vue)
- [ ] Створити сторінки успіху та відміни
- [ ] Створити usePayment композабл
- [ ] Інтегрувати з Inertia.js
- [ ] Додати frontend валідацію

### Фаза 8: Events & Listeners (1-2 дні)

- [ ] Створити Events
- [ ] Створити Listeners
- [ ] Створити Notifications
- [ ] Зареєструвати в EventServiceProvider

### Фаза 9: Jobs (1 день)

- [ ] Створити Jobs для регулярних платежів
- [ ] Налаштувати scheduler

### Фаза 10: Policy (0.5 дня)

- [ ] Створити PaymentPolicy
- [ ] Реалізувати методи авторизації

### Фаза 11: Тестування (3-5 днів)

- [ ] Написати Unit тести
- [ ] Написати Feature тести для Actions
- [ ] Запустити тести:
      `docker compose exec -it app php artisan test --filter=Payment`
- [ ] Досягти 100% покриття критичного коду
- [ ] Запустити mutation тести

### Фаза 12: Документація та QA (1-2 дні)

- [ ] Написати API документацію
- [ ] Додати PHPDoc
- [ ] Перевірити Laravel Pint: `vendor/bin/pint --dirty`
- [ ] Перевірити PHPStan/Larastan (якщо налаштовано)
- [ ] Code review

---

## Оцінка часу

**Загальний час: 18-28 робочих днів (3.5-5.5 тижнів)**

- Фаза 1: 1-2 дні
- Фаза 2: 2-3 дні
- Фаза 3: 3-4 дні
- Фаза 4: 0.5 дня
- Фаза 5: 3-4 дні
- Фаза 6: 1-2 дні
- Фаза 7: 1-2 дні
- Фаза 8: 1-2 дні
- Фаза 9: 1 день
- Фаза 10: 0.5 дня
- Фаза 11: 3-5 днів
- Фаза 12: 1-2 дні

---

## Команди для швидкого старту

```bash
# 1. Встановлення пакету
composer require wayforpay/php-sdk

# 2. Створення міграції
php artisan make:migration update_payments_table_for_wayforpay --no-interaction

# 3. Створення всіх класів
php artisan make:class Contracts/PaymentGatewayInterface --no-interaction
php artisan make:class DataTransferObjects/PaymentData --no-interaction
php artisan make:class DataTransferObjects/PaymentResult --no-interaction
php artisan make:enum PaymentStatus --no-interaction
php artisan make:enum PaymentType --no-interaction
php artisan make:class Services/Payment/WayForPayGateway --no-interaction
php artisan make:provider PaymentServiceProvider --no-interaction

# 4. Створення Actions
php artisan make:class Actions/Payment/CreatePurchaseAction --no-interaction
php artisan make:class Actions/Payment/ProcessRefundAction --no-interaction
php artisan make:class Actions/Payment/TransferToCardAction --no-interaction
php artisan make:class Actions/Payment/TransferToAccountAction --no-interaction
php artisan make:class Actions/Payment/ProcessPaymentCallbackAction --no-interaction
php artisan make:class Actions/Payment/ShowPaymentSuccessAction --no-interaction
php artisan make:class Actions/Payment/ShowPaymentDeclinedAction --no-interaction
php artisan make:class Actions/Payment/ShowCheckoutAction --no-interaction
php artisan make:class Actions/Payment/HandlePurchaseRequestAction --no-interaction
php artisan make:class Actions/Payment/HandleRefundRequestAction --no-interaction

# 5. Створення Form Requests
php artisan make:request Payment/CreatePurchaseRequest --no-interaction
php artisan make:request Payment/RefundPaymentRequest --no-interaction
php artisan make:request Payment/TransferToCardRequest --no-interaction
php artisan make:request Payment/TransferToAccountRequest --no-interaction

# 6. Створення тестів
php artisan make:test Payment/CreatePurchaseActionTest --pest --no-interaction
php artisan make:test Payment/ProcessRefundActionTest --pest --no-interaction

# 7. Запуск міграцій
docker compose exec -it app php artisan migrate

# 8. Запуск тестів
docker compose exec -it app php artisan test --filter=Payment

# 9. Форматування коду
docker compose exec -it app vendor/bin/pint --dirty
```

---

## Примітки та рекомендації

### Безпека

1. **Валідація callback** - завжди перевіряти підпис від WFP
2. **HTTPS** - використовувати тільки HTTPS для callback URL
3. **Rate limiting** - обмежити кількість запитів до API
4. **Idempotency** - забезпечити ідемпотентність операцій (використати
   `order_reference` як унікальний ключ)

### Логування

1. Логувати всі запити до WFP API
2. Логувати всі callback від WFP
3. Логувати помилки з повним стек-трейсом
4. Використовувати Laravel Telescope для моніторингу

### Тестування

1. Використовувати WFP sandbox для тестування
2. Мокувати WFP SDK у юніт-тестах
3. Створити фабрику для Payment моделі
4. Тестувати всі edge cases (помилки мережі, таймаути, невалідні підписи)

### Продуктивність

1. Використовувати Jobs для асинхронної обробки callback
2. Кешувати токени для регулярних платежів
3. Додати індекси на `transaction_id`, `order_reference`, `transaction_status`
4. Використовувати Eloquent для роботи з БД замість raw SQL

### Моніторинг

1. Налаштувати алерти для failed платежів
2. Відслідковувати conversion rate
3. Моніторити час відповіді WFP API

---

## Залежності

- `wayforpay/php-sdk` - офіційний SDK WayForPay
- Laravel 12.x
- Inertia.js v2
- Vue 3
- PostgreSQL
- Ziggy (для генерації routes у Vue)

---

## Контакти та ресурси

- **WFP API документація:** https://wiki.wayforpay.com/
- **WFP PHP SDK:** https://github.com/wayforpay/php-sdk
- **Laravel документація:** https://laravel.com/docs/12.x
- **WayForPay PHP SDK:** https://github.com/wayforpay/php-sdk

---

**Дата створення:** 4 листопада 2025  
**Версія:** 1.5  
**Автор:** GitHub Copilot  
**Проєкт:** MentorWizard
