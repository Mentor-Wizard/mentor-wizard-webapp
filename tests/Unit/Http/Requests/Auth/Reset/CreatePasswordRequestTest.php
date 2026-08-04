<?php

declare(strict_types=1);

use App\Http\Requests\Auth\Reset\CreatePasswordRequest;
use Illuminate\Support\Facades\Validator;

describe('CreatePasswordRequest Validation', function (): void {
    describe('Authorization Scenarios', function (): void {
        it('always allows authorization', function (): void {
            $request = new CreatePasswordRequest;

            expect($request->authorize())->toBeTrue();
        });
    });

    describe('Password Validation', function (): void {
        it('checks password without mixed case', function (): void {
            $validator = Validator::make(
                [
                    'token'                 => 'valid-token-123',
                    'email'                 => 'user@example.com',
                    'password'              => 'lowercasepassword123',
                    'password_confirmation' => 'lowercasepassword123',
                ],
                (new CreatePasswordRequest)->rules()
            );

            expect($validator->fails())->toBeFalse();
        });

        it('validates password with mixed case and numbers', function (): void {
            $validator = Validator::make(
                [
                    'token'                 => 'valid-token-123',
                    'email'                 => 'user@example.com',
                    'password'              => 'StrongPassword123',
                    'password_confirmation' => 'StrongPassword123',
                ],
                (new CreatePasswordRequest)->rules()
            );

            expect($validator->fails())->toBeFalse();
        });
    });
});
