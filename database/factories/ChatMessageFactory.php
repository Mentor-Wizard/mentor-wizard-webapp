<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    #[Override]
    protected $model = ChatMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chat_id'   => Chat::factory(),
            'user_id'   => User::factory(),
            'message'   => fake()->sentence(10),
            'is_read'   => fake()->boolean(),
        ];
    }
}
