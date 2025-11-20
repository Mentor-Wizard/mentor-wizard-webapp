<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\DTO\Payment\PaymentRequestDataDTO;
use App\DTO\Payment\PaymentResponseDataDTO;
use App\Enums\Payment\PaymentStatusEnum;
use App\Enums\Payment\PaymentTypeEnum;
use App\Interfaces\PaymentGatewayInterface;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Facades\Log;
use WayForPay\SDK\Collection\ProductCollection;
use WayForPay\SDK\Credential\AccountSecretCredential;
use WayForPay\SDK\Domain\Client;
use WayForPay\SDK\Domain\Product;
use WayForPay\SDK\Exception\WayForPaySDKException;
use WayForPay\SDK\Helper\SignatureHelper;
use WayForPay\SDK\Wizard\CheckWizard;
use WayForPay\SDK\Wizard\PurchaseWizard;
use WayForPay\SDK\Wizard\RefundWizard;

class WayForPayGateway implements PaymentGatewayInterface
{
    private readonly AccountSecretCredential $credential;

    public function __construct(
        private readonly string $merchantAccount,
        private readonly string $merchantSecretKey,
        private readonly string $merchantDomain,
        private readonly string $serviceUrl,
        private readonly string $returnUrl,
    ) {
        $this->credential = new AccountSecretCredential(
            $this->merchantAccount,
            $this->merchantSecretKey
        );
    }

    public function initiatePurchase(PaymentRequestDataDTO $data): PaymentResponseDataDTO
    {
        try {

            // purchase через widget/redirect
            return $this->createPurchase($data);
        } catch (WayForPaySDKException $e) {
            Log::error('WayForPay purchase failed', [
                'error'           => $e->getMessage(),
                'order_reference' => $data->orderReference,
                'code'            => $e->getCode(),
                'trace'           => $e->getTraceAsString(),
            ]);

            return PaymentResponseDataDTO::failure($e->getMessage());
        } catch (Exception $e) {
            Log::error('WayForPay purchase unexpected error', [
                'error'           => $e->getMessage(),
                'order_reference' => $data->orderReference,
                'trace'           => $e->getTraceAsString(),
            ]);

            return PaymentResponseDataDTO::failure('Payment initialization failed: '.$e->getMessage());
        }
    }

    public function refund(string $transactionId, float $amount, string $comment = ''): PaymentResponseDataDTO
    {
        try {
            $wizard = RefundWizard::get($this->credential)
                ->setOrderReference($transactionId)
                ->setAmount($amount)
                ->setCurrency('UAH')
                ->setComment($comment ?: 'Refund requested');

            $apiResponse = $wizard->getRequest()->send();

            // Check response status
            $status = $this->mapWayForPayStatus($apiResponse->getTransactionStatus());

            return PaymentResponseDataDTO::success([
                'transaction_id' => $transactionId,
                'amount'         => (int) ($amount * 100),
                'status'         => $status->value,
                'payment_type'   => PaymentTypeEnum::Refund->value,
                'payment_system' => 'wayforpay',
                'reason_code'    => $apiResponse->getReason()->getCode(),
                'reason'         => $apiResponse->getReason()->getMessage(),
            ]);
        } catch (WayForPaySDKException $e) {
            Log::error('WayForPay refund failed', [
                'error'          => $e->getMessage(),
                'transaction_id' => $transactionId,
                'amount'         => $amount,
                'code'           => $e->getCode(),
            ]);

            return PaymentResponseDataDTO::failure($e->getMessage());
        } catch (Exception $e) {
            Log::error('WayForPay refund unexpected error', [
                'error'          => $e->getMessage(),
                'transaction_id' => $transactionId,
                'amount'         => $amount,
            ]);

            return PaymentResponseDataDTO::failure('Refund processing failed: '.$e->getMessage());
        }
    }

    public function getTransactionStatus(string $orderReference): PaymentResponseDataDTO
    {
        try {
            $wizard = CheckWizard::get($this->credential)
                ->setOrderReference($orderReference);

            $apiResponse = $wizard->getRequest()->send();
            $order = $apiResponse->getOrder();

            $status = $this->mapWayForPayStatus($order->getStatus());

            return PaymentResponseDataDTO::success([
                'order_reference' => $orderReference,
                'status'          => $status->value,
                'transaction_id'  => $order->getOrderReference(),
                'amount'          => (int) ($order->getAmount() * 100),
                'currency'        => $order->getCurrency(),
                'card_pan'        => $order->getCardPan(),
                'card_type'       => $order->getCardType(),
                'payment_system'  => 'wayforpay',
                'reason_code'     => $order->getReason()->getCode(),
                'reason'          => $order->getReason()->getMessage(),
            ]);
        } catch (WayForPaySDKException $e) {
            Log::error('WayForPay status check failed', [
                'error'           => $e->getMessage(),
                'order_reference' => $orderReference,
                'code'            => $e->getCode(),
            ]);

            return PaymentResponseDataDTO::failure($e->getMessage());
        } catch (Exception $e) {
            Log::error('WayForPay status check unexpected error', [
                'error'           => $e->getMessage(),
                'order_reference' => $orderReference,
            ]);

            return PaymentResponseDataDTO::failure('Status check failed: '.$e->getMessage());
        }
    }

    public function verifyCallback(array $data): bool
    {
        try {
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

            $signatureParams = [];
            foreach ($signatureFields as $field) {
                if (isset($data[$field])) {
                    $signatureParams[] = $data[$field];
                }
            }

            $expectedSignature = SignatureHelper::calculateSignature(
                $signatureParams,
                $this->merchantSecretKey
            );

            $receivedSignature = $data['merchantSignature'] ?? '';

            $isValid = hash_equals($expectedSignature, $receivedSignature);

            if (! $isValid) {
                Log::warning('WayForPay callback signature mismatch', [
                    'expected'        => $expectedSignature,
                    'received'        => $receivedSignature,
                    'order_reference' => $data['orderReference'] ?? null,
                ]);
            }

            return $isValid;
        } catch (Exception $exception) {
            Log::error('WayForPay callback verification error', [
                'error' => $exception->getMessage(),
                'data'  => $data,
            ]);

            return false;
        }
    }

    private function createPurchase(PaymentRequestDataDTO $data): PaymentResponseDataDTO
    {
        $client = new Client(
            $data->clientFirstName ?? '',
            $data->clientLastName ?? '',
            $data->clientEmail ?? '',
            $data->clientPhone ?? '',
            $data->clientCountry ?? 'UA'
        );

        $products = new ProductCollection;
        $products->add(new Product(
            $data->productName,
            $data->productPrice,
            $data->productCount
        ));

        $wizard = PurchaseWizard::get($this->credential)
            ->setOrderReference($data->orderReference)
            ->setAmount($data->amount)
            ->setCurrency($data->currency)
            ->setProducts($products)
            ->setClient($client)
            ->setOrderDate(CarbonImmutable::now())
            ->setMerchantDomainName($this->merchantDomain)
            ->setReturnUrl(url($this->returnUrl))
            ->setServiceUrl(url($this->serviceUrl));

        $form = $wizard->getForm();

        // Data for Frontend to render purchase form
        $widgetData = $form->getData();

        return PaymentResponseDataDTO::success([
            'widget_data'     => $widgetData, // Data for WayForPay widget
            'widget_url'      => $form->getEndpoint()->getUrl(), // URL endpoint
            'order_reference' => $data->orderReference,
            'payment_type'    => $data->paymentType->value,
            'payment_system'  => 'wayforpay',
            'amount'          => (int) ($data->amount * 100),
            'currency'        => $data->currency,
        ]);
    }

    // TODO: Implement regular payment processing through ChargeWizard

    private function mapWayForPayStatus(string|int $status): PaymentStatusEnum
    {
        return match ($status) {
            'Approved', 1100 => PaymentStatusEnum::Approved,
            'Declined' => PaymentStatusEnum::Declined,
            'Refunded', 'RefundInProcessing' => PaymentStatusEnum::Refunded,
            'InProcessing', 'WaitingAuthComplete' => PaymentStatusEnum::Processing,
            'Pending' => PaymentStatusEnum::Pending,
            'Expired' => PaymentStatusEnum::Failed, // Expired == Failed
            default   => PaymentStatusEnum::Failed,
        };
    }
}
