<?php

declare(strict_types=1);

namespace App\Filament\Resources\Category\Schemas;

use App\Models\Category;
use App\Rules\CategoryDepthRule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Category Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(static function (?string $state, callable $set, callable $get): void {
                            if ($state === null || $state === '') {
                                return;
                            }

                            $currentSlug = $get('slug');

                            if ($currentSlug === null || $currentSlug === '') {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(table: Category::class, column: 'slug', ignoreRecord: true)
                        ->helperText('Auto-generated from the name. Edit if needed.'),

                    Select::make('parent_id')
                        ->label('Parent Category')
                        ->relationship(
                            name: 'parent',
                            titleAttribute: 'name',
                            modifyQueryUsing: static fn ($query, ?Category $record) => $record instanceof Category
                                ? $query->whereKeyNot($record->getKey())
                                : $query,
                        )
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->placeholder('No parent (root category)')
                        ->rules([new CategoryDepthRule]),
                ]),
        ]);
    }
}
