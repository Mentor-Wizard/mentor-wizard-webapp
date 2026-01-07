<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

describe('Calendar Pages - CalendarsList', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->user = User::factory()->create();
    });

    it('redirects guests to login', function (): void {
        $this->get(route('pages.calendar.index'))
            ->assertRedirect(route('login'));
    });

    it('renders calendars list for authenticated mentor with create permissions', function (): void {
        $response = $this->actingAs($this->mentor)
            ->get(route('pages.calendar.index'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/CalendarEventsList')
            ->has('locale')
            ->has('availableColours')
            ->has('calendarEvents')
            ->where('permissions', 'create')
        );
    });
});
