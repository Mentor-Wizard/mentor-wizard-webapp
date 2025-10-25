<?php

declare(strict_types=1);

use App\Http\Requests\Auth\Register\RegistrationRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Validator;

mutates(RegistrationRequest::class);

describe('RegistrationRequest Validation', function (): void {
    describe('Positive Scenarios', function (): void {
        it('validates correct registration data', function (): void {
            $data = [
                'username'              => 'validuser',
                'email'                 => 'valid@example.com',
                'password'              => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->passes())->toBeTrue();
        });
    });

    describe('Username Validation', function (): void {
        it('fails when username is too short', function (): void {
            $data = [
                'username'              => 'user',
                'email'                 => 'test@example.com',
                'password'              => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('username'))->toHaveCount(1);
        });

        it('fails when username is too long', function (): void {
            $data = [
                'username'              => str_repeat('A', 256),
                'email'                 => 'test@example.com',
                'password'              => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('username'))->toHaveCount(1);
        });

        it('fails when username is not string', function (): void {
            $data = [
                'username'              => 1234567890,
                'email'                 => 'test@example.com',
                'password'              => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('username'))->toHaveCount(1);
        });

        it('fails when username is missing', function (): void {
            $data = [
                'email'                 => 'test@example.com',
                'password'              => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('username'))->toHaveCount(1);
        });
    });

    describe('Email Validation', function (): void {
        it('fails when email is invalid', function (): void {
            $data = [
                'username'              => 'validuser',
                'email'                 => 'invalid-email',
                'password'              => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('email'))->toHaveCount(1);
        });

        it('fails when email is not unique', function (): void {
            $this->seed(RoleSeeder::class);
            User::factory()->create([
                'email' => 'existing@example.com',
            ]);

            $data = [
                'username'              => 'validuser',
                'email'                 => 'existing@example.com',
                'password'              => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('email'))->toHaveCount(1);
        });
    });

    describe('Password Validation', function (): void {
        it('fails when password is not confirmed', function (): void {
            $data = [
                'username'              => 'validuser',
                'email'                 => 'test@example.com',
                'password'              => 'StrongPassword123!',
                'password_confirmation' => 'DifferentPassword123!',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('password'))->toHaveCount(1);
        });

        it('requires password', function (): void {
            $data = [
                'username'              => 'validuser',
                'email'                 => 'test@example.com',
                'password_confirmation' => 'password123',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('password'))->toHaveCount(1);
        });
    });

    describe('Authorization', function (): void {
        it('always allows registration request', function (): void {
            $request = new RegistrationRequest;
            expect($request->authorize())->toBeTrue();
        });
    });

    describe('Password Validation Without Strict Rules', function (): void {
        it('passes with a simple password', function (): void {
            $data = [
                'username'              => 'validuser',
                'email'                 => 'test@example.com',
                'password'              => 'simple123',
                'password_confirmation' => 'simple123',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->passes())->toBeTrue();
        });

        it('fails with a short password', function (): void {
            $data = [
                'username'              => 'validuser',
                'email'                 => 'test@example.com',
                'password'              => '123',
                'password_confirmation' => '123',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->passes())->toBeFalse();
        });

        it('requires both password and confirmation', function (): void {
            $data = [
                'username' => 'validuser',
                'email'    => 'test@example.com',
                'password' => 'somepassword',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('password'))->toHaveCount(1);
        });
    });
});
