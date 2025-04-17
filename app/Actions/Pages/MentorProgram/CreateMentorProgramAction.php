<?php

declare(strict_types=1);

namespace App\Actions\Pages\MentorProgram;

use Inertia\Inertia;
use Inertia\Response;
use App\Enums\CurrencyEnum;
use App\Models\MentorProgram;
use Lorisleiva\Actions\Concerns\AsController;

class CreateMentorProgramAction
{
    use AsController;

    public function handle(?MentorProgram $mentorProgram = null): Response
    {
        $currencies = array_combine(
            CurrencyEnum::names(),
            CurrencyEnum::values()
        );

        return Inertia::render('MentorProgram/CreateOrEdit', [
            'program' => $mentorProgram,
            'currencies' => $currencies,
        ]);
    }
}
