<?php

declare(strict_types=1);

use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;

mutates(UpdateUserRequest::class);

describe('User data Validation', function (): void {
    it('requires correct data', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateUserRequest;
        $request->setUserResolver(fn () => $user);

        $file = UploadedFile::fake()->image('avatar.jpg');

        $validator = Validator::make([
            'username'    => 'current_name',
            'email'       => 'email@email.com',
            'avatar'      => $file,
        ], $request->rules());

        expect($validator->fails())->toBeFalse();

    });

    it('returns expected validation keys', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateUserRequest;
        $request->setUserResolver(fn () => $user);

        $rules = $request->rules();

        expect(array_keys($rules))->toEqualCanonicalizing([
            'username',
            'email',
            'avatar',
        ]);
    });

    it('requires fields', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateUserRequest;
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'username'     => '',
            'email'        => '',
            'avatar'       => null,
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('username'))->toContain('The username field is required.')
            ->and($validator->errors()->get('email'))->toContain('The email field is required.')
            ->and($validator->errors()->has('avatar'))->toBeFalse();
    });

    it('wrong data type', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateUserRequest;
        $request->setUserResolver(fn () => $user);

        $file = UploadedFile::fake()->image('avatar.docx')->size(2000);
        $validator = Validator::make([
            'username'    => 1,
            'email'       => '',
            'avatar'      => $file,
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('username'))->toContain('The username field must be a string.')
            ->and($validator->errors()->get('email'))->toContain('The email field is required.')
            ->and($validator->errors()->get('avatar'))
            ->toContain('The avatar field must be an image.')
            ->toContain('The avatar field must be a file of type: jpg, jpeg, png, gif.')
            ->toContain('The avatar field must not be greater than 1024 kilobytes.');

    });

    it('wrong data length - short', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateUserRequest;
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'username'   => '1',
            'email'      => '1',
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('username'))
            ->toContain('The username field must be at least 5 characters.')
            ->and($validator->errors()->get('email'))
            ->toContain('The email field must be a valid email address.');
    });

    it('wrong data length - too long', function (): void {
        $user = User::factory()->make(['id' => 1]);
        $request = new UpdateUserRequest;
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'username'    => str_repeat('a', 300),
            'email'       => str_repeat('a', 300).'@email.com',
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->get('username'))
            ->toContain('The username field must not be greater than 255 characters.')
            ->and($validator->errors()->get('email'))
            ->toContain('The email field must not be greater than 255 characters.');
    });

    it('fails when email is already taken by another user', function (): void {
        $this->seed(RoleSeeder::class);
        User::factory()->create(['email' => 'test@example.com']);

        $user = User::factory()->make(['id' => 999]);

        $request = new UpdateUserRequest;
        $request->setUserResolver(fn () => $user);

        $validator = Validator::make([
            'username'  => 'Valid Name',
            'email'     => 'test@example.com',
        ], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });
});
