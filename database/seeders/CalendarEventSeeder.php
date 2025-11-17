<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

class CalendarEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mentors = User::query()->role(RoleEnum::MENTOR)->get();
        $menti = User::query()->role(RoleEnum::MENTI)->get();

        CalendarEvent::factory()
            ->count(500)
            ->create()
            ->each(function ($event) use ($mentors, $menti): void {
                $mentors->random(1)->first()->calendarEvents()->attach($event, [
                    'role' => CalendarEventRoleEnum::HOST,
                    'colour' => CalendarEventColoursEnum::RED,
                ]);
                $menti->random(1, 3)->each(function ($user) use ($event): void {
                    $user->calendarEvents()->attach($event, [
                        'role' => CalendarEventRoleEnum::MENTI,
                        'colour' => CalendarEventColoursEnum::BLUE,
                    ]);
                });
            });
    }
}
