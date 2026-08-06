<?php

declare(strict_types=1);

use App\Enums\MentorSessionTypeEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Models\CalendarEvent;
use Modules\MentorProgram\Models\MentorProgram;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $mentor = User::factory()->create();
    $mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));
});

it('preserves the same instant across a non-UTC timezone round-trip through the datetime cast', function (): void {
    $mentorProgram = MentorProgram::factory()->create();

    $startInKyiv = Date::create(2026, 6, 15, 12, 0, 0, 'Europe/Kyiv');
    $endInKyiv = $startInKyiv->copy()->addHour();

    $event = CalendarEvent::query()->create([
        'title'             => 'Timezone round-trip',
        'status'            => CalendarEventStatusEnum::CONFIRMED->value,
        'start_date_time'   => $startInKyiv,
        'end_date_time'     => $endInKyiv,
        'date'              => $startInKyiv->format('Y-m-d'),
        'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
        'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
        'mentor_program_id' => $mentorProgram->getKey(),
    ]);

    $freshEvent = $event->fresh();

    expect($freshEvent)->not->toBeNull()
        ->and($freshEvent->start_date_time->eq($startInKyiv))->toBeTrue()
        ->and($freshEvent->start_date_time->timestamp)->toBe($startInKyiv->timestamp)
        ->and($freshEvent->end_date_time->eq($endInKyiv))->toBeTrue()
        ->and($freshEvent->end_date_time->timestamp)->toBe($endInKyiv->timestamp);
});

it('stores the same instant regardless of the timezone the Carbon instance was created in', function (): void {
    $mentorProgram = MentorProgram::factory()->create();

    $instant = Date::create(2026, 6, 15, 9, 0, 0, 'UTC');
    $sameInstantInKyiv = $instant->copy()->timezone('Europe/Kyiv');

    $eventFromUtc = CalendarEvent::query()->create([
        'title'             => 'From UTC',
        'status'            => CalendarEventStatusEnum::CONFIRMED->value,
        'start_date_time'   => $instant,
        'end_date_time'     => $instant->copy()->addHour(),
        'date'              => $instant->format('Y-m-d'),
        'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
        'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
        'mentor_program_id' => $mentorProgram->getKey(),
    ]);

    $eventFromKyiv = CalendarEvent::query()->create([
        'title'             => 'From Kyiv',
        'status'            => CalendarEventStatusEnum::CONFIRMED->value,
        'start_date_time'   => $sameInstantInKyiv,
        'end_date_time'     => $sameInstantInKyiv->copy()->addHour(),
        'date'              => $sameInstantInKyiv->format('Y-m-d'),
        'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
        'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
        'mentor_program_id' => $mentorProgram->getKey(),
    ]);

    expect($eventFromUtc->fresh()->start_date_time->eq($eventFromKyiv->fresh()->start_date_time))->toBeTrue();
});
