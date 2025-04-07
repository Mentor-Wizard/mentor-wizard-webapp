<?php

use App\Actions\Auth\Register\Registration;
use App\Http\Requests\Auth\Register\RegistrationRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

mutates(Registration::class);

describe('Registration Action', function () {

    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        Event::fake();
    });

    it('can register', function () {
        $request = new RegistrationRequest([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $result = new Registration()->handle($request);

        expect($result)->toBeInstanceOf(RedirectResponse::class)
            ->and($result->isRedirect())->toBeTrue()
            ->and($result->getTargetUrl())->toBe(route('pages.dashboard'));

        $user = User::where('email', 'test@example.com')->first();

        Event::assertDispatched(Registered::class, function (Registered $event) use ($user) {
            return $event->user->username === $user->username && $event->user->email === $user->email;
        });

        expect(Auth::check())->toBeTrue()
            ->and(Auth::user()->is($user))->toBeTrue()
            ->and(Hash::check('password', $user->password))->toBeTrue()
            ->and($user->username)->toBe('testuser')
            ->and($user->email)->toBe('test@example.com');
    });


    it('can not register without username', function () {
        $request = new RegistrationRequest([
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        new Registration()->handle($request);

        Event::assertNotDispatched(Registered::class);
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);

    })->throws(QueryException::class);

    it('can not register without email', function () {
        $request = new RegistrationRequest([
            'username' => 'testuser',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        new Registration()->handle($request);

        Event::assertNotDispatched(Registered::class);
        $this->assertDatabaseMissing('users', [
            'username' => 'testuser',
        ]);
    })->throws(QueryException::class);
});
