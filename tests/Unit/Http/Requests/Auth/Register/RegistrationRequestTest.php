<?php

declare(strict_types=1);

use App\Http\Requests\Auth\Register\RegistrationRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Validator;

mutates(RegistrationRequest::class);

describe('RegistrationRequest Validation', function () {
    describe('Positive Scenarios', function () {
        it('validates correct registration data', function () {
            $data = [
                'username' => 'validuser',
                'email' => 'valid@example.com',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->passes())->toBeTrue();
        });
    });

    describe('Username Validation', function () {
        it('fails when username is too short', function () {
            $data = [
                'username' => 'user',
                'email' => 'test@example.com',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('username'))->toHaveCount(1);
        });

        it('fails when username is missing', function () {
            $data = [
                'email' => 'test@example.com',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('username'))->toHaveCount(1);
        });
    });

    describe('Email Validation', function () {
        it('fails when email is invalid', function () {
            $data = [
                'username' => 'validuser',
                'email' => 'invalid-email',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('email'))->toHaveCount(1);
        });

        it('fails when email is not unique', function () {
            $this->seed(RoleSeeder::class);
            User::factory()->create([
                'email' => 'existing@example.com',
            ]);

            $data = [
                'username' => 'validuser',
                'email' => 'existing@example.com',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('email'))->toHaveCount(1);
        });
    });

    describe('Password Validation', function () {
        it('fails when password is not confirmed', function () {
            $data = [
                'username' => 'validuser',
                'email' => 'test@example.com',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'DifferentPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('password'))->toHaveCount(1);
        });

        it('requires password', function () {
            $data = [
                'username' => 'validuser',
                'email' => 'test@example.com',
                'password_confirmation' => 'password123',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('password'))->toHaveCount(1);
        });
    });

    describe('Authorization', function () {
        it('always allows registration request', function () {
            $request = new RegistrationRequest;
            expect($request->authorize())->toBeTrue();
        });
    });

    describe('Password Validation Without Strict Rules', function () {
        it('passes with a simple password', function () {
            $data = [
                'username' => 'validuser',
                'email' => 'test@example.com',
                'password' => 'simple123',
                'password_confirmation' => 'simple123',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->passes())->toBeTrue();
        });

        it('fails with a short password', function () {
            $data = [
                'username' => 'validuser',
                'email' => 'test@example.com',
                'password' => '123',
                'password_confirmation' => '123',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->passes())->toBeFalse();
        });

        it('requires both password and confirmation', function () {
            $data = [
                'username' => 'validuser',
                'email' => 'test@example.com',
                'password' => 'somepassword',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('password'))->toHaveCount(1);
        });
    });
});
