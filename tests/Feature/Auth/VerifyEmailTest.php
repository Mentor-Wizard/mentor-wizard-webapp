<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

describe('Email Verification', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('can verify email successfully', function () {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($verificationUrl)
            ->assertRedirect(route('pages.dashboard', ['verified' => 1]));

        $user->refresh();

        expect($user->hasVerifiedEmail())->toBeTrue();
    });

    it('cannot verify email with invalid signature', function () {
        $user = User::factory()->unverified()->create();

        $invalidVerificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => 'invalid-hash']
        );

        $response = $this->actingAs($user)->get($invalidVerificationUrl);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $user->refresh();
        expect($user->hasVerifiedEmail())->toBeFalse();
    });

    it('sends verification email', function () {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $user->sendEmailVerificationNotification();

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    });
});
