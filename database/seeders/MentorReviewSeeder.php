<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MentorReview;
use App\Models\User;
use Illuminate\Database\Seeder;

class MentorReviewSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $menti) {
            // We select all other users to leave them reviews.
            $mentors = $users->where('id', '!=', $menti->id)->shuffle()->take(20);

            foreach ($mentors as $mentor) {
                for ($i = 0; $i < 10; $i++) {
                    MentorReview::query()->create([
                        'mentor_id' => $mentor->id,
                        'menti_id'  => $menti->id,
                        'comment'   => fake()->sentence(50),
                        'rating'    => fake()->numberBetween(1, 5),
                    ]);
                }
            }
        }
    }
}
