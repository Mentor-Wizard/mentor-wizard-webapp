<?php

declare(strict_types=1);

use App\Actions\Auth\UpdatePassword;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

mutates(UpdatePassword::class);

describe('UpdatePassword Action', function () {

    it('updates user password', function () {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $request = Mockery::mock(UpdatePasswordRequest::class);
        $request->shouldReceive('user')->andReturn($user);

        $request->shouldReceive('offsetGet')
            ->with('password')
            ->andReturn('new_password');

        $request->shouldReceive('offsetExists')
            ->with('password')
            ->andReturnTrue();

        $action = new UpdatePassword;
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and(Hash::check('new_password', $user->fresh()->password))->toBeTrue();
    });
});
