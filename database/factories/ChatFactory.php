<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ChatStatusEnum;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatFactory extends Factory
{
    protected $model = ChatMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id'     => User::factory(),
            'companion_id' => User::factory(),
            'status'       => ChatStatusEnum::ACTIVE,
        ];
    }
}
