<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\Auth\Http\Requests\UpdatePasswordRequest;

describe('UpdatePasswordRequest Validation', function (): void {
    describe('Successful Validation', function (): void {
        it('passes validation with correct data', function (): void {
            $this->seed(RoleSeeder::class);
            $user = User::factory()->create([
                'password' => Hash::make('OldPassword123!'),
            ]);
            $this->actingAs($user);

            $request = new UpdatePasswordRequest;
            $validator = Validator::make([
                'current_password'      => 'OldPassword123!',
                'password'              => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ], $request->rules());

            expect($validator->fails())->toBeFalse();
        });
    });

    describe('Extended Current Password Validation', function (): void {
        it('fails when current password is incorrect', function (): void {
            $this->seed(RoleSeeder::class);
            $realPassword = 'RealPassword123!';
            $user = User::factory()->create([
                'password' => Hash::make($realPassword),
            ]);
            $this->actingAs($user);

            $request = new UpdatePasswordRequest;
            $validator = Validator::make(
                [
                    'current_password'      => 'WrongPassword123!',
                    'password'              => 'NewPassword123!',
                    'password_confirmation' => 'NewPassword123!',
                ],
                $request->rules()
            );

            expect($validator->fails())->toBeTrue();
        });

        it('prevents setting same password as current', function (): void {
            $this->seed(RoleSeeder::class);
            $currentPassword = 'CurrentPassword123!';
            $user = User::factory()->create([
                'password' => Hash::make($currentPassword),
            ]);
            $this->actingAs($user);

            $request = new UpdatePasswordRequest;
            $validator = Validator::make(
                [
                    'current_password'      => $currentPassword,
                    'password'              => $currentPassword,
                    'password_confirmation' => $currentPassword,
                ],
                $request->rules()
            );

            expect($validator->fails())->toBeFalse();
        });
    });
});
