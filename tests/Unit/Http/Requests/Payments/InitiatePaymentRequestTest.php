<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Http\Requests\Payments\InitiatePaymentRequest;
use App\Models\MentorProgram;
use App\Models\MentorSession;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

mutates(InitiatePaymentRequest::class);

describe('InitiatePaymentRequest', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('authorizes every request', function (): void {
        expect((new InitiatePaymentRequest)->authorize())->toBeTrue();
    });

    it('exposes exactly the expected validation keys', function (): void {
        expect(array_keys((new InitiatePaymentRequest)->rules()))
            ->toEqualCanonicalizing(['payable_type', 'payable_id']);
    });

    it('passes with a valid mentor_session payload', function (): void {
        $validator = Validator::make([
            'payable_type' => 'mentor_session',
            'payable_id'   => 5,
        ], (new InitiatePaymentRequest)->rules());

        expect($validator->fails())->toBeFalse();
    });

    it('passes with a valid mentor_program payload', function (): void {
        $validator = Validator::make([
            'payable_type' => 'mentor_program',
            'payable_id'   => 5,
        ], (new InitiatePaymentRequest)->rules());

        expect($validator->fails())->toBeFalse();
    });

    it('fails when payable_type is not an allowed value', function (): void {
        $validator = Validator::make([
            'payable_type' => 'unknown',
            'payable_id'   => 5,
        ], (new InitiatePaymentRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('payable_type'))->toBeTrue();
    });

    it('fails when payable_type is missing', function (): void {
        $validator = Validator::make([
            'payable_id' => 5,
        ], (new InitiatePaymentRequest)->rules());

        expect($validator->errors()->has('payable_type'))->toBeTrue();
    });

    it('fails when payable_id is missing', function (): void {
        $validator = Validator::make([
            'payable_type' => 'mentor_session',
        ], (new InitiatePaymentRequest)->rules());

        expect($validator->errors()->has('payable_id'))->toBeTrue();
    });

    it('fails when payable_id is not an integer', function (): void {
        $validator = Validator::make([
            'payable_type' => 'mentor_session',
            'payable_id'   => 'abc',
        ], (new InitiatePaymentRequest)->rules());

        expect($validator->errors()->has('payable_id'))->toBeTrue();
    });

    it('fails when payable_id is below the minimum', function (): void {
        $validator = Validator::make([
            'payable_type' => 'mentor_session',
            'payable_id'   => 0,
        ], (new InitiatePaymentRequest)->rules());

        expect($validator->errors()->has('payable_id'))->toBeTrue();
    });

    describe('resolvePayableModel', function (): void {
        it('resolves a MentorSession for the mentor_session type', function (): void {
            $session = MentorSession::factory()->create();

            $request = new InitiatePaymentRequest;
            $request->merge([
                'payable_type' => 'mentor_session',
                'payable_id'   => $session->getKey(),
            ]);

            $resolved = $request->resolvePayableModel();

            expect($resolved)->toBeInstanceOf(MentorSession::class)
                ->and($resolved->getKey())->toBe($session->getKey());
        });

        it('resolves a MentorProgram for the mentor_program type', function (): void {
            $mentor = User::factory()->create();
            $mentor->assignRole(RoleEnum::MENTOR->value);

            $program = MentorProgram::factory()->create(['mentor_id' => $mentor->getKey()]);

            $request = new InitiatePaymentRequest;
            $request->merge([
                'payable_type' => 'mentor_program',
                'payable_id'   => $program->getKey(),
            ]);

            $resolved = $request->resolvePayableModel();

            expect($resolved)->toBeInstanceOf(MentorProgram::class)
                ->and($resolved->getKey())->toBe($program->getKey());
        });

        it('throws ModelNotFoundException when the session does not exist', function (): void {
            $request = new InitiatePaymentRequest;
            $request->merge([
                'payable_type' => 'mentor_session',
                'payable_id'   => 999999,
            ]);

            expect(fn (): MentorSession|MentorProgram => $request->resolvePayableModel())
                ->toThrow(ModelNotFoundException::class);
        });

        it('throws ModelNotFoundException when the program does not exist', function (): void {
            $request = new InitiatePaymentRequest;
            $request->merge([
                'payable_type' => 'mentor_program',
                'payable_id'   => 999999,
            ]);

            expect(fn (): MentorSession|MentorProgram => $request->resolvePayableModel())
                ->toThrow(ModelNotFoundException::class);
        });
    });
});
