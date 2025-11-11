<?php

declare(strict_types=1);

use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

mutates(CalendarEvent::class);

describe('CalendarEvent model', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('casts start_date_time and end_date_time as Carbon instances', function (): void {
        $start = Carbon::create(2025, 8, 21, 9, 15, 0);
        $end = Carbon::create(2025, 8, 21, 10, 45, 0);

        $event = CalendarEvent::factory()->create([
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'date'            => $start?->format('Y-m-d'),
        ]);

        $fresh = CalendarEvent::query()->find($event->id);

        expect($fresh->start_date_time)->toBeInstanceOf(CarbonImmutable::class)
            ->and($fresh->end_date_time)->toBeInstanceOf(CarbonImmutable::class)
            ->and($fresh->start_date_time->format('H:i'))->toBe('09:15')
            ->and($fresh->end_date_time->format('H:i'))->toBe('10:45');
    });

    it('has users many-to-many relationship with timestamps', function (): void {
        $event = CalendarEvent::factory()->create();
        $user = User::factory()->create();

        $relation = $event->calendarEventUsers();
        expect($relation)->toBeInstanceOf(BelongsToMany::class);

        $event->calendarEventUsers()->attach($user->id, ['role' => 'host', 'created_at' => now(), 'updated_at' => now()]);

        $pivot = $event->calendarEventUsers()->where('users.id', $user->id)->first()->pivot;
        expect($pivot)->not->toBeNull()
            ->and($pivot->created_at)->not->toBeNull()
            ->and($pivot->updated_at)->not->toBeNull();
    });

    it('has the correct fillable attributes', function (): void {
        $model = new CalendarEvent;

        expect($model->getFillable())->toEqual([
            'title',
            'status',
            'start_date_time',
            'end_date_time',
            'duration',
            'date',
            'type',
            'web_link',
            'description',
            'mentor_program_id',
        ]);
    });

    it('can be created with mass assignable attributes', function (): void {
        $program = MentorProgram::factory()->create();

        $start = Carbon::create(2025, 8, 22, 9, 0, 0);
        $end = Carbon::create(2025, 8, 22, 10, 30, 0);

        $payload = [
            'title'             => 'CalendarEvent Create Test',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'duration'          => (int) $start?->diffInSeconds($end),
            'date'              => $start?->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://example.com/meeting',
            'description'       => 'Testing creation',
            'mentor_program_id' => $program->getKey(),
        ];

        $event = CalendarEvent::query()->create($payload);
        $event->refresh();

        expect($event)
            ->toBeInstanceOf(CalendarEvent::class)
            ->and($event->title)->toBe('CalendarEvent Create Test')
            ->and($event->status)->toBe(CalendarEventStatusEnum::CONFIRMED->value)
            ->and($event->start_date_time->format('Y-m-d H:i'))->toBe('2025-08-22 09:00')
            ->and($event->end_date_time->format('Y-m-d H:i'))->toBe('2025-08-22 10:30')
            ->and($event->duration)->toBe((int) $start?->diffInSeconds($end))
            ->and($event->date)->toBe('2025-08-22')
            ->and($event->type)->toBe(CalendarEventTypeEnum::INDIVIDUAL->value)
            ->and($event->web_link)->toBe('https://example.com/meeting')
            ->and($event->description)->toBe('Testing creation')
            ->and($event->mentor_program_id)->toBe($program->getKey());
    });
});
