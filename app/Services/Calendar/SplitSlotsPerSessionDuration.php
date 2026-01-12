<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

class SplitSlotsPerSessionDuration
{
    /**
     * @var array<string, array<int, array{start: CarbonInterface, end: CarbonInterface}>>
     */
    protected array $splitSlots = [];

    public function __construct(
        /** @var array<int, array{start: CarbonInterface, end: CarbonInterface}> $slots */
        private readonly array $slots,
        private readonly int $sessionDurationInMinutes,
        private readonly string $timezone,
    ) {}

    /**
     * @return array<string, array<int, array{start: CarbonInterface, end: CarbonInterface}>>
     */
    /** @phpstan-ignore-next-line complexity.functionLike */
    public function getSplitSlots(): array
    {
        foreach ($this->slots as $slot) {
            $slotDuration = (int) $slot['start']->diffInMinutes($slot['end']);

            if ($slotDuration > $this->sessionDurationInMinutes) {
                $periods = CarbonPeriod::create($slot['start'],
                    $this->sessionDurationInMinutes.' minutes', $slot['end'])
                    ->excludeStartDate()
                    ->excludeEndDate();
                foreach ($periods as $period) {
                    $date = $period->timezone($this->timezone)->format('Y-m-d');
                    $slotElement = [
                        'start' => $period->copy()->subMinutes($this->sessionDurationInMinutes),
                        'end'   => $period,
                    ];
                    if (isset($this->splitSlots[$date])) {
                        $this->splitSlots[$date][] = $slotElement;
                    } else {
                        $this->splitSlots[$date] = [$slotElement];
                    }
                }
            } elseif ($slotDuration === $this->sessionDurationInMinutes) {
                $this->splitSlots[$slot['start']->format('Y-m-d')]
                = [[
                    'start' => $slot['start'],
                    'end'   => $slot['end'],

                ]];
            }
        }

        return $this->splitSlots;
    }
}
