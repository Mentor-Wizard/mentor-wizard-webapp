<?php

declare(strict_types=1);

namespace App\Actions\Pages\MentorProgram;

use App\Models\Currency;
use Exception;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class CreateMentorProgramPage
{
    use AsController;

    public function handle(): Response
    {
        $currencies = Currency::query()->pluck('name', 'id');

        throw_if($currencies->isEmpty(), new Exception('Currencies table is empty'));

        return Inertia::render('MentorProgram/CreateOrEdit', [
            'currencies' => $currencies,
        ]);
    }
}
