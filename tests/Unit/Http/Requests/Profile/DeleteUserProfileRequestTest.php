<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Profile;

use App\Http\Requests\UserProfile\DeleteUserProfileRequest;
use Illuminate\Support\Facades\Validator;

mutates(DeleteUserProfileRequest::class);

describe('Delete User Profile Request Validation', function (): void {
    it('requires password field', function (): void {
        $request = new DeleteUserProfileRequest;

        $validator = Validator::make([], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('password'))->toBeTrue()
            ->and($validator->errors()->get('password'))->toContain('The password field is required.');
    });

    it('returns expected validation rules', function (): void {
        $request = new DeleteUserProfileRequest;

        $rules = $request->rules();

        expect(array_keys($rules))->toEqual(['password'])
            ->and($rules['password'])->toContain('required')
            ->and($rules['password'])->toContain('current_password');
    });
});
