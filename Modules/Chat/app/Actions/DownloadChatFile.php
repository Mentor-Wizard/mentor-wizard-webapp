<?php

declare(strict_types=1);

namespace Modules\Chat\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Chat\Models\ChatMessage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class DownloadChatFile
{
    use AsAction;
    use AsController;

    public function handle(ChatMessage $message, Media $media): BinaryFileResponse
    {
        abort_if($media->model_id !== $message->getKey() || $media->model_type !== $message->getMorphClass(), Response::HTTP_NOT_FOUND);

        return response()->download($media->getPath(), $media->file_name);
    }
}
