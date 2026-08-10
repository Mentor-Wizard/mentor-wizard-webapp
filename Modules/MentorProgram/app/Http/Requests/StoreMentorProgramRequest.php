<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Modules\MentorSession\Enums\MentorSessionDurationOptionsEnum;
use Modules\MentorSession\Enums\MentorSessionTypeEnum;

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
            'session_type_options'          => ['required', 'array', 'min:1'],
            'session_type_options.*'        => ['string', Rule::in(MentorSessionTypeEnum::values())],
            'session_duration'              => ['required', 'integer', Rule::in(MentorSessionDurationOptionsEnum::values())],
            'need_confirmation'             => ['nullable', 'boolean'],
            'start_time'                    => ['nullable', 'date_format:Y-m-d H:i'],
            'end_time'                      => ['nullable', 'date_format:Y-m-d H:i', 'after:start_time'],
        ];
    }
}
