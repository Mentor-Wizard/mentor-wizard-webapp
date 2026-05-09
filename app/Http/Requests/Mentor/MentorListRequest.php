<?php

declare(strict_types=1);

namespace App\Http\Requests\Mentor;

use Illuminate\Foundation\Http\FormRequest;

class MentorListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'filter'                 => ['sometimes', 'array'],
            'filter.stacks'          => ['sometimes', 'string', 'max:500'],
            'filter.languages'       => ['sometimes', 'string', 'max:500'],
            'filter.experience'      => ['sometimes', 'string', 'in:entry,mid,senior,expert'],
            'filter.rating'          => ['sometimes', 'numeric', 'min:1', 'max:5'],
            'filter.rate'            => ['sometimes', 'array'],
            'filter.rate.min'        => ['sometimes', 'numeric', 'min:0', 'max:10000'],
            'filter.rate.max'        => ['sometimes', 'numeric', 'min:0', 'max:10000', 'gte:filter.rate.min'],
            'filter.cost'            => ['sometimes', 'array'],
            'filter.cost.min'        => ['sometimes', 'numeric', 'min:0', 'max:10000'],
            'filter.cost.max'        => ['sometimes', 'numeric', 'min:0', 'max:10000', 'gte:filter.cost.min'],
            'sort'                   => ['sometimes', 'string', 'in:id,-id,rate,-rate,experience_started_at,-experience_started_at'],
            'page'                   => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
