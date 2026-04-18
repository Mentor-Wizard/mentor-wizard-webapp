<?php

declare(strict_types=1);

use App\Console\Commands\Calendar\DecryptTestTokensCommand;
use App\Services\Encryption\CalendarCredentialEncrypter;

mutates(DecryptTestTokensCommand::class);

describe('calendar:decrypt-test-tokens command', function (): void {
    beforeEach(function (): void {
        $encryptionKey = base64_encode(random_bytes(32));
        config(['calendar.encryption_key1' => $encryptionKey]);
        $this->encrypter = new CalendarCredentialEncrypter;
    });

    it('runs successfully and exits with SUCCESS code', function (): void {
        $this->artisan('calendar:decrypt-test-tokens')
            ->assertExitCode(0);
    });

    it('displays "not set" for tokens that are not configured', function (): void {
        config([
            'calendar.testing.outlook.access_token'  => null,
            'calendar.testing.outlook.refresh_token' => null,
            'calendar.testing.google.access_token'   => null,
            'calendar.testing.google.refresh_token'  => null,
            'calendar.testing.google.client_id'      => null,
            'calendar.testing.google.client_secret'  => null,
            'calendar.testing.apple.id'              => null,
            'calendar.testing.apple.app_password'    => null,
            'calendar.testing.outlook.calendar_id'   => null,
            'calendar.testing.google.calendar_id'    => null,
            'calendar.testing.apple.calendar_url'    => null,
        ]);

        $this->artisan('calendar:decrypt-test-tokens')
            ->expectsOutputToContain('not set')
            ->assertExitCode(0);
    });

    it('decrypts a stored token and shows the plaintext value', function (): void {
        $encrypted = $this->encrypter->encrypt('my-secret-access-token');

        config(['calendar.testing.outlook.access_token' => $encrypted]);

        $this->artisan('calendar:decrypt-test-tokens')
            ->expectsOutputToContain('my-secret-access-token')
            ->assertExitCode(0);
    });

    it('shows the raw value when decryption fails (unencrypted plain text)', function (): void {
        config(['calendar.testing.outlook.access_token' => 'plain-text-not-encrypted']);

        $this->artisan('calendar:decrypt-test-tokens')
            ->expectsOutputToContain('plain-text-not-encrypted')
            ->assertExitCode(0);
    });

    it('displays plain calendar IDs directly without decryption', function (): void {
        config([
            'calendar.testing.outlook.calendar_id' => 'test-outlook-cal-id',
            'calendar.testing.google.calendar_id'  => 'test-google-cal-id',
            'calendar.testing.apple.calendar_url'  => 'https://caldav.icloud.com/calendars/test/',
        ]);

        $this->artisan('calendar:decrypt-test-tokens')
            ->expectsOutputToContain('test-outlook-cal-id')
            ->expectsOutputToContain('test-google-cal-id')
            ->assertExitCode(0);
    });
});
