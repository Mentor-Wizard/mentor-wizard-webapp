<?php

declare(strict_types=1);

use App\Http\Requests\Profile\UpdateProfileInfoRequest;

mutates(UpdateProfileInfoRequest::class);

describe('Profile data Validation', function (): void {
    it('requires correct data', function (): void {
        $request = new UpdateProfileInfoRequest;

        $validator = Validator::make([
            'linkedin'    => 'https://www.linkedin.com/in/john',
            'telegram'    => 'https://t.me/john',
            'whatsapp'    => 'https://wa.me/john',
            'description' => 'description!',
            'phone'       => '+380671234567',
        ], $request->rules());

        expect($validator->fails())->toBeFalse();

    });

    it('requires current password', function (): void {
        $request = new UpdateProfileInfoRequest;

        $request->merge([
            'phone' => '+38 067 123 45 67',
        ]);

        $reflection = new ReflectionMethod($request, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($request);

        expect($request->input('phone'))->toBe('+380671234567');
    });

    it('returns expected validation keys', function (): void {
        $request = new UpdateProfileInfoRequest;

        $rules = $request->rules();

        expect(array_keys($rules))->toEqualCanonicalizing([
            'linkedin',
            'telegram',
            'whatsapp',
            'description',
            'phone',
        ]);
    });

    it('nullable fields', function (): void {
        $request = new UpdateProfileInfoRequest;

        $validator = Validator::make([
            'linkedin'     => null,
            'telegram'     => null,
            'whatsapp'     => null,
            'description'  => null,
            'phone'        => null,
        ], $request->rules());

        expect($validator->fails())->toBeFalse()
            ->and($validator->errors()->has('linkedin'))->toBeFalse()
            ->and($validator->errors()->has('telegram'))->toBeFalse()
            ->and($validator->errors()->has('whatsapp'))->toBeFalse()
            ->and($validator->errors()->has('description'))->toBeFalse()
            ->and($validator->errors()->has('phone'))->toBeFalse();
    });

    it('wrong data type', function (): void {
        $request = new UpdateProfileInfoRequest;
        $validator = Validator::make([
            'linkedin'    => 1,
            'telegram'    => 1,
            'whatsapp'    => 1,
            'description' => 1,
            'phone'       => 123,
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('linkedin'))->toContain('The linkedin field must be a string.')
            ->and($validator->errors()->get('telegram'))->toContain('The telegram field must be a string.')
            ->and($validator->errors()->get('whatsapp'))->toContain('The whatsapp field must be a string.')
            ->and($validator->errors()->get('description'))->toContain('The description field must be a string.')
            ->and($validator->errors()->get('phone'))->toContain('The phone field format is invalid.');
    });

    it('wrong data length', function (): void {
        $request = new UpdateProfileInfoRequest;

        $validator = Validator::make([
            'linkedin'    => 'https://www.linkedin.com/in/'.str_repeat('a', 600),
            'telegram'    => 'https://t.me/'.str_repeat('a', 600),
            'whatsapp'    => 'https://wa.me/'.str_repeat('a', 600),
            'description' => str_repeat('a', 6000),
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('linkedin'))
            ->toContain('The linkedin field must not be greater than 200 characters.')
            ->and($validator->errors()->get('telegram'))
            ->toContain('The telegram field must not be greater than 100 characters.')
            ->and($validator->errors()->get('whatsapp'))
            ->toContain('The whatsapp field must not be greater than 100 characters.')
            ->and($validator->errors()->get('description'))
            ->toContain('The description field must not be greater than 1000 characters.');
    });

    it('requires incorrect data', function (): void {
        $request = new UpdateProfileInfoRequest;

        $validator = Validator::make([
            'linkedin'    => 'linkedin_linkedin_linkedin',
            'telegram'    => 'telegram_telegram_telegram',
            'whatsapp'    => 'whatsapp_whatsapp',
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('linkedin'))
            ->toContain('The linkedin field format is invalid.')
            ->and($validator->errors()->get('telegram'))
            ->toContain('The telegram field format is invalid.')
            ->and($validator->errors()->get('whatsapp'))
            ->toContain('The whatsapp field format is invalid.');
    });
});
