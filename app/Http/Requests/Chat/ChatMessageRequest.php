<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class ChatMessageRequest extends FormRequest
{
    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string'],
            'files'   => ['nullable', 'array', 'max:5'],
            'files.*' => [
                'file',
                'max:2048',
            ],
        ];
    }
}
