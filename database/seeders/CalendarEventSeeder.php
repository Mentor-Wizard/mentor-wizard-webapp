<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Illuminate\Database\Seeder;

class CalendarEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menti = User::query()->role(RoleEnum::MENTI)->get();
        $mentorPrograms = MentorProgram::query()->with('mentor')->get();
        foreach ($mentorPrograms as $mentorProgram) {

            CalendarEvent::factory(['mentor_program_id' => $mentorProgram->id])
                ->count(random_int(1, 3))
                ->create()
                ->each(function ($event) use ($menti, $mentorProgram): void {
                    $mentor = $mentorProgram?->mentor;
                    $mentor->calendarEvents()->attach($event, [
                        'role'   => CalendarEventRoleEnum::HOST,
                        'colour' => CalendarEventColoursEnum::RED,
                    ]);
                    $menti->random(1, 3)->each(function ($user) use ($event): void {
                        $user->calendarEvents()->attach($event, [
                            'role'   => CalendarEventRoleEnum::MENTI,
                            'colour' => CalendarEventColoursEnum::BLUE,
                        ]);
                    });
                });
        }
    }
}
