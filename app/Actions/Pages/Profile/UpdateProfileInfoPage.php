<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Http\Requests\Profile\UpdateProfileInfoRequest;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class UpdateProfileInfoPage
{
    use AsController;

    public function handle(UpdateProfileInfoRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $user->profile()->updateOrCreate([], $this->dataUpdate($request));

        return redirect()->route('profile.edit');
    }

    private function dataUpdate(UpdateProfileInfoRequest $request): array
    {
        return [
            'linkedin'    => $request->get('linkedin'),
            'telegram'    => $request->get('telegram'),
            'whatsapp'    => $request->get('whatsapp'),
            'description' => $request->get('description'),
            'phone'       => $request->get('phone'),
        ];
    }
}
