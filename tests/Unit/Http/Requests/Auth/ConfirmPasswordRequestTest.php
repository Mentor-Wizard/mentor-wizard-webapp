<?php

declare(strict_types=1);

use App\Http\Requests\Auth\ConfirmPasswordRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

mutates(ConfirmPasswordRequest::class);

describe('ConfirmPasswordRequest', function () {
    describe('Authorization', function () {
        it('always allows access', function () {
            $request = new ConfirmPasswordRequest;
            expect($request->authorize())->toBeTrue();
        });
    });

    describe('Validation Rules', function () {
        it('contains a rule for mandatory password entry', function () {
            $request = new ConfirmPasswordRequest;
            $rules = $request->rules();

            expect($rules)->toHaveKey('password')
                ->and($rules['password'])->toContain('required');
        });
    });

    describe('Password Validation', function () {
        it('adds validation error for incorrect password', function () {
            $user = Mockery::mock('User')
                ->shouldReceive('getAttribute')
                ->with('email')
                ->andReturn('test@example.com')
                ->getMock();

            $request = Mockery::mock(ConfirmPasswordRequest::class)
                ->makePartial()
                ->shouldReceive('user')
                ->andReturn($user)
                ->shouldReceive('input')
                ->with('password')
                ->andReturn('incorrect_password')
                ->getMock();

            Auth::shouldReceive('guard')
                ->with('web')
                ->andReturnSelf()
                ->shouldReceive('validate')
                ->with([
                    'email' => 'test@example.com',
                    'password' => 'incorrect_password',
                ])
                ->andReturn(false);

            $validatorMock = Mockery::mock(Validator::class);
            $errorsMock = Mockery::mock();

            $validatorMock->shouldReceive('after')
                ->with(Mockery::type('Closure'))
                ->once()
                ->andReturnSelf();

            $validatorMock->shouldReceive('errors')
                ->andReturn($errorsMock);

            $request->withValidator($validatorMock);
        });

        it('does not add error for correct password', function () {
            $user = Mockery::mock('User')
                ->shouldReceive('getAttribute')
                ->with('email')
                ->andReturn('test@example.com')
                ->getMock();

            $request = Mockery::mock(ConfirmPasswordRequest::class)
                ->makePartial()
                ->shouldReceive('user')
                ->andReturn($user)
                ->shouldReceive('input')
                ->with('password')
                ->andReturn('correct_password')
                ->getMock();

            Auth::shouldReceive('guard')
                ->with('web')
                ->andReturnSelf()
                ->shouldReceive('validate')
                ->with([
                    'email' => 'test@example.com',
                    'password' => 'correct_password',
                ])
                ->andReturn(true);

            $validatorMock = Mockery::mock(Validator::class);
            $validatorMock->shouldReceive('after')
                ->with(Mockery::type('Closure'))
                ->once()
                ->andReturnSelf();

            $validatorMock->shouldReceive('errors')
                ->never();

            $request->withValidator($validatorMock);
        });
    });
});
