<?php

declare(strict_types=1);

namespace Modules\Auth\Actions\Socialite;

use Illuminate\Http\RedirectResponse as LaravelRedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Auth\Enums\SocialiteDriverEnum;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class SocialiteRedirect
{
    use AsController;

    public function handle(string $driver): SymfonyRedirectResponse|LaravelRedirectResponse
    {
        if (! SocialiteDriverEnum::isValid($driver)) {
            Log::error('Invalid socialite driver', ['driver' => $driver]);
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return Socialite::driver($driver)->redirect();
    }
}
