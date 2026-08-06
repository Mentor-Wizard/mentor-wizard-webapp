<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Actions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\MentorProgram\Models\MentorProgram;
use Symfony\Component\HttpFoundation\Response;

class DeleteMentorProgram
{
    use AsController;

    public function handle(Request $request, MentorProgram $mentorProgram): RedirectResponse
    {
        throw_unless($mentorProgram->exists, ModelNotFoundException::class, 'Mentor program not found.');

        abort_if($request->user()->cannot('delete', $mentorProgram), Response::HTTP_FORBIDDEN, 'Unauthorized action.');

        $mentorProgram->delete();

        return to_route('mentor-program.list');
    }
}
