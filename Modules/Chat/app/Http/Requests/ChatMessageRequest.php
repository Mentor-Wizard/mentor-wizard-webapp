<?php

declare(strict_types=1);

namespace Modules\Chat\Http\Requests;

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
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain',
            ],
        ];
    }
}
