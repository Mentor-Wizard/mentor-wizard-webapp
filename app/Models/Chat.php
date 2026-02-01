<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ChatFactory;
use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[UseFactory(ChatFactory::class)]
/**
 * @mixin IdeHelperChat
 */
class Chat extends Model implements HasMedia
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory;

    use InteractsWithMedia;

    protected $fillable = [
        'name',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_users')
            ->withPivot(['status', 'is_muted'])
            ->withTimestamps();
    }

    /**
     * getting a chat partner
     */
    public function companion(User $user): ?User
    {
        return $this->users()
            ->wherePivot('user_id', '!=', $user->id)
            ->first();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
