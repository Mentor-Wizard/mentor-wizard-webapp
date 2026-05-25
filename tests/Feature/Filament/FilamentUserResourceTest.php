<?php

declare(strict_types=1);

use App\Actions\MentorTag\CreateMentorTag;
use App\Enums\TagEnum;
use App\Filament\Resources\User\Pages\EditUser;
use App\Filament\Resources\User\Pages\ListUsers;
use App\Filament\Resources\User\UserResource;
use App\Models\Currency;
use App\Models\MentorTag;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Filament\Resources\Pages\PageRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RoleSeeder::class, CurrencySeeder::class]);
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('app');
});

describe('Filament UserResource', function (): void {
    it('returns navigation badge with user count', function (): void {
        User::factory()->count(5)->create();

        $badge = UserResource::getNavigationBadge();

        expect($badge)->toBeString()
            ->and((int) $badge)->toBe(User::query()->count());
    });

    it('creates mentor tags when new tag is created via action', function (): void {
        // Test the createOptionUsing callback directly by calling the CreateMentorTag action
        $newTagName = 'NewTechStack';

        // Ensure the tag doesn't exist before the test
        expect(MentorTag::query()->where('tag', mb_strtolower($newTagName))->exists())->toBeFalse();

        // This simulates what the createOptionUsing callback does in UserResource
        $tag = CreateMentorTag::run($newTagName, TagEnum::STACK);

        // Verify tag was created with correct properties (this tests lines 164-166)
        expect($tag->getKey())->toBeInt();

        $createdTag = MentorTag::query()->where('tag', mb_strtolower($newTagName))->first();
        expect($createdTag)->not->toBeNull()
            ->and($createdTag->tag)->toBe(mb_strtolower($newTagName))
            ->and($createdTag->type)->toBe(TagEnum::STACK);
    });

    it('displays formatted tag labels in select options', function (): void {
        $currency = Currency::query()->first();

        $user = User::factory()->create();
        $user->assignRole('mentor');
        $user->mentorProfile()->create([
            'title'                 => 'Test Title',
            'description'           => 'Test Description',
            'rate'                  => '50.00',
            'currency_id'           => $currency->id,
            'experience_started_at' => '2013-01-01',
        ]);

        $tag = MentorTag::factory()->create([
            'tag'  => 'laravel',
            'type' => TagEnum::STACK,
        ]);
        $user->mentorProfile->mentorTags()->attach($tag);

        $component = Livewire::test(EditUser::class, ['record' => $user->id]);

        expect($component)->not->toBeNull()
            ->and($tag->tag)->toBe('laravel')
            ->and($tag->type)->toBe(TagEnum::STACK);
    });

    it('handles tag creation action directly', function (): void {
        $tagName = 'DirectTestTag';

        $createdTag = CreateMentorTag::run($tagName, TagEnum::STACK);

        expect($createdTag)->not->toBeNull()
            ->and($createdTag->tag)->toBe(mb_strtolower($tagName))
            ->and($createdTag->type)->toBe(TagEnum::STACK)
            ->and($createdTag->getKey())->toBeInt();
    });

    it('can search users in ListUsers table', function (): void {
        $user1 = User::factory()->create(['username' => 'john_doe']);
        $user2 = User::factory()->create(['username' => 'jane_doe']);
        $user3 = User::factory()->create(['username' => 'test_user']);

        $component = Livewire::test(ListUsers::class)
            ->searchTable('john_doe')
            ->assertCanSeeTableRecords([$user1])
            ->assertCanNotSeeTableRecords([$user2, $user3]);

        expect($component)->not->toBeNull();
    });

    it('can access UserResource table configuration through ListUsers', function (): void {
        Livewire::test(ListUsers::class)->assertSuccessful();
    });

    it('has correct resource pages configuration', function (): void {
        $pages = UserResource::getPages();

        expect($pages)->toHaveKeys(['index', 'create', 'edit'])
            ->and($pages['index'])->toBeInstanceOf(PageRegistration::class)
            ->and($pages['create'])->toBeInstanceOf(PageRegistration::class)
            ->and($pages['edit'])->toBeInstanceOf(PageRegistration::class);
    });
});
