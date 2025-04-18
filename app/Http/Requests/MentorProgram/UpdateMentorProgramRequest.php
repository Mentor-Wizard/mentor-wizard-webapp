<?php

declare(strict_types=1);

namespace App\Http\Requests\MentorProgram;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateMentorProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'slug' => [
                'required', 
                'string',
                'max:255',
                Rule::unique('mentor_programs')->ignore($this->route('mentorProgram'))
            ],
            'cost' => ['required', 'numeric', 'min:0'],
            'currency_id' => ['required', 'exists:currencies,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}