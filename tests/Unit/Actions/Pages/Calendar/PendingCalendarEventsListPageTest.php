<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\PendingCalendarEventsListPage;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Inertia\Response;
use Spatie\Permission\Models\Role;

mutates(PendingCalendarEventsListPage::class);

describe('PendingCalendarEventsListPage (Unit)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->program = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'name'      => 'Program X',
        ]);

        $this->event = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->program->getKey(),
            'date'              => Date::tomorrow()->toDateString(),
        ]);
        $this->event->calendarEventUsers()->attach($this->mentor->getKey());

        auth()->login($this->mentor);
    });

    it('returns inertia response with grouped pending events', function (): void {
        $response = new PendingCalendarEventsListPage()->handle();
        expect($response)->toBeInstanceOf(Response::class);

        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        expect($page['component'])->toBe('Calendar/ListPendingCalendarEventsPage')
            ->and($page['props'])->toHaveKeys(['locale', 'calendarEvents'])
            ->and($page['props']['calendarEvents'])->toHaveKey('Program X');
    });

    it('filters by mentor program when provided', function (): void {
        $response = new PendingCalendarEventsListPage()->handle($this->program);
        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        expect($page['props']['calendarEvents'])->toHaveKey('Program X');
    });
});
