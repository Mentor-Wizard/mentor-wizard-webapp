<?php

declare(strict_types=1);

use App\Filament\Resources\Category\Pages\CreateCategory;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([RoleSeeder::class]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    Filament::setCurrentPanel('app');
});

describe('CategoryForm depth validation', function (): void {
    it('allows creating a category under a parent at level 2', function (): void {
        $level1 = Category::factory()->create();
        $level2 = Category::factory()->child($level1)->create();

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'      => 'Level Three',
                'slug'      => 'level-three',
                'parent_id' => $level2->getKey(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Category::query()->where('slug', 'level-three')->where('parent_id', $level2->getKey())->exists())
            ->toBeTrue();
    });

    it('rejects creating a category under a parent already at MAX_DEPTH', function (): void {
        expect(Category::MAX_DEPTH)->toBe(3);

        $level1 = Category::factory()->create();
        $level2 = Category::factory()->child($level1)->create();
        $level3 = Category::factory()->child($level2)->create();

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'      => 'Too Deep',
                'slug'      => 'too-deep',
                'parent_id' => $level3->getKey(),
            ])
            ->call('create')
            ->assertHasFormErrors(['parent_id']);

        expect(Category::query()->where('slug', 'too-deep')->exists())->toBeFalse();
    });

    it('allows creating a root category with no parent', function (): void {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name'      => 'Root Category',
                'slug'      => 'root-category',
                'parent_id' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Category::query()->where('slug', 'root-category')->first();

        expect($created)->not->toBeNull()
            ->and($created->parent_id)->toBeNull();
    });
});
