<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ChatStatusEnum;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatMessageSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $partners = $users->where('id', '!=', $user->id)->shuffle()->take(5);

            foreach ($partners as $partner) {
                $chat = Chat::query()->create(['name' => sprintf('%s & %s', $user->username, $partner->username)]);

                $chat->users()->attach([
                    $user->getKey()    => ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false],
                    $partner->getKey() => ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false],
                ]);

                $participants = [$user->getKey(), $partner->getKey()];

                for ($i = 0; $i < 5; $i++) {
                    ChatMessage::query()->create([
                        'chat_id' => $chat->getKey(),
                        'user_id' => $participants[$i % 2],
                        'message' => fake()->sentence(50),
                        'is_read' => fake()->boolean(),
                    ]);
                }
            }
        }
    }
}
