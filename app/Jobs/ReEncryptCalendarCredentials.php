<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\UserCalendarIntegration;
use App\Services\Encryption\CalendarCredentialEncrypter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReEncryptCalendarCredentials implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var list<string> */
    private const array ENCRYPTED_FIELDS = [
        'access_token',
        'refresh_token',
        'client_id',
        'client_secret',
    ];

    public int $tries = 1;

    public function __construct(
        private readonly int $chunkSize = 100,
    ) {}

    public function handle(CalendarCredentialEncrypter $encrypter): void
    {
        $processed = 0;
        $failed = 0;

        UserCalendarIntegration::query()
            ->orderBy('id')
            ->chunk($this->chunkSize, function ($integrations) use ($encrypter, &$processed, &$failed): void {
                foreach ($integrations as $integration) {
                    try {
                        $this->reEncrypt($integration, $encrypter);
                        $processed++;
                    } catch (Throwable $e) {
                        $failed++;
                        Log::error('ReEncryptCalendarCredentials: failed to re-encrypt integration', [
                            'integration_id' => $integration->getKey(),
                            'error'          => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('ReEncryptCalendarCredentials: completed', [
            'processed' => $processed,
            'failed'    => $failed,
        ]);
    }

    private function reEncrypt(UserCalendarIntegration $integration, CalendarCredentialEncrypter $encrypter): void
    {
        $updates = ['last_encrypted_at' => now()];

        foreach (self::ENCRYPTED_FIELDS as $field) {
            $raw = $integration->getRawOriginal($field);

            if ($raw === null || $raw === '') {
                continue;
            }

            // Decrypt with current key(s), then re-encrypt with current key
            $plaintext = $encrypter->decrypt($raw);
            $updates[$field] = $encrypter->encrypt($plaintext);
        }

        // Write raw values directly to avoid double-encryption via the cast
        $integration->newQuery()
            ->where('id', $integration->getKey())
            ->update($updates);
    }
}
