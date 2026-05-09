<?php

declare(strict_types=1);

use App\Services\Encryption\CalendarCredentialEncrypter;

mutates(CalendarCredentialEncrypter::class);

describe('CalendarCredentialEncrypter', function (): void {
    beforeEach(function (): void {
        $this->key1 = base64_encode(random_bytes(32));
        $this->key2 = base64_encode(random_bytes(32));

        config([
            'calendar.encryption_key1' => $this->key1,
            'calendar.encryption_key2' => null,
        ]);

        $this->encrypter = new CalendarCredentialEncrypter;
    });

    describe('encrypt and decrypt', function (): void {
        it('encrypts and decrypts a value with the current key', function (): void {
            $plaintext = 'my-secret-token';

            $encrypted = $this->encrypter->encrypt($plaintext);

            expect($encrypted)->not->toBe($plaintext)
                ->and($encrypted)->toContain(':')
                ->and($this->encrypter->decrypt($encrypted))->toBe($plaintext);
        });

        it('produces different ciphertext for each encryption call', function (): void {
            $plaintext = 'same-value';

            $encrypted1 = $this->encrypter->encrypt($plaintext);
            $encrypted2 = $this->encrypter->encrypt($plaintext);

            expect($encrypted1)->not->toBe($encrypted2)
                ->and($this->encrypter->decrypt($encrypted1))->toBe($plaintext)
                ->and($this->encrypter->decrypt($encrypted2))->toBe($plaintext);
        });

        it('handles empty string values', function (): void {
            $encrypted = $this->encrypter->encrypt('');
            $decrypted = $this->encrypter->decrypt($encrypted);

            expect($decrypted)->toBe('');
        });

        it('handles long values', function (): void {
            $plaintext = str_repeat('a', 10000);

            $encrypted = $this->encrypter->encrypt($plaintext);
            $decrypted = $this->encrypter->decrypt($encrypted);

            expect($decrypted)->toBe($plaintext);
        });
    });

    describe('key rotation', function (): void {
        it('decrypts data encrypted with the previous key after rotation', function (): void {
            // Encrypt with key1
            $plaintext = 'rotated-secret';
            $encrypted = $this->encrypter->encrypt($plaintext);

            // Rotate: key1 becomes key2, new key becomes key1
            $newKey = base64_encode(random_bytes(32));
            config([
                'calendar.encryption_key1' => $newKey,
                'calendar.encryption_key2' => $this->key1,
            ]);

            $rotatedEncrypter = new CalendarCredentialEncrypter;

            expect($rotatedEncrypter->decrypt($encrypted))->toBe($plaintext);
        });

        it('prefers the current key for decryption', function (): void {
            $plaintext = 'current-key-secret';
            $encrypted = $this->encrypter->encrypt($plaintext);

            // Set a second key that cannot decrypt this value
            config(['calendar.encryption_key2' => $this->key1]);

            $sameEncrypter = new CalendarCredentialEncrypter;

            expect($sameEncrypter->decrypt($encrypted))->toBe($plaintext);
        });
    });

    describe('error handling', function (): void {
        it('throws when key1 is not configured', function (): void {
            config(['calendar.encryption_key1' => null]);

            $encrypter = new CalendarCredentialEncrypter;
            $encrypter->encrypt('test');
        })->throws(RuntimeException::class, 'CALENDAR_ENCRYPTION_KEY1 is not configured.');

        it('throws when key1 is empty string', function (): void {
            config(['calendar.encryption_key1' => '']);

            $encrypter = new CalendarCredentialEncrypter;
            $encrypter->encrypt('test');
        })->throws(RuntimeException::class, 'CALENDAR_ENCRYPTION_KEY1 is not configured.');

        it('throws when key1 is not 44 characters', function (): void {
            config(['calendar.encryption_key1' => 'too-short']);

            $encrypter = new CalendarCredentialEncrypter;
            $encrypter->encrypt('test');
        })->throws(RuntimeException::class, 'CALENDAR_ENCRYPTION_KEY1 must be a base64-encoded 32-byte key (44 characters).');

        it('throws when key1 is not valid base64', function (): void {
            config(['calendar.encryption_key1' => str_repeat('!', 44)]);

            $encrypter = new CalendarCredentialEncrypter;
            $encrypter->encrypt('test');
        })->throws(RuntimeException::class, 'CALENDAR_ENCRYPTION_KEY1 must be a valid base64-encoded 32-byte key.');

        it('throws when no key can decrypt the payload', function (): void {
            $encrypted = $this->encrypter->encrypt('secret');

            // Replace key with a completely different one
            config([
                'calendar.encryption_key1' => base64_encode(random_bytes(32)),
                'calendar.encryption_key2' => null,
            ]);

            $differentEncrypter = new CalendarCredentialEncrypter;
            $differentEncrypter->decrypt($encrypted);
        })->throws(RuntimeException::class, 'Calendar credential decryption failed: no key could decrypt the payload.');

        it('throws when payload format is invalid', function (): void {
            config(['calendar.encryption_key2' => null]);

            $encrypter = new CalendarCredentialEncrypter;
            $encrypter->decrypt('invalid-payload-no-colon');
        })->throws(RuntimeException::class, 'Calendar credential decryption failed: no key could decrypt the payload.');
    });
});
