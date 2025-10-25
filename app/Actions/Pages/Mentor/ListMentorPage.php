<?php

declare(strict_types=1);

namespace App\Actions\Pages\Mentor;

use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class ListMentorPage
{
    use AsController;

    public function handle(): Response
    {

        return Inertia::render('Mentor/ListPage', [

        ]);
    }
}
