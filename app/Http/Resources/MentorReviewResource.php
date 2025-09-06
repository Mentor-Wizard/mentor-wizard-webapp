<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class MentorReviewResource extends JsonResource
{
    public static $wrap;

    public function __construct($resource, protected User $mentor)
    {
        parent::__construct($resource);
    }

    public static function collectionWithMentor($resources, $mentor)
    {
        return $resources->map(
            // @phpstan-ignore-next-line
            fn ($item): static => new static($item, $mentor)
        );
    }

    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->load('menti');

        // Retrieving a list of program names through intermediate tables that link the mentee and the mentor
        $names = $this->resource->menti->mentiProgramProgress()
            ->select('mentor_programs.name')
            ->join('mentor_program_blocks', 'mentor_program_block_progresses.mentor_program_block_id', '=', 'mentor_program_blocks.id')
            ->join('mentor_programs', 'mentor_program_blocks.mentor_program_id', '=', 'mentor_programs.id')
            ->where('mentor_programs.mentor_id', $this->mentor->id)
            ->pluck('mentor_programs.name')
            ->unique()
            ->toArray();

        return [
            'id'    => $this->resource->id,
            'menti' => [
                'id'       => $this->resource->menti->id,
                'username' => mb_trim($this->resource->menti->profile->name.' '.$this->resource->menti->profile->last_name),
                'avatar'   => $this->resource->menti->profile->avatar ?: UserProfile::DEFAULT_AVATAR_URL,
            ],
            'comment'    => $this->resource->comment,
            'rating'     => $this->resource->rating,
            'program'    => implode(', ', $names),
            'created_at' => $this->resource->created_at,
        ];
    }
}
