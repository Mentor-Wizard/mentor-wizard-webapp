<?php

declare(strict_types=1);

namespace App\Casts;

use App\Services\Encryption\CalendarCredentialEncrypter;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<string|null, string|null>
 */
readonly class EncryptedCalendarCredential implements Castable, CastsAttributes
{
    public function __construct(
        private CalendarCredentialEncrypter $encrypter,
    ) {}

    public static function castUsing(array $arguments): static
    {
        return resolve(static::class);
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->encrypter->decrypt((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->encrypter->encrypt((string) $value);
    }
}
