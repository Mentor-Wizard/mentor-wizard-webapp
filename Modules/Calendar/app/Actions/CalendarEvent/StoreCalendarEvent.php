<?php

declare(strict_types=1);

namespace Modules\Calendar\Actions\CalendarEvent;

use App\Actions\Calendar\CalendarEvent\CreateMentorSessionForCalendarEvent;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Calendar\DTO\CalendarEventData;
use Modules\Calendar\Enums\CalendarEventRoleEnum;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Http\Requests\CalendarEvent\StoreCalendarEventRequest;
use Modules\Calendar\Models\CalendarEvent;

class StoreCalendarEvent
{
    use AsController;

    public function handle(StoreCalendarEventRequest $request): RedirectResponse
    {
        $data = CalendarEventData::fromRequest($request);

        $calendarEvent = CalendarEvent::query()->create([
            'title'             => $data->title,
            'start_date_time'   => $data->startDateTime,
            'end_date_time'     => $data->endDateTime,
            'type'              => $data->type,
            'session_type'      => $data->sessionType,
            'web_link'          => $data->webLink,
            'description'       => $data->description,
            'status'            => $data->status,
            'date'              => $data->startDateTime->format('Y-m-d'),
            'mentor_program_id' => $data->mentorProgram->getKey(),
        ]);

        $calendarEvent->calendarEventUsers()->attach($data->mentorProgram->mentor_id, [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => $data->colour,
        ]);

        if ($data->mentorProgram->mentor_id !== $request->user()->getKey()) {
            $calendarEvent->calendarEventUsers()->attach($request->user()->getKey(), [
                'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
                'colour' => $data->colour,
            ]);
        }

        CreateMentorSessionForCalendarEvent::run($calendarEvent);

        $message = $calendarEvent->status === CalendarEventStatusEnum::CONFIRMED
            ? 'Your session has been booked and confirmed!'
            : 'Your session request has been submitted and is awaiting mentor confirmation.';

        return to_route('pages.calendar.index')->with('success', $message);
    }
}
