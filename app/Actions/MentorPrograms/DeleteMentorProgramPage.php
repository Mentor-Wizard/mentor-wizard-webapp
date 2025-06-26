<?php

declare(strict_types=1);

namespace App\Actions\MentorPrograms;

use App\Models\MentorProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;

class DeleteMentorProgramPage
{
    use AsController;

    public function handle(Request $request, MentorProgram $mentorProgram): RedirectResponse
    {
        $mentorProgram->delete();

        return redirect()->route('mentor-program.create');
    }
}
