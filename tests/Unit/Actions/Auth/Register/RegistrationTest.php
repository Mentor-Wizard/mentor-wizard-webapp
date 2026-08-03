<?php

declare(strict_types=1);

use App\Actions\Auth\Register\Registration;
use App\Http\Requests\Auth\Register\RegistrationRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

describe('Registration Action', function (): void {

    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        Event::fake();
    });

    it('can register', function (): void {
        $request = new RegistrationRequest([
            'username'              => 'testuser',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $result = (new Registration)->handle($request);

        expect($result)->toBeInstanceOf(RedirectResponse::class)
            ->and($result->isRedirect())->toBeTrue()
            ->and($result->getTargetUrl())->toBe(route('pages.dashboard'));

        $user = User::query()->where('email', 'test@example.com')->first();

        Event::assertDispatched(fn (Registered $event): bool => $event->user->username === $user->username && $event->user->email === $user->email);

        expect(Auth::check())->toBeTrue()
            ->and(Auth::user()->is($user))->toBeTrue()
            ->and(Hash::check('password', $user->password))->toBeTrue()
            ->and($user->username)->toBe('testuser')
            ->and($user->email)->toBe('test@example.com');
    });

});
