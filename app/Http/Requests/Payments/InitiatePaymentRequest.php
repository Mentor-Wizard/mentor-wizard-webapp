<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use App\Models\MentorProgram;
use App\Models\MentorSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
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
            'payable_type' => ['required', 'string', Rule::in(['mentor_session', 'mentor_program'])],
            'payable_id'   => ['required', 'integer', 'min:1'],
        ];
    }

    public function resolvePayableModel(): MentorSession|MentorProgram
    {
        $type = $this->string('payable_type')->value();

        if ($type === 'mentor_session') {
            return MentorSession::query()->findOrFail($this->integer('payable_id'));
        }

        return MentorProgram::query()->findOrFail($this->integer('payable_id'));
    }
}
