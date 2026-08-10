<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Inertia\Response;
use Modules\Marketplace\Actions\Pages\GetMentorProfilePage;

describe('Mentor Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
    });

    it('throws AuthorizationException if user is not mentor', function (): void {
        $user = User::factory()->create();

        $action = new GetMentorProfilePage;

        expect(fn (): Response => $action->handle(Request::create('/'), $user))
            ->toThrow(ModelNotFoundException::class);
    });
});
