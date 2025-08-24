<?php

declare(strict_types=1);

use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Models\Event;
use App\Models\MentorProgram;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

mutates(Event::class);

describe('Event model', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('casts start_date_time and end_date_time as Carbon instances', function (): void {
        $start = Carbon::create(2025, 8, 21, 9, 15, 0);
        $end = Carbon::create(2025, 8, 21, 10, 45, 0);

        $event = Event::factory()->create([
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'date'            => $start?->format('Y-m-d'),
        ]);

        $fresh = Event::query()->find($event->id);

        expect($fresh->start_date_time)->toBeInstanceOf(CarbonImmutable::class)
            ->and($fresh->end_date_time)->toBeInstanceOf(CarbonImmutable::class)
            ->and($fresh->start_date_time->format('H:i'))->toBe('09:15')
            ->and($fresh->end_date_time->format('H:i'))->toBe('10:45');
    });

    it('has users many-to-many relationship with timestamps', function (): void {
        $event = Event::factory()->create();
        $user = User::factory()->create();

        $relation = $event->users();
        expect($relation)->toBeInstanceOf(BelongsToMany::class);

        $event->users()->attach($user->id, ['role' => 'host', 'created_at' => now(), 'updated_at' => now()]);

        $pivot = $event->users()->where('users.id', $user->id)->first()->pivot;
        expect($pivot)->not->toBeNull()
            ->and($pivot->created_at)->not->toBeNull()
            ->and($pivot->updated_at)->not->toBeNull();
    });

    it('has the correct fillable attributes', function (): void {
        $model = new Event;

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
            'title'             => 'Event Create Test',
            'status'            => EventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'duration'          => (int) $start?->diffInSeconds($end),
            'date'              => $start?->format('Y-m-d'),
            'type'              => EventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://example.com/meeting',
            'description'       => 'Testing creation',
            'mentor_program_id' => $program->getKey(),
        ];

        $event = Event::query()->create($payload);
        $event->refresh();

        expect($event)
            ->toBeInstanceOf(Event::class)
            ->and($event->title)->toBe('Event Create Test')
            ->and($event->status)->toBe(EventStatusEnum::CONFIRMED->value)
            ->and($event->start_date_time->format('Y-m-d H:i'))->toBe('2025-08-22 09:00')
            ->and($event->end_date_time->format('Y-m-d H:i'))->toBe('2025-08-22 10:30')
            ->and($event->duration)->toBe((int) $start?->diffInSeconds($end))
            ->and($event->date)->toBe('2025-08-22')
            ->and($event->type)->toBe(EventTypeEnum::INDIVIDUAL->value)
            ->and($event->web_link)->toBe('https://example.com/meeting')
            ->and($event->description)->toBe('Testing creation')
            ->and($event->mentor_program_id)->toBe($program->getKey());
    });
});
