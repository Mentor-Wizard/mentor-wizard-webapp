<?php

declare(strict_types=1);

namespace App\Console\Commands\Calendar;

use App\Services\Encryption\CalendarCredentialEncrypter;
use Illuminate\Console\Command;
use Throwable;

class DecryptTestTokensCommand extends Command
{
    private const array TOKENS = [
        'TEST_OUTLOOK_ACCESS_TOKEN'  => 'calendar.testing.outlook.access_token',
        'TEST_OUTLOOK_REFRESH_TOKEN' => 'calendar.testing.outlook.refresh_token',
        'TEST_GOOGLE_ACCESS_TOKEN'   => 'calendar.testing.google.access_token',
        'TEST_GOOGLE_REFRESH_TOKEN'  => 'calendar.testing.google.refresh_token',
        'TEST_GOOGLE_CLIENT_ID'      => 'calendar.testing.google.client_id',
        'TEST_GOOGLE_CLIENT_SECRET'  => 'calendar.testing.google.client_secret',
        'TEST_APPLE_ID'              => 'calendar.testing.apple.id',
        'TEST_APPLE_APP_PASSWORD'    => 'calendar.testing.apple.app_password',
    ];

    private const array PLAIN = [
        'TEST_OUTLOOK_CALENDAR_ID' => 'calendar.testing.outlook.calendar_id',
        'TEST_GOOGLE_CALENDAR_ID'  => 'calendar.testing.google.calendar_id',
        'TEST_APPLE_CALENDAR_URL'  => 'calendar.testing.apple.calendar_url',
    ];

    protected $signature = 'calendar:decrypt-test-tokens';

    protected $description = 'Decrypt and print the TEST_* calendar integration tokens from the current environment';

    public function handle(CalendarCredentialEncrypter $encrypter): int
    {
        $rows = [];

        foreach (self::TOKENS as $envKey => $configKey) {
            $raw = config($configKey);

            if (! is_string($raw) || $raw === '') {
                $rows[] = [$envKey, '<fg=yellow>not set</>'];

                continue;
            }

            try {
                $decrypted = $encrypter->decrypt($raw);
                $rows[] = [$envKey, $decrypted];
            } catch (Throwable) {
                $rows[] = [$envKey, $raw];
            }
        }

        foreach (self::PLAIN as $envKey => $configKey) {
            $value = config($configKey);
            $rows[] = [$envKey, is_string($value) && $value !== '' ? $value : '<fg=yellow>not set</>'];
        }

        $this->table(['Key', 'Value'], $rows);

        return self::SUCCESS;
    }
}
