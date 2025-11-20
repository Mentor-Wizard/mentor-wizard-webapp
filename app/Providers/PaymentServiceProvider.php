<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\PaymentGatewayInterface;
use App\Services\Payment\PaymentGatewayFactory;
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
        $this->app->singleton(PaymentGatewayFactory::class);

        // Binding an interface to a specific implementation via a factory
        $this->app->bind(fn ($app): PaymentGatewayInterface => $app->make(PaymentGatewayFactory::class)->make());
    }

    #[Override]
    public function provides(): array
    {
        return [
            PaymentGatewayFactory::class,
            PaymentGatewayInterface::class,
        ];
    }
}
