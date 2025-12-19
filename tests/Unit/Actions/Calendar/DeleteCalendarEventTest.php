<?php

declare(strict_types=1);

use App\Actions\Calendar\DeleteCalendarEvent;
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
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
            'mentor_program_id' => null,
        ]);
        $this->event->calendarEventUsers()->attach($this->user->getKey(),
            ['role'      => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value]);
    });

    it('deletes mentor program and returns redirect response', function (): void {
        $action = new DeleteCalendarEvent;
        $response = $action->handle($this->event);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.index'))
            ->and(CalendarEvent::query()->count())->toBe(0);
    });

    it('deletes already deleted event and returns redirect response', function (): void {

        CalendarEvent::query()->find($this->event->getKey())?->delete();

        $response = new DeleteCalendarEvent()->handle($this->event);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getStatusCode())->toBe(302)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.index'));

    });

    it('deletes event for non-mentor user and returns redirect response', function (): void {
        Auth::logout();
        $viewer = User::factory()->create();
        Auth::login($viewer);

        $response = new DeleteCalendarEvent()->handle($this->event);

        expect($response)
            ->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getStatusCode())->toBe(302)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.index'));
    });

    it("deletes another mentor's event and returns redirect response", function (): void {

        $this->actingAs($this->anotherMentor);

        $anotherMentorEvent = CalendarEvent::factory()->create([
            'title'             => 'Default event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 09:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://google.com',
            'description'       => 'Test description',
            'mentor_program_id' => null,
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
