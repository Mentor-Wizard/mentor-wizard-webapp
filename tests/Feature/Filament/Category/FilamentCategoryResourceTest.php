<?php

declare(strict_types=1);

use App\Filament\Resources\Category\CategoryResource;
use App\Filament\Resources\Category\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Filament\Resources\Pages\PageRegistration;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([RoleSeeder::class]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    Filament::setCurrentPanel('app');
});

describe('Filament CategoryResource', function (): void {
    it('returns navigation badge with category count', function (): void {
        Category::factory()->count(4)->create();

        $badge = CategoryResource::getNavigationBadge();

        expect($badge)->toBeString()
            ->and((int) $badge)->toBe(Category::query()->count());
    });

    it('renders the categories list page', function (): void {
        Livewire::test(ListCategories::class)->assertSuccessful();
    });

    it('shows category records in the list table', function (): void {
        $categories = Category::factory()->count(3)->create();

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords($categories);
    });

    it('exposes name, slug and parent columns on the table', function (): void {
        Category::factory()->count(2)->create();

        Livewire::test(ListCategories::class)
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('slug')
            ->assertTableColumnExists('parent.name');
    });

    it('can search categories by name in the list table', function (): void {
        $matching = Category::factory()->create(['name' => 'Backend Development']);
        $other = Category::factory()->create(['name' => 'Frontend Design']);

        Livewire::test(ListCategories::class)
            ->searchTable('Backend')
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$other]);
    });

    it('has correct resource pages configuration', function (): void {
        $pages = CategoryResource::getPages();

        expect($pages)->toHaveKeys(['index', 'create', 'edit'])
            ->and($pages['index'])->toBeInstanceOf(PageRegistration::class)
            ->and($pages['create'])->toBeInstanceOf(PageRegistration::class)
            ->and($pages['edit'])->toBeInstanceOf(PageRegistration::class);
    });
});
