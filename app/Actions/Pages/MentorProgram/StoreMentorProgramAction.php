<?php

declare(strict_types=1);

namespace App\Actions\Pages\MentorProgram;

use App\Http\Requests\MentorProgram\StoreMentorProgramRequest;
use App\Models\MentorProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsController;

class StoreMentorProgramAction
{
    use AsController;

    public function handle(StoreMentorProgramRequest $request): RedirectResponse
    {
        $mentorProgram = MentorProgram::query()->create([
            ...$request->validated(),
            'mentor_id' => Auth::id(),
        ]);

        return redirect()->route('mentor-program.create', $mentorProgram);
    }
}
