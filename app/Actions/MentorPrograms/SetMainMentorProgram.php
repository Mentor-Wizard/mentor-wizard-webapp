<?php

declare(strict_types=1);

namespace App\Actions\MentorPrograms;

use App\Models\MentorProgram;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class SetMainMentorProgram
{
    use AsController;

    public function handle(MentorProgram $mentorProgram): RedirectResponse
    {
        MentorProgram::query()
            ->where('mentor_id', $mentorProgram->mentor_id)
            ->where('id', '!=', $mentorProgram->getKey())
            ->update(['is_main' => false]);

        $mentorProgram->update(['is_main' => true]);

        return to_route('mentor-program.edit', $mentorProgram->slug)
            ->with('success', 'Main consultation updated successfully.');
    }
}
