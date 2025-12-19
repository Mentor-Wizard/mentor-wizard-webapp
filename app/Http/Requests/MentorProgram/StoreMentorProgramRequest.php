<?php

declare(strict_types=1);

namespace App\Http\Requests\MentorProgram;

use Illuminate\Foundation\Http\FormRequest;

class StoreMentorProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'cost'        => ['required', 'numeric', 'min:0'],
            'currency_id' => ['required', 'exists:currencies,id'],
        ];
    }
}
