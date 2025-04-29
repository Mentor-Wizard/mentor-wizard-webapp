<?php

declare(strict_types=1);

namespace App\Actions\Pages\MentorProgram;

use App\Models\Currency;
use App\Models\MentorProgram;
use Exception;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class EditMentorProgramPage
{
    use AsController;

    public function handle(?MentorProgram $mentorProgram = null): Response
    {
        $currencies = Currency::query()->pluck('name', 'id');
        throw_if($currencies->isEmpty(), new Exception('Currencies table is empty'));

        return Inertia::render('MentorProgram/CreateOrEdit', [
            'program'    => $mentorProgram,
            'currencies' => $currencies,
        ]);
    }
}
