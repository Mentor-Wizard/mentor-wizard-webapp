<?php

declare(strict_types=1);

namespace App\Actions\MentorPrograms;

use App\Models\MentorProgram;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsController;

class SetMainMentorProgram
{
    use AsController;

    public function handle(MentorProgram $mentorProgram): RedirectResponse
    {
        try {
            DB::beginTransaction();
            MentorProgram::query()
                ->where('mentor_id', $mentorProgram->mentor_id)
                ->where('id', '!=', $mentorProgram->getKey())
                ->update(['is_main' => false]);

            $mentorProgram->update(['is_main' => true]);

            return to_route('mentor-program.edit', $mentorProgram->slug)
                ->with('success', 'Main consultation updated successfully.');
        } catch (Exception) {
            DB::rollBack();

            return to_route('mentor-program.edit', $mentorProgram->slug)
                ->with('error', 'Error with setting main consultation.');
        }

    }
}
