<?php

declare(strict_types=1);

use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent as EventModel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;

describe('Calendar CalendarEvent Delete Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->anotherMentor = User::factory()->create();
        $this->anotherMentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->nonMentorUser = User::factory()->create();

        auth()->login($this->user);
        actingAs($this->user);
        $this->event = EventModel::factory()->create([
            'title'             => 'Default event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 09:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'duration'          => 3600,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'mentor_program_id' => null,
        ]);

        $this->event->calendarEventUsers()->attach($this->user->getKey(), ['role' => CalendarEventRoleEnum::HOST,
            'colour'                                                              => 'blue']);

        auth()->login($this->anotherMentor);
        auth()->login($this->nonMentorUser);
    });

    it('event deleted successfully', function (): void {
        actingAs($this->user);
        auth()->login($this->user);

        $response = $this->withSession(['_token' => 'test-token'])
            ->delete(route('pages.calendar.delete', $this->event->getKey()),
                [
                    '_token' => csrf_token(),
                ]);

        $response->assertRedirect(route('pages.calendar.index'));

        $this->assertDatabaseMissing('calendar_events', [
            'id' => $this->event->getKey(),
        ]);
    });

    it('throws 403 when a non-mentor user tries to delete an event', function (): void {
        actingAs($this->nonMentorUser);
        auth()->login($this->nonMentorUser);

        $response = $this->withSession(['_token' => 'test-token'])
            ->delete(route('pages.calendar.delete', $this->event->getKey()),
                [
                    '_token' => csrf_token(),
                ]);
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('throws 403 when a other mentor user tries to delete an event', function (): void {
        actingAs($this->anotherMentor);
        auth()->login($this->anotherMentor);

        $response = $this->withSession(['_token' => 'test-token'])
            ->delete(route('pages.calendar.delete', $this->event->getKey()),
                [
                    '_token' => csrf_token(),
                ]);
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    });
});
