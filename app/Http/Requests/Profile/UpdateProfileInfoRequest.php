<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileInfoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'linkedin'    => [
                'nullable',
                'string',
                'max:200',
                'regex:/^https:\/\/(www\.)?linkedin\.com\/.+$/i',
            ],
            'telegram'    => [
                'nullable',
                'string',
                'max:100',
                'regex:/^https:\/\/(www\.)?t\.me\/.+$/i',
            ],
            'whatsapp'    => [
                'nullable',
                'string',
                'max:100',
                'regex:/^https:\/\/(www\.)?wa\.me\/.+$/i',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'phone'       => [
                'nullable',
                'regex:/^\+\d{11,15}$/',
            ],
        ];
    }

    protected function prepareForValidation()
    {
        if (! empty($this->phone)) {
            $this->merge([
                'phone' => str_replace(' ', '', $this->phone),
            ]);
        }
    }
}
