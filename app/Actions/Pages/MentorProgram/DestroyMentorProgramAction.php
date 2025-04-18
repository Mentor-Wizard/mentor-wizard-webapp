<?php

declare(strict_types=1);

namespace App\Actions\Pages\MentorProgram;

use App\Models\MentorProgram;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class DestroyMentorProgramAction
{
    use AsController;

    public function handle(MentorProgram $mentorProgram): RedirectResponse
    {
        $mentorProgram->delete();

        return redirect()->route('mentor-program.create');
    }
}
