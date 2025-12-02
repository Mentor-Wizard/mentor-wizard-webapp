<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperChatMessage
 */
#[UseFactory(ChatMessageFactory::class)]
class ChatMessage extends Model
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'is_read',
    ];

    public function userSender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function userReceiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    protected function casts(): array
    {
        return [
            'message' => 'string',
            'is_read' => 'boolean',
        ];
    }
}
