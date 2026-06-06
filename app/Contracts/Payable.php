<?php

declare(strict_types=1);

namespace App\Contracts;

interface Payable
{
    public function markPaid(): void;

    public function markUnpaid(): void;

    public function getPayableLabel(): string;
}
