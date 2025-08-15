<?php

declare(strict_types=1);

namespace App\Filament\Resources\User;

use App\Filament\Resources\User\Pages\CreateUser;
use App\Filament\Resources\User\Pages\EditUser;
use App\Filament\Resources\User\Pages\ListUsers;
use App\Filament\Resources\User\Tables\UsersTable;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
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
        return $schema->schema([
            Section::make('User Account')
                ->schema([
                    Group::make([
                        TextInput::make('username')->required()->maxLength(255)->unique(ignoreRecord: true),

                        TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                    ])->columns(2),

                    Group::make([
                        TextInput::make('password')->password()->required()->minLength(User::MIN_PASSWORD_LENGTH)->hiddenOn('edit'),

                        Select::make('roles')->relationship('roles', 'name')->multiple()->preload()->searchable(),
                    ])->columns(2),
                ]),

            Section::make('Profile Information')
                ->relationship('profile')
                ->schema([
                    FileUpload::make('avatar')
                        ->label('Avatar')
                        ->image()
                        ->imageEditor()
                        ->imageCropAspectRatio('1:1')
                        ->imageResizeTargetWidth('300')
                        ->imageResizeTargetHeight('300')
                        ->maxSize(5120) // 5MB
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                        ->storeFiles(false)
                        ->dehydrated(false)
                        ->afterStateUpdated(function ($state, $record, $get): void {
                            if ($state && $record) {
                                // In profile relationship context, $record is UserProfile, need User
                                $user = $record->user ?? $record;
                                app(\App\Actions\User\AddAvatar::class)->handle($user, $state);
                            }
                        }),

                    Group::make([
                        TextInput::make('name')
                            ->label('First Name')
                            ->maxLength(50)
                            ->minLength(3),

                        TextInput::make('last_name')
                            ->label('Last Name')
                            ->maxLength(50)
                            ->minLength(3),
                    ])->columns(2),

                    Group::make([
                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->placeholder('+1234567890')
                            ->regex('/^\+\d{11,15}$/')
                            ->helperText('Enter phone number with country code (e.g., +1234567890)'),

                        Select::make('currency_id')
                            ->label('Currency')
                            ->relationship('currency', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                    Group::make([
                        TextInput::make('cost_per_hour')
                            ->label('Hourly Rate')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0),
                    ])->columns(2),

                    Group::make([
                        TextInput::make('linkedin')
                            ->label('LinkedIn Profile')
                            ->url()
                            ->maxLength(200)
                            ->placeholder('https://www.linkedin.com/in/username')
                            ->regex('/^https:\/\/(www\.)?linkedin\.com\/.+$/i'),

                        TextInput::make('telegram')
                            ->label('Telegram Profile')
                            ->url()
                            ->maxLength(100)
                            ->placeholder('https://t.me/username')
                            ->regex('/^https:\/\/(www\.)?t\.me\/.+$/i'),
                    ])->columns(2),

                    TextInput::make('whatsapp')
                        ->label('WhatsApp')
                        ->url()
                        ->maxLength(100)
                        ->placeholder('https://wa.me/1234567890')
                        ->regex('/^https:\/\/(www\.)?wa\.me\/.+$/i'),
                ]),
        ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
