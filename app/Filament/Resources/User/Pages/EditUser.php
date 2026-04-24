<?php

declare(strict_types=1);

namespace App\Filament\Resources\User\Pages;

use App\Filament\Resources\User\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Override;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
