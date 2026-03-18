<?php

declare(strict_types=1);

namespace App\Actions\Pages\MentorProgram;

use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class ListMentorProgramPage
{
    use AsController;

    public function handle(): Response
    {
        $programs = auth()
            ->user()
            ->mentorPrograms()
            ->select('id', 'name', 'slug', 'is_main', 'description', 'cost', 'currency_id', 'created_at')
            ->with(['currency:id,symbol'])
            ->orderByDesc('is_main')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();

        return Inertia::render('MentorProgram/ListPage', [
            'programs' => $programs,
        ]);
    }
}
