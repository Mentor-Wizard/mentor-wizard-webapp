<?php

declare(strict_types=1);

namespace App\Casts;

use App\Services\Encryption\CalendarCredentialEncrypter;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<string|null, string|null>
 */
class EncryptedCalendarCredential implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return resolve(CalendarCredentialEncrypter::class)->decrypt((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return resolve(CalendarCredentialEncrypter::class)->encrypt((string) $value);
    }
}
