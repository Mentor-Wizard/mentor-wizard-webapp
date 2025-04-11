<?php

declare(strict_types=1);

namespace App\Actions\Auth\Socialite;

use App\Enums\SocialiteDriver;
use Illuminate\Http\RedirectResponse as LaravelRedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class SocialiteRedirect
{
    use AsController;

    public function handle(string $driver): SymfonyRedirectResponse|LaravelRedirectResponse
    {
        if (! SocialiteDriver::isValid($driver)) {
            Log::error('Invalid socialite driver', ['driver' => $driver]);
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return Socialite::driver($driver)->redirect();
    }
}
