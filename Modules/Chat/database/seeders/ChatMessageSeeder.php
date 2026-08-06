<?php

declare(strict_types=1);

namespace Modules\Chat\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Chat\Enums\ChatStatusEnum;
use Modules\Chat\Models\Chat;
use Modules\Chat\Models\ChatMessage;

class ChatMessageSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $sender) {
            // We select all other users to leave replies to in the chat
            $receivers = $users->where('id', '!=', $sender->getKey())->shuffle()->take(5);

            foreach ($receivers as $receiver) {
                $this->seedChatBetween($sender, $receiver);
            }
        }
    }

    private function seedChatBetween(User $sender, User $receiver): void
    {
        $chat = Chat::query()->create([
            'name' => fake()->words(3, true),
        ]);

        $chat->users()->attach([
            $sender->getKey()   => ['status' => ChatStatusEnum::ACTIVE->value],
            $receiver->getKey() => ['status' => ChatStatusEnum::ACTIVE->value],
        ]);

        for ($i = 0; $i < 5; $i++) {
            ChatMessage::query()->create([
                'chat_id' => $chat->getKey(),
                'user_id' => $i % 2 === 0 ? $sender->getKey() : $receiver->getKey(),
                'message' => fake()->sentence(50),
                'is_read' => fake()->boolean(),
            ]);
        }
    }
}
