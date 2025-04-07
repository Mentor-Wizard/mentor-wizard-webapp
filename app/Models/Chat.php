<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\ChatObserver;
use Database\Factories\ChatFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperChat
 */
#[ObservedBy(ChatObserver::class)]
#[UseFactory(ChatFactory::class)]
class Chat extends Model
{
    /** @use HasFactory<ChatFactory> */
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'menti_id',
        'coach_id',
    ];

    protected function casts(): array
    {
        return [
            'mentor_id' => 'int',
            'menti_id' => 'int',
            'coach_id' => 'int',
        ];
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function menti(): BelongsTo
    {
        return $this->belongsTo(User::class, 'menti_id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
