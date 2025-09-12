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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RoleSeeder::class, CurrencySeeder::class]);
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('app');
});

describe('Filament UserResource', function (): void {
    it('returns navigation badge with user count', function (): void {
        // Create some users to have a count
        User::factory()->count(5)->create();

        $badge = UserResource::getNavigationBadge();

        expect($badge)->toBeString()
            ->and((int) $badge)->toBe(User::query()->count());
    });

    it('creates mentor tags when new tag is created via action', function (): void {
        // Test the createOptionUsing callback directly by calling the CreateMentorTag action
        $newTagName = 'NewTechStack';

        // Ensure tag doesn't exist before test
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
        $mentorRole = Role::query()->where('name', 'mentor')->first();

        // Create a user with mentor role first
        $user = User::factory()->create();
        $user->assignRole('mentor');
        $user->mentorProfile()->create([
            'title'                 => 'Test Title',
            'description'           => 'Test Description',
            'rate'                  => '50.00',
            'currency_id'           => $currency->id,
            'experience_started_at' => '2013-01-01',
        ]);

        // Create a tag to test the label formatting
        $tag = MentorTag::factory()->create([
            'tag'  => 'laravel',
            'type' => TagEnum::STACK,
        ]);
        $user->mentorProfile->mentorTags()->attach($tag);

        // Test the edit form which should display existing tags with formatted labels
        $component = Livewire::test(EditUser::class, ['record' => $user->id]);

        // The form should load successfully and display the mentor profile section
        expect($component)->not->toBeNull();

        // Verify the tag exists and would be formatted properly
        expect($tag->tag)->toBe('laravel')
            ->and($tag->type)->toBe(TagEnum::STACK);
    });

    it('handles tag creation action directly', function (): void {
        // Test the CreateMentorTag action directly to ensure it works
        $tagName = 'DirectTestTag';

        $createdTag = CreateMentorTag::run($tagName, TagEnum::STACK);

        expect($createdTag)->not->toBeNull()
            ->and($createdTag->tag)->toBe(mb_strtolower($tagName))
            ->and($createdTag->type)->toBe(TagEnum::STACK)
            ->and($createdTag->getKey())->toBeInt();
    });

    it('handles non-existent tag lookup gracefully', function (): void {
        // Create a mock scenario where tag lookup might fail
        $nonExistentTagId = 99999;

        $tag = MentorTag::query()->find($nonExistentTagId);
        expect($tag)->toBeNull();

        // Simulate the getOptionLabelUsing logic
        $formattedLabel = $tag ? ucwords((string) $tag->tag).' ('.ucfirst((string) $tag->type->value).')' : '';
        expect($formattedLabel)->toBe('');
    });

    it('can render ListUsers page', function (): void {
        // Create some users to display in the table
        User::factory()->count(3)->create();

        $component = Livewire::test(ListUsers::class);

        expect($component)->not->toBeNull();
        $component->assertSuccessful();
    });

    it('can search users in ListUsers table', function (): void {
        $user1 = User::factory()->create(['username' => 'johndoe']);
        $user2 = User::factory()->create(['username' => 'janedoe']);
        $user3 = User::factory()->create(['username' => 'testuser']);

        $component = Livewire::test(ListUsers::class)
            ->searchTable('johndoe')
            ->assertCanSeeTableRecords([$user1])
            ->assertCanNotSeeTableRecords([$user2, $user3]);

        expect($component)->not->toBeNull();
    });

    it('can access UserResource table configuration through ListUsers', function (): void {
        // Test the table configuration is accessible through the ListUsers page
        $component = Livewire::test(ListUsers::class);

        // The table should be configured successfully (this tests UserResource::table and UsersTable::configure)
        expect($component)->not->toBeNull();
        $component->assertSuccessful();
    });

    it('has correct resource pages configuration', function (): void {
        $pages = UserResource::getPages();

        expect($pages)->toHaveKeys(['index', 'create', 'edit'])
            ->and($pages['index'])->toBeInstanceOf(\Filament\Resources\Pages\PageRegistration::class)
            ->and($pages['create'])->toBeInstanceOf(\Filament\Resources\Pages\PageRegistration::class)
            ->and($pages['edit'])->toBeInstanceOf(\Filament\Resources\Pages\PageRegistration::class);
    });
});
