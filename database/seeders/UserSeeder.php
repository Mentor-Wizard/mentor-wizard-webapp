<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()
            ->count(10)
            ->create()
            ->each(function ($user): void {
                $user->profile()->update([
                    'name'          => explode(' ', $user->username)[0],
                    'last_name'     => explode(' ', $user->username)[1],
                    'linkedin'      => fake()->url,
                    'telegram'      => fake()->userName,
                    'whatsapp'      => fake()->phoneNumber,
                    'phone'         => fake()->phoneNumber,
                    'cost_per_hour' => fake()->randomFloat(2, 10, 100),
                    'currency_id'   => Currency::query()->inRandomOrder()->value('id'),
                ]
                );
                $name = urlencode($user->profile->name.' '.$user->profile->last_name);
                $avatarUrl = sprintf(UserProfile::TEST_AVATAR_URL, $name);
                $user->profile->addMediaFromUrl($avatarUrl)
                    ->usingFileName('avatar.png')
                    ->toMediaCollection('avatar');
            });
    }
}
