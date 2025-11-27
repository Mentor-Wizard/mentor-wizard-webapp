<?php

declare(strict_types=1);

namespace App\Filament\Resources\User\Schemas;

use App\Actions\User\AddAvatar;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('User Account')
                ->schema([
                    Group::make([
                        TextInput::make('username')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])->columns(2),

                    Group::make([
                        TextInput::make('password')
                            ->password()
                            ->required()
                            ->minLength(User::MIN_PASSWORD_LENGTH)
                            ->hiddenOn('edit'),

                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])->columns(2),
                ]),

            Section::make('Profile Information')
                ->relationship('profile')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('avatar')
                        ->label('Avatar')
                        ->image()
                        ->avatar()
                        ->imageEditor()
                        ->imageCropAspectRatio('1:1')
                        ->imageResizeTargetWidth('300')
                        ->imageResizeTargetHeight('300')
                        ->maxSize(5120) // 5MB
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                        ->collection('avatar')
                        ->saveUploadedFileUsing(static function (
                            SpatieMediaLibraryFileUpload $component,
                            TemporaryUploadedFile $file,
                            ?Model $record,
                        ): ?string {
                            $user = $component->getLivewire()->getRecord();

                            if (! $user instanceof User) {
                                return null;
                            }

                            return AddAvatar::run($user, $file);
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

                    TextInput::make('phone')
                        ->label('Phone Number')
                        ->placeholder('+1234567890')
                        ->regex('/^\+\d{11,15}$/')
                        ->helperText('Enter phone number with country code (e.g., +1234567890)')
                        ->afterStateUpdatedJs(<<<'JS'
                            const digits = ($state ?? '').replace(/^\+/, '').replace(/\D/g, '');
                            const currentWhatsapp = $get('whatsapp');

                            if (!digits) {
                                if (!currentWhatsapp || currentWhatsapp.startsWith('https://wa.me/')) {
                                    $set('whatsapp', null);
                                }

                                return;
                            }

                            const waLink = `https://wa.me/${digits}`;

                            if (!currentWhatsapp || currentWhatsapp.startsWith('https://wa.me/')) {
                                $set('whatsapp', waLink);
                            }
                        JS),

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

            Section::make('Mentor Profile')
                ->relationship('mentorProfile')
                ->schema([
                    Group::make([
                        TextInput::make('title')
                            ->label('Professional Title')
                            ->maxLength(100)
                            ->placeholder('Senior Software Engineer, Tech Lead, etc.'),

                        Select::make('currency_id')
                            ->label('Currency')
                            ->relationship('currency', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                    Group::make([
                        TextInput::make('rate')
                            ->label('Hourly Rate')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->prefix('$')
                            ->required(),

                        TextInput::make('experience_started_at')
                            ->label('Experience Started')
                            ->type('date')
                            ->helperText('When did you start your professional career?')
                            ->required(),
                    ])->columns(2),

                    Textarea::make('description')
                        ->label('Professional Description')
                        ->maxLength(1000)
                        ->placeholder('Describe your experience, expertise, and what you can offer as a mentor...')
                        ->rows(4),

                    Select::make('mentorTags')
                        ->label('Skills & Technologies')
                        ->relationship('mentorTags', 'tag')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->helperText('Select existing tags from the available options.'),
                ])
                ->visible(function (?User $record): bool {
                    if (! $record instanceof User) {
                        return false;
                    }

                    return $record->hasRole('mentor');
                }),
        ]);
    }
}
