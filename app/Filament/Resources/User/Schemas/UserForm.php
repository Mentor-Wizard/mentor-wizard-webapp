<?php

declare(strict_types=1);

namespace App\Filament\Resources\User\Schemas;

use App\Models\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;

class UserForm extends Schema
{
    public function schema(\Filament\Schemas\Components\Component|\Filament\Actions\Action|\Filament\Actions\ActionGroup|\Illuminate\Contracts\Support\Htmlable|Closure|array|string $components): static
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
