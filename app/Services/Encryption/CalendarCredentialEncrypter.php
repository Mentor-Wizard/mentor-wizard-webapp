<?php

declare(strict_types=1);

namespace App\Services\Encryption;

use RuntimeException;

class CalendarCredentialEncrypter
{
    private const string CIPHER = 'aes-256-gcm';

    private const int IV_BYTES = 12;

    private const int TAG_BYTES = 16;

    /**
     * Encrypts a value with the current key.
     * Payload format: base64(iv) . ':' . base64(tag) . ':' . base64(ciphertext)
     */
    public function encrypt(string $value): string
    {
        $key = $this->getCurrentKey();
        $iv = random_bytes(self::IV_BYTES);
        $tag = '';

        $encrypted = openssl_encrypt($value, self::CIPHER, $key,
            OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_BYTES);

        throw_if($encrypted === false,
            RuntimeException::class, 'Calendar credential encryption failed.');

        return base64_encode($iv).':'.base64_encode($tag).':'.base64_encode($encrypted);
    }

    /**
     * Decrypts a payload, trying the current key first then the previous key.
     */
    public function decrypt(string $payload): string
    {
        foreach ($this->decryptionKeys() as $key) {
            $result = $this->attemptDecrypt($payload, $key);

            if ($result !== null) {
                return $result;
            }
        }

        throw new RuntimeException('Calendar credential decryption failed: no key could decrypt the payload.');
    }

    private function attemptDecrypt(string $payload, string $key): ?string
    {
        $parts = explode(':', $payload, 3);

        if (count($parts) !== 3) {
            return null;
        }

        $iv = base64_decode($parts[0], strict: true);
        $tag = base64_decode($parts[1], strict: true);
        $ciphertext = base64_decode($parts[2], strict: true);

        if ($iv === false || $tag === false || $ciphertext === false) {
            return null;
        }

        $decrypted = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $decrypted === false ? null : $decrypted;
    }

    private function getCurrentKey(): string
    {
        return $this->resolveKey('calendar.encryption_key1', 'CALENDAR_ENCRYPTION_KEY1');
    }

    /**
     * @return list<string>
     */
    private function decryptionKeys(): array
    {
        $keys = [$this->getCurrentKey()];

        $key2 = config('calendar.encryption_key2');

        if (is_string($key2) && $key2 !== '') {
            $keys[] = $this->resolveKey('calendar.encryption_key2', 'CALENDAR_ENCRYPTION_KEY_PREVIOUS');
        }

        return $keys;
    }

    private function resolveKey(string $configKey, string $envName): string
    {
        $raw = config($configKey);

        throw_if(! is_string($raw) || $raw === '', RuntimeException::class,
            $envName.' is not configured.');

        // A base64-encoded 32-byte key is always 44 ASCII characters (with padding).
        // Checking the encoded length avoids binary mb_strlen issues on the decoded value.
        throw_if(mb_strlen($raw) !== 44, RuntimeException::class,
            $envName.' must be a base64-encoded 32-byte key (44 characters).');

        $decoded = base64_decode($raw, strict: true);

        throw_if($decoded === false, RuntimeException::class,
            $envName.' must be a valid base64-encoded 32-byte key.');

        return $decoded;
    }
}
