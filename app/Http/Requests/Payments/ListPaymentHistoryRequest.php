<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use App\Enums\PaymentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ListPaymentHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, Enum[]|string[]|string[]>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::enum(PaymentStatusEnum::class)],
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }
}
