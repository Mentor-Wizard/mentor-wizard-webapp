<?php

declare(strict_types=1);

use App\Http\Requests\Auth\Reset\ResetPasswordRequest;
use Illuminate\Support\Facades\Validator;

mutates(ResetPasswordRequest::class);

describe('ResetPasswordRequest Validation', function () {
    describe('Email validation', function () {
        it('requires email to be present', function () {
            $request = new ResetPasswordRequest;

            $validator = Validator::make(['email' => ''], $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('email'))->toBeTrue();
        });

        it('accepts a valid email', function () {
            $request = new ResetPasswordRequest;

            $validator = Validator::make(['email' => 'test@example.com'], $request->rules());

            expect($validator->fails())->toBeFalse();
        });

        it('rejects an invalid email', function () {
            $request = new ResetPasswordRequest;

            $validator = Validator::make(['email' => 'invalid-email'], $request->rules());

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('email'))->toBeTrue();
        });
    });

    describe('Authorization', function () {
        it('always allows the request', function () {
            $request = new ResetPasswordRequest;

            expect($request->authorize())->toBeTrue();
        });
    });
});
