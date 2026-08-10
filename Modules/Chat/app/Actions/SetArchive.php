<?php

declare(strict_types=1);

namespace Modules\Chat\Actions;

use Illuminate\Http\Response;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Chat\Enums\ChatStatusEnum;
use Modules\Chat\Models\Chat;

class SetArchive
{
    use AsController;

    public function handle(Chat $chat): Response
    {
        $user = auth()->user();
        $user->chats()->updateExistingPivot(
            $chat->getKey(),
            [
                'status' => ChatStatusEnum::ARCHIVED->value,
            ]
        );

        return response()->noContent();
    }
}
