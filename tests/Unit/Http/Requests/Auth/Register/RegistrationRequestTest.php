<?php

declare(strict_types=1);

use App\Http\Requests\Auth\Register\RegistrationRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Validator;

describe('RegistrationRequest Validation', function (): void {
    describe('Positive Scenarios', function (): void {
        it('validates correct registration data', function (): void {
            $data = [
                'username'              => 'validuser',
                'email'                 => 'valid@example.com',
                'password'              => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
                'timezone'              => 'Europe/Kyiv',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->passes())->toBeTrue();
        });
    });

    describe('Email Validation', function (): void {
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
                'timezone'              => 'Europe/Kyiv',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('email'))->toHaveCount(1);
        });
    });

    describe('Password Validation Without Strict Rules', function (): void {
        it('requires timezone validation check', function (): void {
            $data = [
                'username'              => 'validuser',
                'email'                 => 'test@example.com',
                'password'              => 'somepassword',
                'password_confirmation' => 'somepassword',
                'timezone'              => null,
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('timezone'))->toHaveCount(1);
        });

        it('requires timezone validation check with number', function (): void {
            $data = [
                'username'              => 'validuser',
                'email'                 => 'test@example.com',
                'password'              => 'somepassword',
                'password_confirmation' => 'somepassword',
                'timezone'              => 234234,
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('timezone'))->toHaveCount(2);
        });

        it('check custom timezones', function (): void {
            $data = [
                'username'              => 'validuser',
                'email'                 => 'test@example.com',
                'password'              => 'somepassword',
                'password_confirmation' => 'somepassword',
                'timezone'              => 'Europe/Kiev',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->passes())->toBeTrue();

            $data = [
                'username'              => 'validuser',
                'email'                 => 'test@example.com',
                'password'              => 'somepassword',
                'password_confirmation' => 'somepassword',
                'timezone'              => 'Asia/Calcutta',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->passes())->toBeTrue();
        });

        it('check wrong timezones', function (): void {
            $data = [
                'username'              => 'validuser',
                'email'                 => 'test@example.com',
                'password'              => 'somepassword',
                'password_confirmation' => 'somepassword',
                'timezone'              => 'Europe/Wrong',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('timezone'))->toHaveCount(1);

            $data = [
                'username'              => 'validuser',
                'email'                 => 'test@example.com',
                'password'              => 'somepassword',
                'password_confirmation' => 'somepassword',
                'timezone'              => 'Wrong/Calcutta',
            ];

            $request = new RegistrationRequest;
            $validator = Validator::make($data, $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->get('timezone'))->toHaveCount(1);
        });
    });
});
