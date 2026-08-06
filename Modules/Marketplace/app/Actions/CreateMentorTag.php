<?php

declare(strict_types=1);

namespace Modules\Marketplace\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;
use Modules\Marketplace\Enums\TagEnum;
use Modules\Marketplace\Models\MentorTag;

class CreateMentorTag
{
    use AsObject;

    public function handle(string $tag, TagEnum $type): MentorTag
    {
        $normalizedTag = Str::lower(mb_trim($tag));

        $existingTag = MentorTag::query()
            ->where('tag', $normalizedTag)
            ->where('type', $type)
            ->first();

        if ($existingTag) {
            return $existingTag;
        }

        return MentorTag::query()->create([
            'tag'  => $normalizedTag,
            'type' => $type,
        ]);
    }
}
