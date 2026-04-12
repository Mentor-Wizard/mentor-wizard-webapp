<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\UserCalendarIntegration;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator as ValidatorImpl;

class ExternalCalendarSyncSingleEventRequest extends ExternalCalendarRequest
{
    public function rules(): array
    {
        return [];
    }

    public function withValidator(ValidatorImpl $validator): void
    {
        $validator->after(function (ValidatorImpl $validator): void {
            $provider = $this->resolveProvider();

            if ($provider === null) {
                $validator->errors()->add('provider', 'Invalid calendar provider.');

                return;
            }

            $user = $this->user();
            /** @var CalendarEvent $calendarEvent */
            $calendarEvent = $this->route('calendarEvent');

            if (! $calendarEvent->calendarEventUsers()->where('users.id', $user->getKey())->exists()) {
                $validator->errors()->add('event', 'You are not authorized for this calendar event.');

                return;
            }

            $integrationExists = UserCalendarIntegration::query()
                ->where('user_id', $user->getKey())
                ->where('provider', $provider)
                ->where('sync_status', CalendarSyncStatusEnum::Active)
                ->exists();

            if (! $integrationExists) {
                $validator->errors()->add('integration', 'No active calendar integration found for this provider.');

                return;
            }

            $alreadySynced = ExternalCalendarEvent::query()
                ->where('calendar_event_id', $calendarEvent->getKey())
                ->where('user_id', $user->getKey())
                ->where('provider', $provider)
                ->exists();

            if ($alreadySynced) {
                $validator->errors()->add('sync', 'This event is already synced to the selected calendar.');
            }
        });
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): never
    {
        throw new HttpResponseException(
            back()->with('error', $validator->errors()->first()),
        );
    }
}
