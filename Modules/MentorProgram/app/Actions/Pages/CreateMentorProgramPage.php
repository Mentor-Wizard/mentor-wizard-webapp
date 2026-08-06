<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Actions\Pages;

use App\Enums\MentorSessionDurationOptionsEnum;
use App\Enums\MentorSessionTypeEnum;
use App\Models\Currency;
use Exception;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;
use Throwable;

class CreateMentorProgramPage
{
    use AsController;

    /**
     * @throws Throwable
     */
    public function handle(): Response
    {
        $currencies = Currency::query()->pluck('name', 'id');

        throw_if($currencies->isEmpty(), Exception::class, 'Currencies table is empty');

        return Inertia::render('MentorProgram/CreateOrEdit', [
            'currencies'              => $currencies,
            'sessionTypeOptions'      => MentorSessionTypeEnum::values(),
            'sessionDurationOptions'  => MentorSessionDurationOptionsEnum::values(),
        ]);
    }
}
