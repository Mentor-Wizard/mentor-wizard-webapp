<?php

declare(strict_types=1);

namespace App\Filament\Resources\User;

use App\Filament\Resources\User\Pages\CreateUser;
use App\Filament\Resources\User\Pages\EditUser;
use App\Filament\Resources\User\Pages\ListUsers;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Override;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // protected static ?string $navigationIcon = 'heroicon-o-users';

    // protected static ?string $navigationGroup = 'User Management';

    // protected static ?int $navigationSort = 1;

    #[Override]
    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return UserForm::make();
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return UsersTable::make()->table($table);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
