<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests\Register;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $allValidZones = array_merge(config('app.custom_timezones'), timezone_identifiers_list());

        return [
            'username' => ['required', 'string', 'max:255', 'min:5'],
            'email'    => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Password::defaults()],
            'timezone' => ['required', 'string', Rule::in($allValidZones)],
        ];
    }
}
