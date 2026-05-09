<?php

declare(strict_types=1);

use App\Enums\CalendarProviderEnum;
use App\Http\Requests\Calendar\ExternalCalendar\ExternalCalendarConnectCallbackRequest;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

mutates(ExternalCalendarConnectCallbackRequest::class);

describe('ExternalCalendarConnectCallbackRequest', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);

        $this->user = User::factory()->create();
    });

    describe('authorize', function (): void {
        it('always returns true regardless of authentication state', function (): void {
            $response = get(route('external-calendar.connect.callback', ['provider' => 'google']));

            // Not a 403 — authorize() returned true
            $response->assertStatus(302);
            $response->assertRedirectToRoute('profile.edit');
        });
    });

    describe('rules', function (): void {
        it('requires code when no error param is present', function (): void {
            $response = get(route('external-calendar.connect.callback', ['provider' => 'google']));

            // Missing 'code' fails validation → redirects to profile.edit
            $response->assertRedirectToRoute('profile.edit');
            $response->assertSessionHas('error');
        });

        it('skips code validation when error param is present', function (): void {
            $response = get(
                route('external-calendar.connect.callback', ['provider' => 'google']).'?error=access_denied',
            );

            // No validation error — rules() returns [] when error is present
            $response->assertStatus(302);
            $response->assertSessionMissing('errors');
        });
    });

    describe('failedValidation', function (): void {
        it('cleans up the calendar integration on failed validation', function (): void {
            // Must be authenticated so $request->user() is non-null inside failedValidation,
            // otherwise cleanupIntegration() short-circuits on the null-user guard.
            actingAs($this->user);

            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
            ]);

            $state = encrypt(json_encode(['user_id' => $this->user->getKey(), 'provider' => 'google']));

            get(route('external-calendar.connect.callback', ['provider' => 'google']).'?state='.urlencode($state));

            $this->assertDatabaseMissing(UserCalendarIntegration::class, [
                'id' => $integration->getKey(),
            ]);
        });

        it('redirects to profile.edit with an error flash when validation fails', function (): void {
            $response = get(route('external-calendar.connect.callback', ['provider' => 'google']));

            $response->assertRedirectToRoute('profile.edit');
            $response->assertSessionHas('error');
        });
    });
});
