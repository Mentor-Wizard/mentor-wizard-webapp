<?php

declare(strict_types=1);

namespace App\Filament\Resources\Category\Pages;

use App\Filament\Resources\Category\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    /**
     * @return CreateAction[]
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
