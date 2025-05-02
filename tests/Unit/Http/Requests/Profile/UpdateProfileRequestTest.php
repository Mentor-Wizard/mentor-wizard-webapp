<?php

declare(strict_types=1);

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;

mutates(UpdateProfileRequest::class);

describe('Profile data Validation', function (): void {
    it('requires correct data', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateProfileRequest;
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'name'        => 'current_name',
            'last_name'   => 'last_name!',
            'linkedin'    => 'linkedin!',
            'telegram'    => 'telegram!',
            'whatsapp'    => 'whatsapp!',
            'description' => 'description!',
            'phone'       => '+380671234567',
            'email'       => 'email@email.com',
        ], $request->rules());

        expect($validator->fails())->toBeFalse();

    });

    it('requires current password', function (): void {
        $request = new UpdateProfileRequest;

        $request->merge([
            'phone' => '+38 067 123 45 67',
        ]);

        $reflection = new ReflectionMethod($request, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($request);

        expect($request->input('phone'))->toBe('+380671234567');
    });

    it('returns expected validation keys', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateProfileRequest;
        $request->setUserResolver(fn () => $user);

        $rules = $request->rules();

        expect(array_keys($rules))->toEqualCanonicalizing([
            'name',
            'last_name',
            'linkedin',
            'telegram',
            'whatsapp',
            'description',
            'phone',
            'email',
            'avatar',
        ]);
    });

    it('requires fields', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateProfileRequest;
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'name'         => '',
            'last_name'    => '',
            'email'        => '',
            'linkedin'     => null,
            'telegram'     => null,
            'whatsapp'     => null,
            'description'  => null,
            'phone'        => null,
            'avatar'       => null,
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('name'))->toContain('The name field is required.')
            ->and($validator->errors()->get('last_name'))->toContain('The last name field is required.')
            ->and($validator->errors()->get('email'))->toContain('The email field is required.')
            ->and($validator->errors()->has('linkedin'))->toBeFalse()
            ->and($validator->errors()->has('telegram'))->toBeFalse()
            ->and($validator->errors()->has('whatsapp'))->toBeFalse()
            ->and($validator->errors()->has('description'))->toBeFalse()
            ->and($validator->errors()->has('phone'))->toBeFalse()
            ->and($validator->errors()->has('avatar'))->toBeFalse();
    });

    it('requires wrong data', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateProfileRequest;
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'name'        => 1,
            'last_name'   => 1,
            'linkedin'    => 1,
            'telegram'    => 1,
            'whatsapp'    => 1,
            'description' => 1,
            'phone'       => 123,
            'email'       => '',
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('name'))->toContain('The name field must be a string.')
            ->and($validator->errors()->get('last_name'))->toContain('The last name field must be a string.')
            ->and($validator->errors()->get('linkedin'))->toContain('The linkedin field must be a string.')
            ->and($validator->errors()->get('telegram'))->toContain('The telegram field must be a string.')
            ->and($validator->errors()->get('whatsapp'))->toContain('The whatsapp field must be a string.')
            ->and($validator->errors()->get('description'))->toContain('The description field must be a string.')
            ->and($validator->errors()->get('phone'))->toContain('The phone field format is invalid.')
            ->and($validator->errors()->get('email'))->toContain('The email field is required.');

    });

    it('requires wrong data 2', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateProfileRequest;
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'name'        => '1',
            'last_name'   => '1',
            'email'       => '1',
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('name'))
            ->toContain('The name field must be at least 5 characters.')
            ->and($validator->errors()->get('last_name'))
            ->toContain('The last name field must be at least 5 characters.')
            ->and($validator->errors()->get('email'))
            ->toContain('The email field must be a valid email address.');

    });

    it('requires wrong data 3', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateProfileRequest;
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'name'        => str_repeat('a', 60),
            'last_name'   => str_repeat('a', 60),
            'linkedin'    => str_repeat('a', 600),
            'telegram'    => str_repeat('a', 600),
            'whatsapp'    => str_repeat('a', 600),
            'description' => str_repeat('a', 6000),
            'email'       => str_repeat('a', 300).'@email.com',
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('name'))
            ->toContain('The name field must not be greater than 50 characters.')
            ->and($validator->errors()->get('last_name'))
            ->toContain('The last name field must not be greater than 50 characters.')
            ->and($validator->errors()->get('linkedin'))
            ->toContain('The linkedin field must not be greater than 200 characters.')
            ->and($validator->errors()->get('telegram'))
            ->toContain('The telegram field must not be greater than 100 characters.')
            ->and($validator->errors()->get('whatsapp'))
            ->toContain('The whatsapp field must not be greater than 100 characters.')
            ->and($validator->errors()->get('description'))
            ->toContain('The description field must not be greater than 1000 characters.')
            ->and($validator->errors()->get('email'))
            ->toContain('The email field must not be greater than 255 characters.');
    });

    it('fails when email is already taken by another user', function (): void {
        $this->seed(RoleSeeder::class);
        User::factory()->create(['email' => 'test@example.com']);

        $user = User::factory()->make(['id' => 999]);

        $request = new UpdateProfileRequest;
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'name'      => 'Valid Name',
            'last_name' => 'Valid Last',
            'email'     => 'test@example.com',
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });
});
