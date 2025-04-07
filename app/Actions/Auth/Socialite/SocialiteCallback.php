<?php

declare(strict_types=1);

namespace App\Actions\Auth\Socialite;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class SocialiteCallback
{
    use AsController;

    public function handle(string $driver): RedirectResponse
    {
        $socialiteUser = Socialite::driver($driver)->stateless()->user();

        if (empty($socialiteUser->getEmail())) {
            Log::error('Email is empty, but required for login', ['driver' => $driver]);
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::firstOrCreate(
            ['email' => $socialiteUser->getEmail()],
            [
                'username' => empty($socialiteUser->getNickname()) ? $socialiteUser->getName() : $socialiteUser->getNickname(),
                'password' => Str::random(User::DEFAULT_PASSWORD_LENGHT),
            ]
        );

        Auth::login($user);

        return redirect()->route('pages.welcome');
    }
}
