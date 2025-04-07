<?php

declare(strict_types=1);

use App\Actions\Auth\Reset\CreatePassword;
use App\Http\Requests\Auth\Reset\CreatePasswordRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

mutates(CreatePassword::class);

describe('CreatePassword Action', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        Event::fake();
    });

    it('should reset password successfully', closure: function () {
        $oldPassword = 'initial_password';
        $newPassword = 'new_password';

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make($oldPassword),
        ]);

        $request = Mockery::mock(CreatePasswordRequest::class);

        $request->shouldReceive('only')
            ->with('email', 'password', 'password_confirmation', 'token')
            ->andReturn([
                'email' => $user->email,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
                'token' => 'test_token',
            ]);
        $request->shouldReceive('get')
            ->with('password')
            ->andReturn($newPassword);

        $request->shouldReceive('all')
            ->andReturn([
                'email' => $user->email,
                'password' => 'new_password',
                'password_confirmation' => 'new_password',
                'token' => 'test_token',
            ]);

        Password::shouldReceive('reset')
            ->andReturnUsing(function (array $credentials, $callback) use ($user) {
                expect($user->email)->toBe(Arr::get($credentials, 'email'));
                $callback($user);

                return Password::PASSWORD_RESET;
            });

        $action = new CreatePassword;
        $result = $action->handle($request);

        expect($result->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($result->getSession()->get('status'))->toBe('Your password has been reset.');

        $user->refresh();

        expect(Hash::check($newPassword, $user->password))->toBeTrue()
            ->and(strlen($user->remember_token))->toBe(60)
            ->and($user->remember_token)->not()->toBeNull();

        Event::assertDispatched(PasswordReset::class, function (PasswordReset $event) use ($user) {
            return $event->user === $user;
        });
    });

    it('should throw validation exception when password reset fails', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);
        $errorStatus = Password::INVALID_TOKEN;
        $request = Mockery::mock(CreatePasswordRequest::class);

        $request->shouldReceive('only')
            ->with('email', 'password', 'password_confirmation', 'token')
            ->andReturn([
                'email' => $user->email,
                'password' => 'new_password',
                'password_confirmation' => 'new_password',
                'token' => 'invalid_token',
            ]);

        Password::shouldReceive('reset')
            ->andReturn(Password::INVALID_TOKEN);

        Lang::shouldReceive('trans')
            ->with($errorStatus)
            ->andReturn('Invalid reset token');
        Lang::shouldReceive('get')
            ->with('passwords.token', [], null)
            ->andReturn('Invalid reset token');

        $action = new CreatePassword;

        $exception = null;
        try {
            $action->handle($request);
        } catch (ValidationException $e) {
            $exception = $e;
        }

        expect($exception)->toBeInstanceOf(ValidationException::class)
            ->and($exception->errors())->toHaveKey('email')
            ->and($exception->errors()['email'][0])->toBe('Invalid reset token');

        Event::assertNotDispatched(PasswordReset::class);
    });

    it('redirects to login with correct status when password reset is successful', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);
        $errorStatus = Password::PASSWORD_RESET;

        $request = Mockery::mock(CreatePasswordRequest::class);
        $request->shouldReceive('only')
            ->with('email', 'password', 'password_confirmation', 'token')
            ->andReturn([
                'email' => $user->email,
                'password' => 'new_password',
                'password_confirmation' => 'new_password',
                'token' => 'valid_token',
            ]);
        $request->shouldReceive('get')
            ->with('password')
            ->andReturn('new_password');

        Lang::shouldReceive('trans')
            ->with($errorStatus)
            ->andReturn('Invalid reset token');
        Lang::shouldReceive('get')
            ->with('passwords.reset', [], null)
            ->andReturn('passwords.reset');

        Password::shouldReceive('reset')
            ->andReturnUsing(function (array $credentials, $callback) use ($user) {
                $callback($user);

                return Password::PASSWORD_RESET;
            });

        $action = new CreatePassword;
        $response = $action->handle($request);

        expect($response->getStatusCode())->toBe(302)
            ->and($response->getTargetUrl())->toBe(route('login'))
            ->and($response->getSession()->get('status'))->toBe('passwords.reset');
    });

    it('handles different password reset statuses correctly', function (string $status) {
        $user = User::factory()->create();

        $request = Mockery::mock(CreatePasswordRequest::class);
        $request->shouldReceive('only')
            ->with('email', 'password', 'password_confirmation', 'token')
            ->andReturn([
                'email' => $user->email,
                'password' => 'new_password',
                'password_confirmation' => 'new_password',
                'token' => 'invalid_token',
            ]);

        Password::shouldReceive('reset')
            ->andReturn($status);

        $action = new CreatePassword;

        try {
            $action->handle($request);
            $this->fail("Expected ValidationException for status $status");
        } catch (ValidationException $exception) {

            expect($exception->errors())->toHaveKey('email')
                ->and(Arr::get($exception->errors(), 'email.0'))->toBe(trans($status));
        }
    })->with([
        Password::INVALID_USER,
        Password::INVALID_TOKEN,
        Password::RESET_THROTTLED,
    ]);

    it('changes password and remember token during reset', function () {
        $oldPassword = 'old_password';
        $newPassword = 'new_password';

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make($oldPassword),
            'remember_token' => 'old_token',
        ]);

        $request = Mockery::mock(CreatePasswordRequest::class);
        $request->shouldReceive('only')
            ->with('email', 'password', 'password_confirmation', 'token')
            ->andReturn([
                'email' => $user->email,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
                'token' => 'reset_token',
            ]);
        $request->shouldReceive('get')
            ->with('password')
            ->andReturn('new_password');

        Password::shouldReceive('reset')
            ->andReturnUsing(function ($credentials, $callback) use ($user) {
                $callback($user);

                return Password::PASSWORD_RESET;
            });

        $action = new CreatePassword;
        $action->handle($request);

        $user->refresh();

        expect(Hash::check($newPassword, $user->password))->toBeTrue()
            ->and($user->remember_token)->not()->toBe('old_token')
            ->and(strlen($user->remember_token))->toBe(60);
    });
});
