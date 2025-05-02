<?php

declare(strict_types=1);

use App\Http\Requests\Profile\UpdateProfileMainRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;

mutates(UpdateProfileMainRequest::class);

describe('Profile data Validation', function (): void {
    it('requires correct data', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateProfileMainRequest;
        $request->setUserResolver(fn () => $user);

        $file = UploadedFile::fake()->image('avatar.jpg');

        $validator = Validator::make([
            'name'        => 'current_name',
            'last_name'   => 'last_name!',
            'email'       => 'email@email.com',
            'avatar'      => $file,
        ], $request->rules());

        expect($validator->fails())->toBeFalse();

    });

    it('returns expected validation keys', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateProfileMainRequest;
        $request->setUserResolver(fn () => $user);

        $rules = $request->rules();

        expect(array_keys($rules))->toEqualCanonicalizing([
            'name',
            'last_name',
            'email',
            'avatar',
        ]);
    });

    it('requires fields', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateProfileMainRequest;
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'name'         => '',
            'last_name'    => '',
            'email'        => '',
            'avatar'       => null,
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('name'))->toContain('The name field is required.')
            ->and($validator->errors()->get('last_name'))->toContain('The last name field is required.')
            ->and($validator->errors()->get('email'))->toContain('The email field is required.')
            ->and($validator->errors()->has('avatar'))->toBeFalse();
    });

    it('wrong data type', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateProfileMainRequest;
        $request->setUserResolver(fn () => $user);
        $file = UploadedFile::fake()->image('avatar.docx')->size(2000);
        $validator = Validator::make([
            'name'        => 1,
            'last_name'   => 1,
            'email'       => '',
            'avatar'      => $file,
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('name'))->toContain('The name field must be a string.')
            ->and($validator->errors()->get('last_name'))->toContain('The last name field must be a string.')
            ->and($validator->errors()->get('email'))->toContain('The email field is required.')
            ->and($validator->errors()->get('avatar'))
            ->toContain('The avatar field must be an image.')
            ->toContain('The avatar field must be a file of type: jpg, jpeg, png, gif.')
            ->toContain('The avatar field must not be greater than 1024 kilobytes.');

    });

    it('wrong data length - short', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateProfileMainRequest;
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

    it('wrong data length - too long', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateProfileMainRequest;
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'name'        => str_repeat('a', 60),
            'last_name'   => str_repeat('a', 60),
            'email'       => str_repeat('a', 300).'@email.com',
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('name'))
            ->toContain('The name field must not be greater than 50 characters.')
            ->and($validator->errors()->get('last_name'))
            ->toContain('The last name field must not be greater than 50 characters.')
            ->and($validator->errors()->get('email'))
            ->toContain('The email field must not be greater than 255 characters.');
    });

    it('fails when email is already taken by another user', function (): void {
        $this->seed(RoleSeeder::class);
        User::factory()->create(['email' => 'test@example.com']);

        $user = User::factory()->make(['id' => 999]);

        $request = new UpdateProfileMainRequest;
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
