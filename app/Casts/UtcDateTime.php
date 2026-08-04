<?php

declare(strict_types=1);

namespace App\Casts;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * Stores datetime attributes as wall-clock values in `config('app.timezone')`,
 * regardless of the timezone the Carbon instance was created in.
 *
 * Laravel's built-in `datetime` cast keeps the original timezone of the value
 * being assigned instead of converting it to the application timezone before
 * formatting, which silently corrupts the stored instant when the underlying
 * column has no timezone offset (e.g. Postgres `timestamp without time zone`).
 *
 * Laravel caches class-castable attributes by the raw value assigned to them
 * (`Illuminate\Database\Eloquent\Concerns\HasAttributes::$classCastCache`), so
 * a freshly assigned, not-yet-normalized value (e.g. a plain `DateTime` from a
 * factory) would otherwise be handed back as-is until the model is reloaded.
 * `$withoutObjectCaching` disables that cache so `get()` always normalizes the
 * value that was actually persisted to `$attributes`.
 *
 * @implements CastsAttributes<CarbonInterface, DateTimeInterface|string>
 */
class UtcDateTime implements CastsAttributes
{
    public bool $withoutObjectCaching = true;

    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonInterface
    {
        if ($value === null) {
            return null;
        }

        return Date::parse((string) $value, (string) config('app.timezone'));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $date = $value instanceof DateTimeInterface
            ? Date::instance($value)
            : Date::parse((string) $value);

        return $date->copy()
            ->timezone((string) config('app.timezone'))
            ->format($model->getDateFormat());
    }
}
