<?php

declare(strict_types=1);

namespace App\Http\Requests\UserSchedule;

use App\Enums\UserScheduleRecordType;
use App\Services\UserSchedule\CheckUserScheduleOverlap;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Override;

class StoreBatchUserScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'schedules'                    => ['nullable', 'array'],
            'schedules.*.id'               => ['nullable', 'integer', 'exists:user_schedules,id'],
            'schedules.*.day_of_week'      => ['required', 'integer', 'between:0,6'],
            'schedules.*.start_time'       => ['required', 'date_format:H:i'],
            'schedules.*.end_time'         => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.type'             => ['required', Rule::in(UserScheduleRecordType::values())],
            'schedules.*.day_off_date'     => ['nullable', 'required_if:schedules.*.type,'.UserScheduleRecordType::DAY_OFF->value, 'date'],
            'delete_ids'                   => ['nullable', 'array'],
            'delete_ids.*'                 => ['integer', 'exists:user_schedules,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->any()) {
                return;
            }

            if ($validator->errors()->hasAny(['schedules', 'delete_ids'])) {
                return;
            }

            $this->validateScheduleOverlaps($validator);
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'schedules.required'                   => 'Schedules are required.',
            'schedules.array'                      => 'Schedules must be an array.',
            'schedules.*.id.exists'                => 'Schedule ID does not exist.',
            'schedules.*.day_of_week.required'     => 'Day of week is required.',
            'schedules.*.day_of_week.between'      => 'Day of week must be between 0 (Sunday) and 6 (Saturday).',
            'schedules.*.start_time.required'      => 'Start time is required.',
            'schedules.*.start_time.date_format'   => 'Start time must be in HH:MM format.',
            'schedules.*.end_time.required'        => 'End time is required.',
            'schedules.*.end_time.date_format'     => 'End time must be in HH:MM format.',
            'schedules.*.end_time.after'           => 'End time must be after start time.',
            'schedules.*.type.required'            => 'Schedule type is required.',
            'schedules.*.type.in'                  => 'Invalid schedule type selected.',
            'schedules.*.day_off_date.required_if' => 'Day off date is required when type is Day off.',
            'schedules.*.day_off_date.date'        => 'Day off date must be a valid date.',
            'delete_ids.array'                     => 'Delete IDs must be an array.',
            'delete_ids.*.exists'                  => 'Schedule ID to delete does not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $schedules = $this->input('schedules', []);

        foreach ($schedules as $index => $schedule) {
            if (isset($schedule['type']) && $schedule['type'] === UserScheduleRecordType::DAY_OFF->value) {
                $schedules[$index]['start_time'] = '00:00';
                $schedules[$index]['end_time'] = '23:59';
            }
        }

        $this->merge(['schedules' => $schedules]);
    }

    private function validateScheduleOverlaps(Validator $validator): void
    {
        $user = Auth::user();
        $schedules = $this->input('schedules') ?? [];
        $deleteIds = $this->input('delete_ids') ?? [];

        $overLappingErrors = new CheckUserScheduleOverlap($schedules, $deleteIds, $user?->id)
            ->verifyOverlappingErrors();

        foreach ($overLappingErrors as $errorPair) {
            foreach ($errorPair as $key => $message) {
                $validator->errors()->add($key, $message);
            }
        }
    }
}
