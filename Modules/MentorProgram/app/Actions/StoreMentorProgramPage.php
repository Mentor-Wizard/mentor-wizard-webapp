<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\MentorProgram\Http\Requests\StoreMentorProgramRequest;
use Modules\MentorProgram\Models\MentorProgram;

class StoreMentorProgramPage
{
    use AsController;

    public function handle(StoreMentorProgramRequest $request): RedirectResponse
    {
        MentorProgram::query()->create([
            ...$request->validated(),
            'mentor_id' => Auth::id(),
        ]);

        return to_route('mentor-program.list')
            ->with('success', 'Mentor program created successfully.');
    }
}
