<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EventRoleEnum;
use App\Enums\EventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\Event;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mentors = User::query()->role(RoleEnum::MENTOR)->get();
        $menti = User::query()->role(RoleEnum::MENTI)->get();

        Event::factory()
            ->count(500)
            ->create()
            ->each(function ($event) use($mentors,$menti): void {
                $mentors->random(1)->first()->events()->attach($event,[
                    'role' => EventRoleEnum::HOST,
                ]);
                $menti->random(1,3)->each(function ($user) use($event): void {
                    $user->events()->attach($event,[
                        'role'=> EventRoleEnum::MENTI
                    ]);
                });
            });
    }
}
