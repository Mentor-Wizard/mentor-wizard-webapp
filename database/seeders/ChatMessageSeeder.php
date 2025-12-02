<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatMessageSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $sender) {
            // We select all other users to leave replies to in the chat
            $receivers = $users->where('id', '!=', $sender->id)->shuffle()->take(5);

            foreach ($receivers as $receiver) {
                for ($i = 0; $i < 5; $i++) {
                    ChatMessage::query()->create([
                        'sender_id'    => $sender->id,
                        'receiver_id'  => $receiver->id,
                        'message'      => fake()->sentence(50),
                        'is_read'      => fake()->boolean(),
                    ]);
                }
            }
        }
    }
}
