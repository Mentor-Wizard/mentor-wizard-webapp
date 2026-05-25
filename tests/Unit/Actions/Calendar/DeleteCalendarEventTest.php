<?php

declare(strict_types=1);

use App\Actions\Calendar\CalendarEvent\DeleteCalendarEvent;
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Role;

mutates(DeleteCalendarEvent::class);

describe('Delete Calendar CalendarEvent Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->timezone = 'Europe/Kyiv';
        $this->user->profile->save();

        // Create mentor program for the primary mentor
        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        $this->anotherMentor = User::factory()->create();
        $this->anotherMentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->nonMentorUser = User::factory()->create();

        $this->user = createAndAuthenticateMentorForDestroyUnit();
        $this->request = Request::create('/')->setUserResolver(fn (): User => $this->user);

        $this->event = CalendarEvent::factory()->create([
            'title'             => 'Default event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 09:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://google.com',
            'description'       => 'Test description',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->event->calendarEventUsers()->attach($this->user->getKey(),
            ['role'      => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value]);
    });

    it(' confirmed event cannot be cancelled and returns redirect response', function (): void {
        // Confirmed events should be cancelled, not deleted
        $action = new DeleteCalendarEvent;
        $response = $action->handle($this->event);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.index'))
            ->and(session('error'))->toBe('Confirmed event was cancelled.')
            ->and(CalendarEvent::query()->find($this->event->getKey()))->not->toBeNull()
            ->and(CalendarEvent::query()->find($this->event->getKey())?->status)->toBe(CalendarEventStatusEnum::CANCELLED);
    });

    it('deletes event when status is pending or cancelled', function (): void {
        // Pending mentor confirmation -> delete
        $pending = CalendarEvent::factory()->create([
            'status' => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
        ]);
        $response1 = new DeleteCalendarEvent()->handle($pending);
        expect($response1)->toBeInstanceOf(RedirectResponse::class)
            ->and(CalendarEvent::query()->find($pending->getKey()))->toBeNull();

        // Cancelled -> delete
        $cancelled = CalendarEvent::factory()->create([
            'status' => CalendarEventStatusEnum::CANCELLED,
        ]);
        $response2 = new DeleteCalendarEvent()->handle($cancelled);
        expect($response2)->toBeInstanceOf(RedirectResponse::class)
            ->and(CalendarEvent::query()->find($cancelled->getKey()))->toBeNull();
    });

    it('deletes event when status stored as raw string (string path of switch)', function (): void {
        // Ensure we cover the non-enum (string) branch of the switch expression
        $stringCancelled = CalendarEvent::factory()->create([
            'status'            => 'Cancelled',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $response = new DeleteCalendarEvent()->handle($stringCancelled);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and(CalendarEvent::query()->find($stringCancelled->getKey()))->toBeNull();
    });

    it('handles delete call for non-mentor viewer and returns redirect (authorization handled in feature)',
        function (): void {
            Auth::logout();
            $viewer = User::factory()->create();
            Auth::login($viewer);

            $response = new DeleteCalendarEvent()->handle($this->event);

            expect($response)
                ->toBeInstanceOf(RedirectResponse::class)
                ->and($response->getStatusCode())->toBe(302)
                ->and($response->getTargetUrl())->toBe(route('pages.calendar.index'));
        });

    it("handles another mentor's event and returns redirect (authorization handled in feature)",
        function (): void {

            $this->actingAs($this->anotherMentor);

            $anotherMentorEvent = CalendarEvent::factory()->create([
                'title'             => 'Default event',
                'status'            => CalendarEventStatusEnum::CONFIRMED,
                'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 09:00:00',
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'web_link'          => 'https://google.com',
                'description'       => 'Test description',
                // Create a separate mentor program for another mentor to reflect ownership properly
                'mentor_program_id' => MentorProgram::factory()->create([
                    'mentor_id' => $this->anotherMentor->getKey(),
                ])->getKey(),
            ]);

            $anotherMentorEvent->calendarEventUsers()
                ->attach($this->anotherMentor->getKey(),
                    ['role'      => CalendarEventRoleEnum::HOST,
                        'colour' => 'blue']);

            $response = new DeleteCalendarEvent()->handle($anotherMentorEvent);

            expect($response)->toBeInstanceOf(RedirectResponse::class)
                ->and($response->getStatusCode())->toBe(302)
                ->and($response->getTargetUrl())->toBe(route('pages.calendar.index'));
        });
});

function createAndAuthenticateMentorForDestroyUnit(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    Auth::login($user);

    return $user;
}
