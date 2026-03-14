<?php

declare(strict_types=1);

namespace App\Actions\MentorPrograms;

use App\Http\Requests\MentorProgram\StoreMentorProgramRequest;
use App\Models\MentorProgram;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class StoreMentorProgramPage
{
    use AsController;

    public function handle(StoreMentorProgramRequest $request): Response
    {
        MentorProgram::query()->create([
            ...$request->validated(),
            'mentor_id' => Auth::id(),
        ]);

        return Inertia::location(route('mentor-program.list'));
    }
}
