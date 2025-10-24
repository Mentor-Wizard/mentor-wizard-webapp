<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class MentorProgramsResource extends JsonResource
{
    public static $wrap;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->resource->id,
            'name'         => $this->resource->name,
            'slug'         => $this->resource->slug,
            'description'  => $this->resource->description,
            'cost'         => $this->resource->cost,
            'currency'     => $this->resource->currency->symbol,
            'blocks'       => $this->blocks(),
        ];
    }

    private function blocks()
    {
        return $this->resource->mentorProgramBlocks()
            ->get()
            ->map(fn ($block): array => [
                'id'          => $block->id,
                'name'        => $block->name,
                'description' => $block->description,
            ])->toArray();
    }
}
