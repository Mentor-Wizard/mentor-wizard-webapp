<?php

declare(strict_types=1);

namespace App\Actions\MentorPrograms;

use App\Models\MentorProgram;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class DeleteMentorProgram
{
    use AsController;

    public function handle(Request $request, MentorProgram $mentorProgram): RedirectResponse
    {
        throw_unless($mentorProgram->exists, new ModelNotFoundException('Mentor program not found.'));

        abort_if($request->user()->cannot('delete', $mentorProgram), Response::HTTP_FORBIDDEN, 'Unauthorized action.');

        $mentorProgram->delete();

        return redirect()->route('mentor-program.list');
    }
}
