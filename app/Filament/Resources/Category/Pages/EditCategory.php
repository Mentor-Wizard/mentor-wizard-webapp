<?php

declare(strict_types=1);

namespace App\Filament\Resources\Category\Pages;

use App\Filament\Resources\Category\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    /**
     * @return DeleteAction[]
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
