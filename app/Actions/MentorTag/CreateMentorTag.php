<?php

declare(strict_types=1);

namespace App\Actions\MentorTag;

use App\Enums\TagEnum;
use App\Models\MentorTag;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;

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
