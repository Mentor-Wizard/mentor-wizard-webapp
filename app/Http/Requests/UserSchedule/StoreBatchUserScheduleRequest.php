<?php

declare(strict_types=1);

namespace App\Http\Requests\UserSchedule;

use App\Enums\UserScheduleRecordType;
use App\Models\UserSchedule;
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
            'schedules.*.timezone'         => ['required', 'string'],
            'delete_ids'                   => ['nullable', 'array'],
            'delete_ids.*'                 => ['integer', 'exists:user_schedules,id'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->any()) {
                return;
            }

            $schedules = $this->input('schedules', []);
            $userId = Auth::id();
            $deleteIds = $this->input('delete_ids', []);

            // Verify user owns all schedules being deleted
            if (! empty($deleteIds)) {
                $ownedCount = UserSchedule::query()
                    ->whereIn('id', $deleteIds)
                    ->where('user_id', $userId)
                    ->count();

                if ($ownedCount !== count($deleteIds)) {
                    $validator->errors()->add('delete_ids', 'You can only delete your own schedules.');

                    return;
                }
            }

            // Verify user owns all schedules being updated
            foreach ($schedules as $index => $schedule) {
                if (isset($schedule['id'])) {
                    $exists = UserSchedule::query()
                        ->where('id', $schedule['id'])
                        ->where('user_id', $userId)
                        ->exists();

                    if (! $exists) {
                        $validator->errors()->add(sprintf('schedules.%s.id', $index), 'You can only update your own schedules.');

                        return;
                    }
                }
            }

            $schedulesByDay = [];
            foreach ($schedules as $index => $schedule) {
                if ($schedule['type'] === UserScheduleRecordType::DAY_OFF->value) {
                    continue;
                }

                $dayOfWeek = $schedule['day_of_week'];
                if (! isset($schedulesByDay[$dayOfWeek])) {
                    $schedulesByDay[$dayOfWeek] = [];
                }

                $schedulesByDay[$dayOfWeek][] = [
                    'index'      => $index,
                    'id'         => $schedule['id'] ?? null,
                    'start_time' => $schedule['start_time'],
                    'end_time'   => $schedule['end_time'],
                ];
            }

            // Check for overlaps within the submitted schedules
            foreach ($schedulesByDay as $dayOfWeek => $daySchedules) {
                $schedulesCount = count($daySchedules);
                for ($i = 0; $i < $schedulesCount; $i++) {
                    for ($j = $i + 1; $j < $schedulesCount; $j++) {
                        $schedule1 = $daySchedules[$i];
                        $schedule2 = $daySchedules[$j];

                        if (
                            $schedule1['start_time'] < $schedule2['end_time']
                            && $schedule1['end_time'] > $schedule2['start_time']
                        ) {
                            $validator->errors()->add(
                                sprintf('schedules.%s.start_time', $schedule1['index']),
                                'This time slot overlaps with another schedule on the same day.'
                            );

                            return;
                        }
                    }
                }

                // Check for overlaps with existing schedules (excluding ones being deleted or updated)
                foreach ($daySchedules as $schedule) {
                    $existingOverlaps = UserSchedule::query()
                        ->where('user_id', $userId)
                        ->where('day_of_week', $dayOfWeek)
                        ->where('type', '!=', UserScheduleRecordType::DAY_OFF->value)
                        ->when($schedule['id'], fn ($q) => $q->where('id', '!=', $schedule['id']))
                        ->unless(empty($deleteIds), fn ($q) => $q->whereNotIn('id', $deleteIds))
                        ->where(function ($query) use ($schedule): void {
                            $query->where(function ($q) use ($schedule): void {
                                $q->where('start_time', '<', $schedule['end_time'])
                                    ->where('end_time', '>', $schedule['start_time']);
                            });
                        })
                        ->exists();

                    if ($existingOverlaps) {
                        $validator->errors()->add(
                            sprintf('schedules.%s.start_time', $schedule['index']),
                            'This time slot overlaps with an existing schedule.'
                        );

                        return;
                    }
                }
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
            'schedules.*.timezone.required'        => 'Timezone is required.',
            'schedules.*.timezone.timezone'        => 'Invalid timezone.',
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
}
