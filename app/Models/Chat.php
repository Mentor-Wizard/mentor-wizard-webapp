<?php

declare(strict_types=1);

namespace App\Models;

use App\Policies\ChatPolicy;
use Database\Factories\ChatFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Override;

#[UseFactory(ChatFactory::class)]
#[UsePolicy(ChatPolicy::class)]
/**
 * @mixin IdeHelperChat
 */
class Chat extends Model implements HasMedia
{
    /** @use HasFactory<ChatFactory> */
    use HasFactory;

    use InteractsWithMedia;

    protected $fillable = [
        'name',
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_users')
            ->withPivot(['status', 'is_muted'])
            ->withTimestamps();
    }

    /**
     * @return User|null
     *                   getting a chat partner
     */
    public function companion(User $user): ?User
    {
        return $this->users()
            ->wherePivot('user_id', '!=', $user->getKey())
            ->first();
    }

    /**
     * @return HasMany<ChatMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'mentor_id' => 'int',
            'menti_id'  => 'int',
            'coach_id'  => 'int',
        ];
    }
}
