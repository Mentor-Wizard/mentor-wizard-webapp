<?php

declare(strict_types=1);

namespace App\Actions\Pages;

use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class DashboardPage
{
    use AsController;

    public function handle(): Response
    {
        return Inertia::render('Dashboard');
    }
}
