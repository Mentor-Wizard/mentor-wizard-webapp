<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Requests\Auth\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsController;

class UpdatePassword
{
    use AsController;

    public function handle(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make(Arr::get($request, 'password')),
        ]);

        return back();
    }
}
