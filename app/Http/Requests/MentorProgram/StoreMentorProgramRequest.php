<?php

declare(strict_types=1);

namespace App\Http\Requests\MentorProgram;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreMentorProgramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Assuming authorization is handled through middleware
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'slug'        => ['required', 'string', 'max:255', Rule::unique('mentor_programs', 'slug')],
            'cost'        => ['required', 'numeric', 'min:0'],
            'currency_id' => ['required', 'exists:currencies,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->name),
        ]);
    }
}
