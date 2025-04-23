<?php

declare(strict_types=1);

namespace App\Actions\Pages\MentorProgram;

use App\Http\Requests\MentorProgram\UpdateMentorProgramRequest;
use App\Models\MentorProgram;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class UpdateMentorProgramPage
{
    use AsController;

    public function handle(UpdateMentorProgramRequest $request, MentorProgram $mentorProgram): Response
    {
        $mentorProgram->update($request->validated());

        return Inertia::location(route('mentor-program.edit', $mentorProgram->slug));
    }
}
