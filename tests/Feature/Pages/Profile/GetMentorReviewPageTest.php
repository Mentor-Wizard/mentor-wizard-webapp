<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\GetMentorProfilePage;
use App\Actions\Pages\Profile\GetMentorReviewPage;
use App\Enums\RoleEnum;
use App\Models\Currency;
use App\Models\MentorProfile;
use App\Models\MentorReview;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

mutates(GetMentorReviewPage::class);

describe('GetMentorReviewPage', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
    });

    it('returns mentor reviews JSON', function (): void {
        // Створюємо ментора
        $mentor = User::factory()->create();
        $mentor->assignRole(RoleEnum::MENTOR->value);

        // Створюємо кілька менті і відгуків
        $menti = User::factory()->create();
        $menti->assignRole(RoleEnum::MENTI->value);

        MentorReview::factory()->count(6)->create([
            'mentor_id' => $mentor->id,
            'menti_id'  => $menti->id,
            'comment'   => 'Great mentor',
            'rating'    => 5,
        ]);


        $response = $this->getJson(route('page.mentor-review', ['user' => $mentor->slug]));

        $response->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json->has('items', 4) // перевіряємо, що повернулось 4 елементи (PER_PAGE = 4)
                ->has('next_page', 1)
                    ->etc();
            });

        // Тест на наступну сторінку
        $responsePage2 = $this->getJson(route('page.mentor-review', ['user' => $mentor->slug, 'page' => 1]));

        $responsePage2->assertOk()
            ->assertJson(function (AssertableJson $json) {
                $json->has('items', 2) // залишилось 2 відгуки
                ->has('next_page', 2)
                    ->etc();
            });
    });
});
