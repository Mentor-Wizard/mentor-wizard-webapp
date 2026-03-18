<?php

declare(strict_types=1);

namespace App\Http\Requests\MentorProgram;

use Illuminate\Validation\Rules\In;
use App\Enums\MentorSessionDurationOptionsEnum;
use App\Enums\MentorSessionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMentorProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<In|string>>
     */
    public function rules(): array
    {
        return [
            'name'                          => ['required', 'string', 'max:255'],
            'description'                   => ['required', 'string'],
            'cost'                          => ['required', 'numeric', 'min:0'],
            'currency_id'                   => ['required', 'exists:currencies,id'],
            'session_type_options'          => ['nullable', 'array'],
            'session_type_options.*'        => ['string', Rule::in(MentorSessionTypeEnum::values())],
            'session_duration'              => ['required', 'integer', Rule::in(MentorSessionDurationOptionsEnum::values())],
            'need_confirmation'             => ['boolean'],
        ];
    }
}
