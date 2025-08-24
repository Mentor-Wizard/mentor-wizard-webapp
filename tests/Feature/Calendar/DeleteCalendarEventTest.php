<?php

declare(strict_types=1);

use App\Enums\EventRoleEnum;
use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\Event as EventModel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;

describe('Calendar Event Delete Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->anotherMentor = User::factory()->create();
        $this->anotherMentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->nonMentorUser = User::factory()->create();

        auth()->login($this->user);
        auth()->login($this->anotherMentor);
        auth()->login($this->nonMentorUser);

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

    it('event deleted successfully', function (): void {
        actingAs($this->user);
        auth()->login($this->user);

        $response = $this->withoutMiddleware()
            ->delete(route('pages.calendar.delete', $this->event->getKey()));
        $response->assertRedirect(route('pages.calendar'));

        $this->assertDatabaseMissing('events', [
            'id' => $this->event->getKey(),
        ]);
    });

    it('throws 403 when a non-mentor user tries to delete an event', function (): void {
        actingAs($this->nonMentorUser);
        auth()->login($this->nonMentorUser);

        $response = $this->withoutMiddleware()
            ->delete(route('pages.calendar.delete', $this->event->getKey()));
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('throws 403 when a other mentor user tries to delete an event', function (): void {
        actingAs($this->anotherMentor);
        auth()->login($this->anotherMentor);

        $response = $this->withoutMiddleware()
            ->delete(route('pages.calendar.delete', $this->event->getKey()));
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    });
});
