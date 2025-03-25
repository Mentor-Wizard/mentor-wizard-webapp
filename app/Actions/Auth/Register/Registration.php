<?php

declare(strict_types=1);

namespace App\Actions\Auth\Register;

use App\Http\Requests\Auth\Register\RegistrationRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsController;

class Registration
{
    use AsController;

    public function handle(RegistrationRequest $request): RedirectResponse
    {
        $user = User::create([
            'username' => $request->get('username'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('pages.dashboard');
    }
}
