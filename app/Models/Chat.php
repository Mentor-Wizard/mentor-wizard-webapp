<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChatStatusEnum;
use Database\Factories\ChatFactory;
use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'owner_id',
        'companion_chat_id',
        'status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function companionChat(): BelongsTo
    {
        return $this->belongsTo(self::class, 'companion_chat_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status'    => ChatStatusEnum::class,
        ];
    }
}
