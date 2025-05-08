<?php

declare(strict_types=1);

namespace App\Actions\Pages\MentorProgram;

use App\Http\Requests\MentorProgram\UpdateMentorProgramRequest;
use App\Models\MentorProgram;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class UpdateMentorProgramPage
{
    use AsController;

    public function handle(UpdateMentorProgramRequest $request, MentorProgram $mentorProgram): Response
    {
        throw_unless($mentorProgram->exists, new ModelNotFoundException('Mentor program not found.'));
        abort_if($request->user()->cannot('update', $mentorProgram), 403, 'Unauthorized action.');

        $mentorProgram->update($request->validated());

        return Inertia::location(route('mentor-program.edit', $mentorProgram->slug));
    }
}
