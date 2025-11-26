<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\PaymentGatewayInterface;
use App\Services\Payment\PaymentGatewayBuilder;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Override;

class PaymentServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayBuilder::class);

        // Binding an interface to a specific implementation via a payment gateway builder
        $this->app->bind(fn ($app): PaymentGatewayInterface => $app->make(PaymentGatewayBuilder::class)->make());
    }

    #[Override]
    public function provides(): array
    {
        return [
            PaymentGatewayBuilder::class,
            PaymentGatewayInterface::class,
        ];
    }
}
