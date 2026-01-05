<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class EditCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by the policy on the route (can:update)
        return true;
    }

    /**
     * Only allow updating of the web link.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'webLink' => ['sometimes', 'nullable', 'url', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'webLink.url' => 'Web link must be a valid URL.',
        ];
    }
}
