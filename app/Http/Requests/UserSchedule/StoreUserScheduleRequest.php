<?php

declare(strict_types=1);

namespace App\Http\Requests\UserSchedule;

use App\Enums\UserScheduleRecordType;
use App\Models\UserSchedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Override;

class StoreUserScheduleRequest extends FormRequest
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
            'day_of_week'  => ['required', 'integer', 'between:0,6'],
            'start_time'   => ['required', 'date_format:H:i'],
            'end_time'     => ['required', 'date_format:H:i', 'after:start_time'],
            'type'         => ['required', Rule::in(UserScheduleRecordType::values())],
            'day_off_date' => ['nullable', 'date_format:Y-m-d', 'required_if:type,'.UserScheduleRecordType::DAY_OFF->value, 'date'],
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

            // Skip overlap validation for day-off type
            if ($this->input('type') === UserScheduleRecordType::DAY_OFF->value) {
                return;
            }

            $userId = Auth::id();
            $dayOfWeek = $this->input('day_of_week');
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');

            // Check for overlapping schedules (exclude day-off schedules from overlap check)
            $overlapping = UserSchedule::query()
                ->where('user_id', $userId)
                ->where('day_of_week', $dayOfWeek)
                ->where('type', '!=', UserScheduleRecordType::DAY_OFF->value)
                ->where(function ($query) use ($startTime, $endTime): void {
                    $query->where(function ($q) use ($startTime, $endTime): void {
                        $q->where('start_time', '<', $endTime)
                            ->where('end_time', '>', $startTime);
                    });
                })
                ->exists();

            if ($overlapping) {
                $validator->errors()->add('start_time', 'This time slot overlaps with an existing schedule.');
            }
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
            'day_of_week.required'     => 'Day of week is required.',
            'day_of_week.between'      => 'Day of week must be between 0 (Sunday) and 6 (Saturday).',
            'start_time.required'      => 'Start time is required.',
            'start_time.date_format'   => 'Start time must be in HH:MM format.',
            'end_time.required'        => 'End time is required.',
            'end_time.date_format'     => 'End time must be in HH:MM format.',
            'end_time.after'           => 'End time must be after start time.',
            'type.required'            => 'Schedule type is required.',
            'type.in'                  => 'Invalid schedule type selected.',
            'day_off_date.required_if' => 'Day off date is required when type is Day off.',
            'day_off_date.date'        => 'Day off date must be a valid date.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default times for day-off type
        if ($this->input('type') === UserScheduleRecordType::DAY_OFF->value) {
            $this->merge([
                'start_time' => '00:00',
                'end_time'   => '23:59',
            ]);
        }
    }
}
