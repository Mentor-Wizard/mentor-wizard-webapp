<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class AddAvatar
{
    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function handle(User $user, UploadedFile $avatar): void
    {
        $user
            ->profile
            ->addMedia($avatar)
            ->toMediaCollection('avatar');
    }
}
