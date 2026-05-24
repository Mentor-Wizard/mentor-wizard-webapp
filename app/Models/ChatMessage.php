<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Policies\ChatMessagesPolicy;
use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @mixin IdeHelperChatMessage
 */
#[UseFactory(ChatMessageFactory::class)]
#[UsePolicy(ChatMessagesPolicy::class)]
#[Fillable([
    'chat_id',
    'user_id',
    'message',
    'is_read',
])]
class ChatMessage extends Model implements HasMedia
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory;

    use InteractsWithMedia;

    /**
     * @return BelongsTo<Chat, $this>
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('files')
            ->useDisk('public');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'message'    => 'string',
            'is_read'    => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
