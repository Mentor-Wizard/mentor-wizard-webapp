<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\ChatStatusEnum;
use App\Models\Chat;
use Illuminate\Http\Response;
use Lorisleiva\Actions\Concerns\AsController;

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
