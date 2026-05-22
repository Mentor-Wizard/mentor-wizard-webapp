<?php

declare(strict_types=1);

namespace App\Actions\MentorPrograms;

use App\Models\MentorProgram;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsController;

class SetMainMentorProgram
{
    use AsController;

    public function handle(MentorProgram $mentorProgram): RedirectResponse
    {
        try {
            DB::transaction(function () use ($mentorProgram): void {
                MentorProgram::query()
                    ->where('mentor_id', $mentorProgram->mentor_id)
                    ->where('id', '!=', $mentorProgram->getKey())
                    ->update(['is_main' => false]);

                $mentorProgram->update(['is_main' => true]);
            });

            return to_route('mentor-program.edit', $mentorProgram->slug)
                ->with('success', 'Main consultation updated successfully.');
        } catch (Exception $exception) {
            Log::error('Error with setting main consultation', [
                'exception'         => $exception->getMessage(),
                'mentor_program_id' => $mentorProgram->id,
            ]);

            return to_route('mentor-program.edit', $mentorProgram->slug)
                ->with('error', 'Error with setting main consultation.');
        }

    }
}
