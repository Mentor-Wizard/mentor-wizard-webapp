<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleGuardEnum;
use App\Http\Resources\EventDayViewResource;
use App\Http\Resources\EventMonthViewResource;
use App\Http\Resources\EventWeekViewResource;
use App\Observers\UserObserver;
use Carbon\CarbonPeriod;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\HasName;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read UserProfile $profile
 * @property string $username
 *
 * @mixin IdeHelperUser
 */
#[ObservedBy(UserObserver::class)]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable implements HasMedia, HasName, MustVerifyEmail
{
    use HasFactory;
    use HasRoles;
    use InteractsWithMedia;
    use Notifiable;

    public const int MIN_PASSWORD_LENGTH = 8;

    public const int DEFAULT_MENTOR_PAGE_PAGINATION = 10;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected array $guard_name = [
        'web',
        RoleGuardEnum::USER->value,
        RoleGuardEnum::ADMIN->value,
        RoleGuardEnum::SUPER_ADMIN->value,
        RoleGuardEnum::MENTOR->value,
        RoleGuardEnum::MENTI->value,
        RoleGuardEnum::COACH->value,
    ];

    protected $visible = [
        'id',
        'username',
        'email',
        'created_at',
        'updated_at',
        'profile',
        'media',
    ];

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function mentorProfile(): ?HasOne
    {
        return $this->hasOne(MentorProfile::class);
    }

    public function mentiProgramProgress(): ?HasOne
    {
        return $this->hasOne(MentorProgramBlockProgress::class);
    }

    public function mentorReviews(): HasMany
    {
        return $this->hasMany(MentorReview::class, 'mentor_id');
    }

    public function reviewsByMenti(): HasMany
    {
        return $this->hasMany(MentorReview::class, 'menti_id');
    }

    public function mentorPrograms(): HasMany
    {
        return $this->hasMany(MentorProgram::class, 'mentor_id');
    }

    public function mentorSessions(): HasMany
    {
        return $this->hasMany(MentorSession::class, 'mentor_id');
    }

    public function mentiSessions(): HasMany
    {
        return $this->hasMany(MentorSession::class, 'menti_id');
    }

    public function mentorChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'mentor_id');
    }

    public function mentiChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'menti_id');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    public function coachChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'coach_id');
    }

    public function rating(): Attribute
    {
        return Attribute::make(
            get: fn (): float => (float) $this->mentorReviews()->avg('rating'),
        );
    }

    public function getFilamentName(): string
    {
        return $this->username ?? '';
    }

    /**
     * Get formatted events for month view
     *
     * @return array{calendarView: array, hasEventsBefore: bool, hasEventsAfter: bool}
     */
    public function getMonthFormattedEvents(string $date, string $timezone = 'Europe/Kyiv'): array
    {
        $dateConfig = $this->prepareDateConfiguration($date, $timezone);
        $events = $this->getFormattedEventsForPeriod($dateConfig['startDate'], $dateConfig['endDate'], $date);
        $calendarView = $this->buildCalendarView($dateConfig['monthDates'], $events);

        return [
            'calendarView'    => $calendarView,
            'hasEventsBefore' => $this->hasEventsBeforeDate($dateConfig['startDate']),
            'hasEventsAfter'  => $this->hasEventsAfterDate($dateConfig['endDate']),
        ];
    }

    public function getWeekFormattedEvents(string $date, string $timezone = 'Europe/Kyiv'): array
    {
        $appTimezone = config('app.timezone');
        $setTimeZone = $timezone !== $appTimezone ? $timezone : null;
        $startDate = Carbon::parse($date, $setTimeZone)->startOfWeek();
        $endDate = Carbon::parse($date, $setTimeZone)->endOfWeek();
        $todayDate = Carbon::parse($date, $setTimeZone);
        $userEvents = $this->events();
        $userEventsForCalendar = clone $userEvents;

        $events = $userEvents->whereBetween('start_date_time',
            [$startDate, $endDate])->orderBy('start_date_time')->get()
            ->map(fn (Event $dayEvent): array => new EventWeekViewResource($dayEvent, $setTimeZone)->resolve());
        $daysEvents = $userEventsForCalendar->pluck('date')->unique()->toArray();
        $weekDays = CarbonPeriod::create($startDate, '1 day', $endDate);
        $calendarView = [];

        foreach ($weekDays as $weekDay) {
            $payload = ['date' => $weekDay->format('Y-m-d')];
            if (Carbon::parse($weekDay)->isSameMonth($todayDate)) {
                $payload['isCurrentMonth'] = true;
            }

            if (Carbon::parse($weekDay)->isSameDay($todayDate)) {
                $payload['isSelected'] = true;
            }

            if (Carbon::now()->isSameDay(Carbon::parse($weekDay))) {
                $payload['isToday'] = true;
            }

            if (in_array($weekDay->format('Y-m-d'), $daysEvents)) {
                $payload['hasEvent'] = true;
            }

            $calendarView[] = $payload;
        }

        return [
            'events'       => $events,
            'calendarView' => $calendarView,
        ];
    }

    public function getDailyFormattedEvents(string $date, string $timezone = 'Europe/Kyiv'): array
    {
        $dateConfig = $this->prepareDailyDateConfiguration($date, $timezone);
        $events = $this->getDailyEvents($dateConfig['todayDate'], $dateConfig['tomorrowDate'], $timezone);
        $calendarView = $this->buildDailyCalendarView($dateConfig['months'], $dateConfig['todayDate'], $dateConfig['daysEvents'], $timezone);

        return [
            'events'       => $events,
            'calendarView' => $calendarView,
        ];
    }

    /**
     * @return array{start: mixed, end: mixed}[]
     */
    public function getAvailableSlots(): array
    {
        $availableSlots = [];
        $currentDate = Carbon::now();
        $currentDatetimeStamp = $currentDate->timestamp;
        $events = $this->events()->where('start_date_time', '>', $currentDate)->orderBy('start_date_time')->get();
        if ($events->isEmpty()) {
            return [];
        }

        $previousEvent = null;
        foreach ($events as $event) {
            if (is_null($previousEvent)) {
                if ($currentDatetimeStamp < $event->start_date_time->timestamp) {
                    $availableSlots[] = ['start' => $currentDatetimeStamp, 'end' => $event->start_date_time->timestamp];
                }
            } else {
                $availableSlots[] = ['start' => $previousEvent->end_date_time->timestamp, 'end' => $event->start_date_time->timestamp];
            }

            $previousEvent = $event;
        }

        $availableSlots[] = ['start' => $previousEvent->end_date_time->timestamp, 'end' => Carbon::now()->addYear()->timestamp];

        return $availableSlots;
    }

    public function checkAvailableSlots($startDateTime, $endDateTime): bool
    {
        $availableSlots = $this->getAvailableSlots();
        if ($availableSlots === []) {
            return true;
        }

        foreach ($availableSlots as $slot) {
            $start = $slot['start'];
            $end = $slot['end'];
            if ($startDateTime >= $start && $endDateTime <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    private function prepareDateConfiguration(string $date, string $timezone): array
    {
        $appTimezone = config('app.timezone');
        $setTimeZone = $timezone !== $appTimezone ? $timezone : null;
        $startDate = Carbon::parse($date, $setTimeZone)->startOfMonth()->startOfWeek();
        $endDate = Carbon::parse($date, $setTimeZone)->endOfMonth()->endOfWeek();
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);

        return [
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'monthDates' => $period->toArray(),
        ];
    }

    private function getFormattedEventsForPeriod(Carbon $startDate, Carbon $endDate, string $date): array
    {
        return $this->events()
            ->whereBetween('start_date_time', [$startDate, $endDate])
            ->orderBy('start_date_time')
            ->get()
            ->groupBy('date')
            ->map(fn (Collection $dateEvents): array => $this->formatDateEvents($dateEvents, $date))
            ->toArray();
    }

    private function formatDateEvents(Collection $dateEvents, string $date): array
    {
        /** @var Event $firstEvent */
        $firstEvent = $dateEvents->first();
        $payload = [
            'date'   => $firstEvent->start_date_time->format('Y-m-d'),
            'events' => EventMonthViewResource::collection($dateEvents)->resolve(),
        ];

        $parsedDate = Carbon::parse($date);
        $eventDate = $firstEvent->start_date_time;

        if ($parsedDate->isSameMonth($eventDate)) {
            $payload['isCurrentMonth'] = true;
        }

        if ($parsedDate->isSameDay($eventDate)) {
            $payload['isSelected'] = true;
        }

        if (Carbon::now()->isSameDay($eventDate)) {
            $payload['isToday'] = true;
        }

        return $payload;
    }

    private function buildCalendarView(array $monthDates, array $events): array
    {
        $calendarView = [];
        foreach ($monthDates as $monthDate) {
            $dateKey = $monthDate->format('Y-m-d');
            $calendarView[] = Arr::has($events, $dateKey)
                ? $events[$dateKey]
                : ['date' => $dateKey, 'events' => []];
        }

        return $calendarView;
    }

    private function hasEventsBeforeDate(Carbon $startDate): bool
    {
        return $this->events()->where('start_date_time', '<', $startDate)->exists();
    }

    private function hasEventsAfterDate(Carbon $endDate): bool
    {
        return $this->events()->where('start_date_time', '>', $endDate->endOfDay())->exists();
    }

    private function prepareDailyDateConfiguration(string $date, string $timezone): array
    {
        $setTimeZone = $timezone !== config('app.timezone') ? $timezone : null;
        $todayDate = Carbon::parse($date, $setTimeZone)->startOfDay();
        $tomorrowDate = Carbon::parse($date, $setTimeZone)->addDay()->startOfDay();
        $firstEvent = $this->events()->orderBy('start_date_time')->first();
        $latestEvent = $this->events()->orderBy('start_date_time', 'desc')->latest()->first();

        $startCalendarMonth = Carbon::parse($firstEvent->start_date_time ?? $date, $setTimeZone)->startOfMonth();
        $endCalendarMonth = Carbon::parse($latestEvent->start_date_time ?? $date, $setTimeZone)->endOfMonth();
        if ($todayDate->isAfter($endCalendarMonth)) {
            $endCalendarMonth = Carbon::parse($todayDate, $setTimeZone)->endOfMonth();
        }

        $dailyEvents = clone $this->events();
        $period = CarbonPeriod::create($startCalendarMonth, '1 month', $endCalendarMonth);

        return [
            'todayDate'    => $todayDate,
            'tomorrowDate' => $tomorrowDate,
            'months'       => $period->toArray(),
            'daysEvents'   => $dailyEvents->pluck('date')->unique()->toArray(),
        ];
    }

    private function getDailyEvents(Carbon $todayDate, Carbon $tomorrowDate, ?string $timezone): array
    {
        $setTimeZone = $timezone !== config('app.timezone') ? $timezone : null;

        return $this->events()
            ->whereBetween('start_date_time', [$todayDate, $tomorrowDate])
            ->orderBy('start_date_time')
            ->get()
            ->map(fn (Event $dayEvent): array => new EventDayViewResource($dayEvent, $setTimeZone)->resolve())
            ->toArray();
    }

    private function buildDailyCalendarView(array $months, Carbon $todayDate, array $daysEvents, ?string $timezone): array
    {
        $setTimeZone = $timezone !== config('app.timezone') ? $timezone : null;
        $calendarView = [];

        foreach ($months as $month) {
            $startDate = Carbon::parse($month, $setTimeZone)->startOfMonth()->startOfWeek();
            $endDate = Carbon::parse($month, $setTimeZone)->endOfMonth()->endOfWeek();
            $daysPeriod = CarbonPeriod::create($startDate, '1 day', $endDate);
            $monthDates = $daysPeriod->toArray();

            foreach ($monthDates as $monthDate) {
                $payload = $this->buildDayPayload($monthDate, $todayDate, $daysEvents);

                if (isset($calendarView[$month->format('Y-m')])) {
                    $calendarView[$month->format('Y-m')][] = $payload;
                } else {
                    $calendarView[$month->format('Y-m')] = [$payload];
                }
            }
        }

        return $calendarView;
    }

    private function buildDayPayload(Carbon $monthDate, Carbon $todayDate, array $daysEvents): array
    {
        $payload = ['date' => $monthDate->format('Y-m-d')];

        if ($monthDate->isSameMonth($todayDate)) {
            $payload['isCurrentMonth'] = true;
        }

        if ($monthDate->isSameDay($todayDate)) {
            $payload['isSelected'] = true;
        }

        if (Carbon::now()->isSameDay($monthDate)) {
            $payload['isToday'] = true;
        }

        if (in_array($monthDate->format('Y-m-d'), $daysEvents)) {
            $payload['hasEvent'] = true;
        }

        return $payload;
    }
}
