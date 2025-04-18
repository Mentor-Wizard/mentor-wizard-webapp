<?php

declare(strict_types=1);

namespace App\Actions\Pages\MentorProgram;

use App\Models\MentorProgram;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use App\Http\Requests\MentorProgram\UpdateMentorProgramRequest;

class UpdateMentorProgramAction
{
    use AsController;

    public function handle(UpdateMentorProgramRequest $request, MentorProgram $mentorProgram): RedirectResponse
    {
        $mentorProgram->update($request->validated());

        return redirect()->route('mentor-program.edit', $mentorProgram);
    }
}
