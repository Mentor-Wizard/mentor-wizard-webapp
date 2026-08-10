<?php

declare(strict_types=1);

namespace App\Support\MediaLibrary;

use Illuminate\Database\Eloquent\Relations\Relation;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator as BasePathGenerator;

class PathGenerator implements BasePathGenerator
{
    /*
     * Get the path for the given media, relative to the root storage path.
     */
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media).'/';
    }

    /*
     * Get the path for conversions of the given media, relative to the root storage path.
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media).'/conversions/';
    }

    /*
     * Get the path for responsive images of the given media, relative to the root storage path.
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media).'/responsive-images/';
    }

    /*
     * Get a unique base path for the given media.
     */
    private function getBasePath(Media $media): string
    {
        $prefix = config('media-library.prefix');
        // $media->model_type may be a short, enforced morph alias (e.g. "chat_message")
        // rather than the FQCN — resolve it back to the real class so storage paths
        // stay byte-identical to what they were before the morph map was introduced.
        $className = class_basename(Relation::getMorphedModel($media->model_type) ?? $media->model_type);

        return mb_ltrim(sprintf('%s/%s/%s', $prefix, $className, $media->getKey()), '/');
    }
}
