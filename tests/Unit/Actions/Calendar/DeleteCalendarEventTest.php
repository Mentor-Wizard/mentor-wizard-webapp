<?php

declare(strict_types=1);

use App\Actions\Calendar\DeleteCalendarPage;
use App\Enums\EventRoleEnum;
use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\Event as EventModel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

mutates(DeleteCalendarPage::class);

describe('Delete Calendar Event Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->anotherMentor = User::factory()->create();
        $this->anotherMentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->nonMentorUser = User::factory()->create();

        $this->user = createAndAuthenticateMentorForDestroyUnit();
        $this->request = Request::create('/')->setUserResolver(fn (): User => $this->user);

        $this->event = EventModel::factory()->create([
            'title'             => 'Default event',
            'status'            => EventStatusEnum::CONFIRMED,
            'start_date_time'   => Carbon::tomorrow()->format('Y-m-d').' 09:00:00',
            'date'              => Carbon::tomorrow()->format('Y-m-d'),
            'duration'          => 3600,
            'type'              => EventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'mentor_program_id' => null,
        ]);
        $this->event->users()->attach($this->user->getKey(), ['role' => EventRoleEnum::HOST]);
    });

    it('deletes mentor program and returns redirect response', function (): void {
        $action = new DeleteCalendarPage;
        $response = $action->handle((string) $this->event->getKey());

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar'))
            ->and(EventModel::query()->count())->toBe(0);
    });

    it('throws exception when trying to delete non-existent event', function (): void {
        $deletedEventId = (string) $this->event->getKey();
        $this->event->delete();

        $response = new DeleteCalendarPage()->handle($deletedEventId);

        expect($response)->toBeInstanceOf(Illuminate\Http\JsonResponse::class)
            ->and($response->getStatusCode())->toBe(404)
            ->and($response->getData(true)['message'])->toBe('Event not found');

    });

    it('returns 403 for non-mentor user', function (): void {
        Auth::logout();
        $viewer = User::factory()->create();
        Auth::login($viewer);

        $response = new DeleteCalendarPage()->handle((string) $this->event->getKey());

        expect($response)
            ->toBeInstanceOf(Illuminate\Http\JsonResponse::class)
            ->and($response->getStatusCode())->toBe(403)
            ->and($response->getData(true)['message'])->toBe('Only mentor can create events.');
    });

    it("throws 403 forbidden when trying to delete another mentor's program", function (): void {

        $this->actingAs($this->anotherMentor);

        $anotherMentorEvent = EventModel::factory()->create([
            'title'             => 'Default event',
            'status'            => EventStatusEnum::CONFIRMED,
            'start_date_time'   => Carbon::tomorrow()->format('Y-m-d').' 09:00:00',
            'date'              => Carbon::tomorrow()->format('Y-m-d'),
            'duration'          => 3600,
            'type'              => EventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'mentor_program_id' => null,
        ]);

        $response = new DeleteCalendarPage()->handle((string) $this->event->getKey());

        expect($response)->toBeInstanceOf(Illuminate\Http\JsonResponse::class)
            ->and($response->getStatusCode())->toBe(403)
            ->and($response->getData(true)['message'])->toBe('Attempt to delete event of other mentor');
        //
        //        $this->fail('Exception was not thrown');
    });
});

function createAndAuthenticateMentorForDestroyUnit(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    Auth::login($user);

    return $user;
}
