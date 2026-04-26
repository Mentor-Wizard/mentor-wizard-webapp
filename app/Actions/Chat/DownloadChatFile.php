<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\ChatMessage;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Concerns\AsController;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadChatFile
{
    use AsAction;
    use AsController;

    public function handle(ChatMessage $message, Media $media): BinaryFileResponse
    {
        abort_if($media->model_id !== $message->id || $media->model_type !== ChatMessage::class, 404);

        return response()->download($media->getPath(), $media->file_name);
    }
}
