<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Actions;

use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\MentorProgram\Http\Requests\UpdateMentorProgramRequest;
use Modules\MentorProgram\Models\MentorProgram;

class UpdateMentorProgramPage
{
    use AsController;

    public function handle(UpdateMentorProgramRequest $request, MentorProgram $mentorProgram): RedirectResponse
    {
        $mentorProgram->update($request->validated());

        return to_route('mentor-program.edit', $mentorProgram->slug)
            ->with('success', 'Mentor program updated successfully.');
    }
}
