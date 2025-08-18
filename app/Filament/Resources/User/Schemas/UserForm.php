<?php

declare(strict_types=1);

namespace App\Filament\Resources\User\Schemas;

use App\Models\User;
use Closure;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class UserForm extends Schema
{
    /**
     * @throws Exception
     */
    public function schema(Component|Action|ActionGroup|Htmlable|Closure|array|string $components): static
    {
        return $this->components([
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
        ]);
    }
}
