<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'min:5', 'max:50'],
            'last_name' => ['required', 'string', 'min:5', 'max:50'],
            'linkedin' => ['nullable','string', 'max:200'],
            'telegram' => ['nullable','string', 'max:100'],
            'whatsapp' => ['nullable','string', 'max:100'],
            'description' => ['nullable','string', 'max:1000'],
            'phone' => [
                'nullable',
                'string',
                'regex:/^\+\d{11,15}$/',
            ],
            'email'     => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'avatar' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,gif',
                'max:1024',
            ],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'phone' => str_replace(' ', '', $this->phone),
        ]);
    }
}
