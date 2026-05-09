<?php

declare(strict_types=1);

namespace App\Filament\Resources\Category;

use App\Filament\Resources\Category\Pages\CreateCategory;
use App\Filament\Resources\Category\Pages\EditCategory;
use App\Filament\Resources\Category\Pages\ListCategories;
use App\Filament\Resources\Category\Schemas\CategoryForm;
use App\Filament\Resources\Category\Tables\CategoriesTable;
use App\Models\Category;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-tag';

    protected static string|null|UnitEnum $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasRole('admin');
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    #[Override]
    public static function getPages(): array
    {
        return [
            'index'  => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit'   => EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->count();
    }
}
