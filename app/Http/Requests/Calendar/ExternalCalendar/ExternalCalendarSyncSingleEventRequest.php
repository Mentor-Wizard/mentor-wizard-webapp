<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar\ExternalCalendar;

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\ExternalCalendarEvent;
use App\Models\UserCalendarIntegration;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Calendar\Models\CalendarEvent;
use Override;

class ExternalCalendarSyncSingleEventRequest extends ExternalCalendarRequest
{
    /**
     * @return array{}
     */
    public function rules(): array
    {
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $provider = $this->resolveProvider();

            if (! $provider instanceof CalendarProviderEnum) {
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
                ->where('sync_status', CalendarSyncStatusEnum::ACTIVE)
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

    #[Override]
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            back()->with('error', $validator->errors()->first()),
        );
    }
}
