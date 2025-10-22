<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\GetMentorReviewPage;
use App\Enums\RoleEnum;
use App\Models\MentorProfile;
use App\Models\MentorReview;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Testing\Fluent\AssertableJson;

mutates(GetMentorReviewPage::class);

describe('GetMentorReviewPage', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
    });

    it('returns mentor reviews JSON with correct structure and pagination', function (): void {
        $mentor = User::factory()->create();
        $mentor->assignRole(RoleEnum::MENTOR->value);
        MentorProfile::factory()->create(['user_id' => $mentor->id]);

        $menti = User::factory()->create();
        $menti->assignRole(RoleEnum::MENTI->value);

        // Create 6 mentor reviews for the mentor
        MentorReview::factory()->count(8)->create([
            'mentor_id' => $mentor->id,
            'menti_id'  => $menti->id,
            'comment'   => 'Great mentor review',
            'rating'    => 5,
        ]);

        // Test the first page
        $response = $this->getJson(route('page.mentor-review', ['mentor' => $mentor->slug]));

        $response->assertOk()
            ->assertJson(function (AssertableJson $json): void {
                $json->has('items', 4) // Expect 4 items per page (PER_PAGE = 4)
                    ->has('items.0', function (AssertableJson $json): void {
                        $json->where('id', is_int(...))
                            ->where('comment', 'Great mentor review')
                            ->where('rating', 5)
                            ->where('menti.id', is_int(...))
                            ->where('menti.username', is_string(...))
                            ->where('menti.avatar', fn (?string $avatar): bool => is_string($avatar) || is_null($avatar))
                            ->where('created_at', is_string(...))
                            ->etc();
                    })
                    ->where('next_page', 1) // Expect next_page to be 1 for the second page
                    ->etc();
            });

        // Test the second page
        $responsePage2 = $this->getJson(route('page.mentor-review', ['mentor' => $mentor->slug, 'page' => 1]));

        $responsePage2->assertOk()
            ->assertJson(function (AssertableJson $json): void {
                $json->has('items', 4) // Expect 2 remaining items
                    ->where('next_page', null) // Expect next_page to be null as there are no more pages
                    ->etc();
            });

        // Test an empty page (page 2, which should be empty)
        $responsePage3 = $this->getJson(route('page.mentor-review', ['mentor' => $mentor->slug, 'page' => 2]));

        $responsePage3->assertOk()
            ->assertJson(function (AssertableJson $json): void {
                $json->has('items', 0) // Expect 0 items
                    ->where('next_page', null) // Expect next_page to be null
                    ->etc();
            });
    });

    it('returns 404 if user is not a mentor', function (): void {
        $user = User::factory()->create(); // Not a mentor
        $response = $this->getJson(route('page.mentor-review', ['mentor' => $user->slug]));
        $response->assertNotFound();
    });
});
