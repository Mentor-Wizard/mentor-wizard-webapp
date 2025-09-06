<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class MentorProgramsResource extends JsonResource
{
    public static $wrap;

    #[Override]
    public function toArray(Request $request): array
    {
        $this->load('currency', 'mentorProgramBlocks');

        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'description'  => $this->description,
            'cost'         => $this->cost,
            'currency'     => $this->currency->symbol,
            'blocks'       => $this->blocks(),
        ];
    }

    private function blocks()
    {
        return $this->mentorProgramBlocks()
            ->get()
            ->map(fn($block): array => [
                'id'          => $block->id,
                'name'        => $block->id.'-'.$block->name,
                'description' => $block->description,
            ])->toArray();
    }
}
