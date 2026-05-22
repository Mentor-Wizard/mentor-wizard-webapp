<?php

declare(strict_types=1);

namespace App\Actions\MentorPrograms;

use App\Http\Requests\MentorProgram\UpdateMentorProgramRequest;
use App\Models\MentorProgram;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

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
