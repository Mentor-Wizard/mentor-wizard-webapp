<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\Payable;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\RoleEnum;
use App\Models\MentorSession;
use App\Models\Payment;
use App\Models\User;

final class PaymentPolicy
{
    public function initiate(User $user, Payable $payable): bool
    {
        if ($payable instanceof MentorSession) {
            if ($payable->menti_id !== $user->getKey()) {
                return false;
            }

            $calendarEvent = $payable->calendarEvent()->first();
            if ($calendarEvent === null) {
                return false;
            }

            return $calendarEvent->status === CalendarEventStatusEnum::CONFIRMED
                || $calendarEvent->status === CalendarEventStatusEnum::PENDING_PAYMENT;
        }

        return false;
    }

    public function view(User $user, Payment $payment): bool
    {
        $payable = $payment->payable;

        if ($payable instanceof MentorSession) {
            if ($payable->menti_id === $user->getKey()) {
                return true;
            }

            return $payable->mentor_id === $user->getKey();
        }

        return false;
    }

    public function refund(User $user): bool
    {
        return $user->hasAnyRole([RoleEnum::ADMIN, RoleEnum::SUPER_ADMIN]);
    }
}
