<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Http\Resources\UserResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

mutates(UserResource::class);

describe('UserResource', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('correctly transforms a user model to an array', function (): void {
        $role = Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value);
        $user = User::factory()->create([
            'username'          => 'johndoe',
            'email'             => 'john@example.com',
            'email_verified_at' => now(),
        ]);
        $user->syncRoles($role);

        $userCollection = $user->newCollection([$user]);
        $resource = new UserResource($userCollection);
        $result = $resource->toArray(request());

        expect($result)
            ->toBeArray()
            ->toHaveKey('data')
            ->and($result['data'])
            ->toBeInstanceOf(Illuminate\Support\Collection::class)
            ->and($result['data']->first())
            ->toBeArray()
            ->toHaveKeys(['id', 'username', 'email', 'created_at', 'updated_at', 'profile'])
            ->and($result['data']->first()['username'])->toBe('johndoe')
            ->and($result['data']->first()['email'])->toBe('john@example.com')
            ->and($result['data']->first()['profile'])
            ->toBeArray()
            ->toHaveKeys(['id', 'name', 'last_name', 'linkedin', 'telegram', 'whatsapp', 'phone', 'description', 'title', 'avatar']);

    });

    it('includes only public information when transforming user', function (): void {
        $user = User::factory()->create([
            'password'       => 'hashed_password',
            'remember_token' => 'some_token',
        ]);
        $userCollection = $user->newCollection([$user]);
        $resource = new UserResource($userCollection);
        $result = $resource->toArray(request());

        expect($result)
            ->toBeArray()
            ->toHaveKey('data')
            ->and($result['data'])
            ->toBeInstanceOf(Illuminate\Support\Collection::class)
            ->and($result['data']->first())
            ->toBeArray()
            ->not->toHaveKeys(['password', 'remember_token']);
    });

    it('properly formats timestamps', function (): void {
        $now = now();
        $user = User::factory()->create([
            'email_verified_at' => $now,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
        $userCollection = $user->newCollection([$user]);

        $resource = new UserResource($userCollection);
        $result = $resource->toArray(request());

        expect($result)
            ->toBeArray()
            ->toHaveKey('data')
            ->and($result['data'])
            ->toBeInstanceOf(Illuminate\Support\Collection::class)
            ->and($result['data']->first())
            ->toBeArray()
            ->toHaveKeys(['created_at', 'updated_at'])
            ->and($result['data']->first()['created_at'])->toBeInstanceOf(Carbon\CarbonImmutable::class)
            ->and($result['data']->first()['updated_at'])->toBeInstanceOf(Carbon\CarbonImmutable::class);
    });

    test('user resource contains all required profile attributes', function (): void {
        $user = User::factory()->create();

        $avatarUrl = sprintf(
            'https://ui-avatars.com/api/?name=%s&background=random&size=256&format=png',
            urlencode('John Doe')
        );

        $user->profile->addMediaFromUrl($avatarUrl)
            ->usingFileName('avatar.png')
            ->toMediaCollection('avatar');

        $user->profile->update([
            'name'        => 'John',
            'last_name'   => 'Doe',
            'linkedin'    => 'https://linkedin.com/in/johndoe',
            'telegram'    => '@johndoe',
            'whatsapp'    => '+1234567890',
            'phone'       => '+9876543210',
            'description' => 'Test description',
            'title'       => 'Senior Developer',
        ]);
        $user->refresh();

        $resource = new UserResource(collect([$user]));
        $resourceArray = $resource->toArray(request());

        expect($resourceArray['data'][0]['profile'])
            ->toBeArray()
            ->toMatchArray([
                'id'          => $user->profile->id,
                'name'        => 'John',
                'last_name'   => 'Doe',
                'linkedin'    => 'https://linkedin.com/in/johndoe',
                'telegram'    => '@johndoe',
                'whatsapp'    => '+1234567890',
                'phone'       => '+9876543210',
                'description' => 'Test description',
                'title'       => 'Senior Developer',
                'avatar'      => $user->profile->getFirstMediaUrl('avatar'),
            ]);
    });

    test('user resource handles null profile correctly', function (): void {
        Event::fake();
        $user = User::factory()->create();
        $resource = new UserResource(collect([$user]));
        $resourceArray = $resource->toArray(request());

        expect($resourceArray['data'][0]['profile'])
            ->toBeNull();
    });

    it('includes meta key in the response', function (): void {
        $user = User::factory()->create();
        $userCollection = $user->newCollection([$user]);
        $resource = new UserResource($userCollection);
        $result = $resource->toArray(request());

        expect($result)->toHaveKey('meta');
    });

});
