<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail as IlluminateVerifyEmail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Notification::fake();
});

describe('Successful Scenarios', function () {
    it('renders the verification notice screen', function () {
        $user = User::factory()->create();
        actingAs($user);
        $this->get(route('verification.notice'))->assertStatus(Response::HTTP_FOUND);
    });

    it('verifies the user email', function () {
        $user = User::factory()->unverified()->create();
        actingAs($user);

        $this->postJson(route('verification.send'))->assertStatus(Response::HTTP_FOUND);

        Notification::assertSentTo($user, IlluminateVerifyEmail::class, function (IlluminateVerifyEmail $notification) use ($user) {
            $verificationUrl = $notification->toMail($user)->actionUrl;
            $response = $this->get($verificationUrl);
            $response->assertStatus(Response::HTTP_FOUND);
            $user->refresh();

            return $user->hasVerifiedEmail();
        });
    });
});

describe('Failure Scenarios', function () {
    it('returns 404 for an incorrect link to the verification screen', function () {
        $user = User::factory()->create();
        actingAs($user);
        $this->get('vr-email')->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('returns 403 for an incorrect verification letter link', function () {
        $user = User::factory()->unverified()->create();
        actingAs($user)->postJson(route('verification.send'))->assertStatus(Response::HTTP_FOUND);

        Notification::assertSentTo($user, IlluminateVerifyEmail::class, function (IlluminateVerifyEmail $notification) use ($user) {
            $verificationUrl = $notification->toMail($user)->actionUrl;
            $response = $this->get($verificationUrl.hash('md2', 'test_wrong'));
            $response->assertStatus(Response::HTTP_FORBIDDEN);
            $user->refresh();

            return ! $user->hasVerifiedEmail();
        });
    });
});
