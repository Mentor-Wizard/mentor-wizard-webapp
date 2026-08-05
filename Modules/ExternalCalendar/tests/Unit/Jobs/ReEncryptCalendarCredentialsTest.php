<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Log;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Jobs\ReEncryptCalendarCredentials;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Services\Encryption\CalendarCredentialEncrypter;

mutates(ReEncryptCalendarCredentials::class);

describe('ReEncryptCalendarCredentials job', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->key1 = base64_encode(random_bytes(32));

        config([
            'calendar.encryption_key1' => $this->key1,
            'calendar.encryption_key2' => null,
        ]);

        $this->encrypter = new CalendarCredentialEncrypter;
    });

    it('re-encrypts all credential fields for existing integrations', function (): void {
        $originalToken = 'my-access-token-'.fake()->sha256();
        $originalRefresh = 'my-refresh-token-'.fake()->sha256();

        $integration = UserCalendarIntegration::factory()->create([
            'provider'      => CalendarProviderEnum::GOOGLE,
            'access_token'  => $originalToken,
            'refresh_token' => $originalRefresh,
        ]);

        // Capture the raw encrypted values before re-encryption
        $rawBefore = $integration->getRawOriginal('access_token');

        new ReEncryptCalendarCredentials()->handle($this->encrypter);

        $integration->refresh();

        // Raw value should have changed (re-encrypted)
        $rawAfter = $integration->getRawOriginal('access_token');
        expect($rawAfter)->not->toBe($rawBefore);

        // Decrypted value should remain the same
        expect($integration->access_token)->toBe($originalToken)
            ->and($integration->refresh_token)->toBe($originalRefresh);
    });

    it('updates last_encrypted_at timestamp', function (): void {
        $integration = UserCalendarIntegration::factory()->create([
            'provider'          => CalendarProviderEnum::GOOGLE,
            'last_encrypted_at' => null,
        ]);

        new ReEncryptCalendarCredentials()->handle($this->encrypter);

        $integration->refresh();
        expect($integration->last_encrypted_at)->not->toBeNull();
    });

    it('skips null and empty credential fields', function (): void {
        $integration = UserCalendarIntegration::factory()->create([
            'provider'      => CalendarProviderEnum::GOOGLE,
            'access_token'  => null,
            'refresh_token' => null,
            'client_id'     => null,
            'client_secret' => null,
        ]);

        new ReEncryptCalendarCredentials()->handle($this->encrypter);

        $integration->refresh();
        expect($integration->access_token)->toBeNull()
            ->and($integration->refresh_token)->toBeNull();
    });

    it('logs completion summary', function (): void {
        Log::spy();

        UserCalendarIntegration::factory()->count(2)->create([
            'provider' => CalendarProviderEnum::GOOGLE,
        ]);

        new ReEncryptCalendarCredentials()->handle($this->encrypter);

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context): bool => str_contains($message, 'completed')
                && $context['processed'] === 2
                && $context['failed'] === 0)
            ->once();
    });

    it('continues processing when a single integration fails and logs the error', function (): void {
        Log::spy();

        $goodIntegration = UserCalendarIntegration::factory()->create([
            'provider' => CalendarProviderEnum::GOOGLE,
        ]);

        // Create an integration with a corrupted raw value that cannot be decrypted
        $badIntegration = UserCalendarIntegration::factory()->create([
            'provider' => CalendarProviderEnum::GOOGLE,
        ]);

        // Corrupt the raw encrypted value directly in DB
        $badIntegration->newQuery()
            ->where('id', $badIntegration->getKey())
            ->update(['access_token' => 'corrupted:data']);

        new ReEncryptCalendarCredentials()->handle($this->encrypter);

        Log::shouldHaveReceived('error')
            ->once();

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context): bool => str_contains($message, 'completed')
                && $context['failed'] === 1)
            ->once();
    });
});
