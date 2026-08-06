<?php

declare(strict_types=1);

namespace Modules\Auth\Actions\Register;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Auth\Http\Requests\Register\RegistrationRequest;

class Registration
{
    use AsController;

    public function handle(RegistrationRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'username' => $request->input('username'),
            'email'    => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('pages.dashboard');
    }
}
