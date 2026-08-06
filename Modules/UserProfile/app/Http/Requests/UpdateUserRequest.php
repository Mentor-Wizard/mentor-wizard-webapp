<?php

declare(strict_types=1);

namespace Modules\UserProfile\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class UpdateUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int|string, ValidationRule|Unique|string>|ValidationRule|string>
     */
    public function rules(): array
    {
        return [
            'username'        => [
                'required',
                'string',
                'min:5',
                'max:255',
            ],
            'email'       => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->getKey()),
            ],
            'avatar' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,gif',
                'max:1024',
            ],
        ];
    }
}
