<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\MentorProgram\Models\MentorProgram;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

describe('MentorProgramEventBookingPage (Feature)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->mentor->profile->timezone = 'UTC';
        $this->mentor->profile->save();

        $this->program = MentorProgram::factory()->create([
            'mentor_id'        => $this->mentor->getKey(),
            'session_duration' => 30,
        ]);
    });

    it('redirects guest to login', function (): void {
        $this->get(route('pages.mentor.program.book', $this->program->slug))
            ->assertRedirect(route('login'));
    });

    it('renders booking page with expected props and current date when date param omitted', function (): void {
        $this->actingAs($this->mentor);

        $response = $this->get(route('pages.mentor.program.book', $this->program->slug));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/MentorProgramEventBookingPage')
            ->has('locale')
            ->has('days')
            ->has('weekDays')
            ->has('mentorProgram')
            ->has('roundingMinutes')
            ->has('currentDate')
        );
    });

    it('renders booking page honoring explicit date param', function (): void {
        $this->actingAs($this->mentor);
        $date = Date::parse('2026-01-15', 'UTC')->toDateString();

        $response = $this->get(route('pages.mentor.program.book', [$this->program->slug, 'date' => $date]));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/MentorProgramEventBookingPage')
            ->where('currentDate', $date)
        );
    });
});
