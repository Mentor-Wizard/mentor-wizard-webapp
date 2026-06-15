<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\EncryptedCalendarCredential;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use Carbon\Carbon;
use Database\Factories\UserCalendarIntegrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property CalendarProviderEnum $provider
 * @property CalendarSyncStatusEnum $sync_status
 * @property Carbon|null $token_expires_at
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $last_encrypted_at
 *
 * @mixin IdeHelperUserCalendarIntegration
 */
#[UseFactory(UserCalendarIntegrationFactory::class)]
#[Fillable([
    'user_id',
    'provider',
    'client_id',
    'client_secret',
    'access_token',
    'calendar_id',
    'calendar_name',
    'refresh_token',
    'token_expires_at',
    'needs_reauth',
    'sync_status',
    'last_error_message',
    'last_synced_at',
    'last_encrypted_at',
])]
class UserCalendarIntegration extends Model
{
    /** @use HasFactory<UserCalendarIntegrationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at !== null && $this->token_expires_at->isPast();
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'provider'          => CalendarProviderEnum::class,
            'sync_status'       => CalendarSyncStatusEnum::class,
            'client_id'         => EncryptedCalendarCredential::class,
            'client_secret'     => EncryptedCalendarCredential::class,
            'access_token'      => EncryptedCalendarCredential::class,
            'refresh_token'     => EncryptedCalendarCredential::class,
            'token_expires_at'  => 'datetime',
            'needs_reauth'      => 'boolean',
            'last_synced_at'    => 'datetime',
            'last_encrypted_at' => 'datetime',
        ];
    }
}
