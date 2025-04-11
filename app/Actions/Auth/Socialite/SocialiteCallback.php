<?php

declare(strict_types=1);

namespace App\Actions\Auth\Socialite;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider as SocialiteAbstractProvider;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class SocialiteCallback
{
    use AsController;

    public function handle(string $driver): RedirectResponse
    {
        /** @var SocialiteAbstractProvider $provider */
        $provider = Socialite::driver($driver);

        $socialiteUser = $provider->stateless()->user();

        $email = $socialiteUser->getEmail();

        if (! $email) {
            Log::error('Email is empty, but required for login', ['driver' => $driver]);
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::query()->firstOrCreate(['email' => $email], [
            'username' => $socialiteUser->getNickname() ?: $socialiteUser->getName(),
            'password' => Str::random(User::DEFAULT_PASSWORD_LENGHT),
        ]);

        Auth::login($user);

        return redirect()->route('pages.welcome');
    }
}
