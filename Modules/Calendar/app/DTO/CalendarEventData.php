<?php

declare(strict_types=1);

namespace Modules\Calendar\DTO;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Http\Requests\CalendarEvent\StoreCalendarEventRequest;
use Modules\MentorProgram\Models\MentorProgram;
use Modules\MentorSession\Enums\MentorSessionTypeEnum;

readonly class CalendarEventData
{
    public function __construct(
        public string $title,
        public CarbonImmutable $startDateTime,
        public CarbonImmutable $endDateTime,
        public CalendarEventTypeEnum $type,
        public MentorSessionTypeEnum $sessionType,
        public ?string $webLink,
        public string $colour,
        public ?string $description,
        public CalendarEventStatusEnum $status,
        public MentorProgram $mentorProgram,
    ) {}

    public static function fromRequest(StoreCalendarEventRequest $request): self
    {
        $validated = $request->validated();

        $timezone = $request->user()->profile->timezone;

        /** @var CarbonImmutable $startDateTime */
        $startDateTime = Date::createFromFormat('Y-m-d H:i', $validated['fromDate'].' '.$validated['fromTime'], $timezone)->timezone('UTC');

        /** @var CarbonImmutable $endDateTime */
        $endDateTime = Date::createFromFormat('Y-m-d H:i', $validated['toDate'].' '.$validated['toTime'], $timezone)->timezone('UTC');

        /** @var MentorProgram $mentorProgram */
        $mentorProgram = MentorProgram::query()->findOrFail((int) $validated['mentor_program_id']);

        $type = $validated['type'] === CalendarEventTypeEnum::GROUP->value
            ? CalendarEventTypeEnum::GROUP
            : CalendarEventTypeEnum::INDIVIDUAL;

        $status = $mentorProgram->need_confirmation
            ? CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION
            : CalendarEventStatusEnum::CONFIRMED;

        return new self(
            title: (string) Arr::get($validated, 'title'),
            startDateTime: $startDateTime,
            endDateTime: $endDateTime,
            type: $type,
            sessionType: MentorSessionTypeEnum::from($validated['session_type']),
            webLink: Arr::get($validated, 'webLink'),
            colour: (string) Arr::get($validated, 'colour'),
            description: Arr::get($validated, 'description'),
            status: $status,
            mentorProgram: $mentorProgram,
        );
    }
}
