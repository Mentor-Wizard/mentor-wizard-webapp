<?php

use App\Http\Requests\Auth\Reset\CreatePasswordRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

mutates(CreatePasswordRequest::class);

describe('CreatePasswordRequest Validation', function () {
    describe('Successful Validation Scenarios', function () {
        it('passes validation with valid data', function () {
            $request = new CreatePasswordRequest();

            $data = [
                'token' => 'valid-token-123',
                'email' => 'user@example.com',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!'
            ];

            $validator = Validator::make($data, $request->rules());

            expect($validator->passes())->toBeTrue()
                ->and($validator->errors()->count())->toBe(0);
        });
    });

    describe('Validation Failure Scenarios', function () {
        it('fails validation when token is missing', function () {
            $request = new CreatePasswordRequest();

            $data = [
                'email' => 'user@example.com',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!'
            ];

            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('token'))->toHaveCount(1);
        });

        it('fails validation with invalid email', function () {
            $request = new CreatePasswordRequest();

            $data = [
                'token' => 'valid-token-123',
                'email' => 'invalid-email',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!'
            ];

            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('email'))->toHaveCount(1);
        });

        it('fails validation when password is not confirmed', function () {
            $request = new CreatePasswordRequest();

            $data = [
                'token' => 'valid-token-123',
                'email' => 'user@example.com',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'DifferentPassword123!'
            ];

            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('password'))->toHaveCount(1);
        });

        it('fails validation when password is missing', function () {
            $request = new CreatePasswordRequest();

            $data = [
                'token' => 'valid-token-123',
                'email' => 'user@example.com',
                'password_confirmation' => 'DifferentPassword123!'
            ];

            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('password'))->toHaveCount(1);
        });
    });

    describe('Authorization Scenarios', function () {
        it('always allows authorization', function () {
            $request = new CreatePasswordRequest();

            expect($request->authorize())->toBeTrue();
        });
    });

    describe('Password Validation', function () {
        it('rejects password that is too short', function () {
            $validator = Validator::make(
                [
                    'token' => 'valid-token-123',
                    'email' => 'user@example.com',
                    'password' => 'short',
                    'password_confirmation' => 'short'
                ],
                new CreatePasswordRequest()->rules()
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('password'))
                ->toHaveCount(1);
        });

        it('checks password without mixed case', function () {
            $validator = Validator::make(
                [
                    'token' => 'valid-token-123',
                    'email' => 'user@example.com',
                    'password' => 'lowercasepassword123',
                    'password_confirmation' => 'lowercasepassword123'
                ],
                new CreatePasswordRequest()->rules()
            );

            expect($validator->fails())->toBeFalse();
        });

        it('validates password with mixed case and numbers', function () {
            $validator = Validator::make(
                [
                    'token' => 'valid-token-123',
                    'email' => 'user@example.com',
                    'password' => 'StrongPassword123',
                    'password_confirmation' => 'StrongPassword123'
                ],
                new CreatePasswordRequest()->rules()
            );

            expect($validator->fails())->toBeFalse();
        });
    });
});
