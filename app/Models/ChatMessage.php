<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @mixin IdeHelperChatMessage
 */
#[UseFactory(ChatMessageFactory::class)]
class ChatMessage extends Model implements HasMedia
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory;

    use InteractsWithMedia;

    protected $fillable = [
        'chat_id',
        'user_id',
        'message',
        'is_read',
    ];

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
    protected function casts(): array
    {
        return [
            'message'    => 'string',
            'is_read'    => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
