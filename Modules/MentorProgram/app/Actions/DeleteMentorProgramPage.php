<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\MentorProgram\Models\MentorProgram;

class DeleteMentorProgramPage
{
    use AsController;

    public function handle(Request $request, MentorProgram $mentorProgram): RedirectResponse
    {
        $mentorProgram->delete();

        return to_route('mentor-program.create');
    }
}
