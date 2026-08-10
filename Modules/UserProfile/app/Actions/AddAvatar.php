<?php

declare(strict_types=1);

namespace Modules\UserProfile\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class AddAvatar
{
    use AsObject;

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
