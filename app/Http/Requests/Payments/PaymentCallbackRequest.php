<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class PaymentCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->has('orderReference')) {
            $this->merge(['order_reference' => $this->input('orderReference')]);
        }
    }
}
