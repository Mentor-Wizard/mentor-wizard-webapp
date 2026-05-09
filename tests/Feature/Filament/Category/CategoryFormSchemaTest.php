<?php

declare(strict_types=1);

use App\Filament\Resources\Category\Pages\CreateCategory;
use App\Filament\Resources\Category\Pages\EditCategory;
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

describe('CategoryForm Schema', function (): void {
    it('renders the create form successfully', function (): void {
        Livewire::test(CreateCategory::class)->assertSuccessful();
    });

    it('creates a category with valid data', function (): void {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Programming',
                'slug' => 'programming',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Category::query()->where('slug', 'programming')->exists())->toBeTrue();
    });

    it('auto-generates the slug from the name when slug is empty', function (): void {
        Livewire::test(CreateCategory::class)
            ->fillForm(['name' => 'Web Development'])
            ->assertFormSet(['slug' => 'web-development']);
    });

    it('does not overwrite a slug that the user has already filled', function (): void {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'slug' => 'custom-slug',
                'name' => 'Web Development',
            ])
            ->assertFormSet(['slug' => 'custom-slug']);
    });

    it('requires the name field', function (): void {
        Livewire::test(CreateCategory::class)
            ->fillForm(['slug' => 'no-name'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    });

    it('requires the slug field', function (): void {
        Livewire::test(CreateCategory::class)
            ->set('data.name', 'Programming')
            ->set('data.slug', '')
            ->call('create')
            ->assertHasFormErrors(['slug' => 'required']);
    });

    it('rejects a duplicate slug on create', function (): void {
        Category::factory()->create(['slug' => 'taken-slug']);

        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Other',
                'slug' => 'taken-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    });

    it('pre-populates the edit form with the existing record', function (): void {
        $category = Category::factory()->create([
            'name' => 'Backend',
            'slug' => 'backend',
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->assertFormSet([
                'name' => 'Backend',
                'slug' => 'backend',
            ]);
    });

    it('updates a category with valid data', function (): void {
        $category = Category::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->fillForm([
                'name' => 'New Name',
                'slug' => 'new-name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $category->refresh();

        expect($category->name)->toBe('New Name')
            ->and($category->slug)->toBe('new-name');
    });

    it('allows keeping the same slug on edit', function (): void {
        $category = Category::factory()->create(['slug' => 'unchanged']);

        Livewire::test(EditCategory::class, ['record' => $category->getKey()])
            ->fillForm([
                'name' => 'Renamed',
                'slug' => 'unchanged',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    });
});
