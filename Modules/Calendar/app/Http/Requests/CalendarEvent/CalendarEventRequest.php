<?php

declare(strict_types=1);

namespace Modules\Calendar\Http\Requests\CalendarEvent;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;

abstract class CalendarEventRequest extends FormRequest
{
    /**
     * @return array{startDate: CarbonImmutable, endDate: CarbonImmutable}
     */
    protected function getFormattedDates(string $timezone): array
    {
        /** @var CarbonImmutable $startDate */
        $startDate = Date::createFromFormat(
            '!Y-m-d H:i',
            $this->input('fromDate').' '.$this->input('fromTime'), // @pest-mutate-ignore ConcatOperandRemoval
            $timezone
        );

        /** @var CarbonImmutable $endDate */
        $endDate = Date::createFromFormat(
            '!Y-m-d H:i',
            $this->input('toDate').' '.$this->input('toTime'), // @pest-mutate-ignore ConcatOperandRemoval
            $timezone
        );

        return ['startDate' => $startDate, 'endDate' => $endDate];
    }
}
