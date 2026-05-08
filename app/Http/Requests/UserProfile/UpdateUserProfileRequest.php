<?php

declare(strict_types=1);

namespace App\Http\Requests\UserProfile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<int|string, ValidationRule|string>|string>
     */
    public function rules(): array
    {
        return [
            'name'        => [
                'nullable',
                'string',
                'min:3',
                'max:50',
            ],
            'last_name'   => [
                'nullable',
                'string',
                'min:3',
                'max:50',
            ],
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
            'phone'       => [
                'nullable',
                'regex:/^\+\d{11,15}$/',
            ],
        ];
    }

    #[Override]
    protected function prepareForValidation()
    {
        if (! empty($this->phone)) {
            $this->merge([
                'phone' => str_replace(' ', '', $this->phone),
            ]);
        }
    }
}
