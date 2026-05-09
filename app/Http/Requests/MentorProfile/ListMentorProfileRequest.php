<?php

declare(strict_types=1);

namespace App\Http\Requests\MentorProfile;

use Illuminate\Foundation\Http\FormRequest;

class ListMentorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }
}
