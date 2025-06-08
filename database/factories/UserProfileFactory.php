<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName,
            'last_name' => fake()->lastName,
            'title' => fake()->jobTitle(),
            'linkedin' => fake()->url,
            'telegram' => fake()->userName,
            'whatsapp' => fake()->phoneNumber,
            'phone' => fake()->phoneNumber,
            'description' => fake()->text(),
        ];
    }

//    public function configure(): static
//    {
//        return $this->afterCreating(function (UserProfile $profile) {
//            $tempImage = UploadedFile::fake()->image('avatar.jpg', 200, 200);
//            $profile->addMedia($tempImage)
//                ->preservingOriginal()
//                ->toMediaCollection('avatar');
//        });
//    }

//    public function configure()
//    {
//        return $this->afterCreating(function ($userProfile) {
//            $name = urlencode($userProfile->name . ' ' . $userProfile->last_name);
//            $avatarUrl = "https://ui-avatars.com/api/?name={$name}&background=random&size=256&format=png";
//
//            try {
//                $userProfile->addMediaFromUrl($avatarUrl)
//                    ->usingFileName('avatar.png')
//                    ->toMediaCollection('avatar');
//            } catch (\Exception $e) {
//                // Handle the exception if needed
//            }
//        });
//    }




}
