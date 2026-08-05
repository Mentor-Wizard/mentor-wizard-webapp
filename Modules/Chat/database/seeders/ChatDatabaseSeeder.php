<?php

declare(strict_types=1);

namespace Modules\Chat\Database\Seeders;

use Illuminate\Database\Seeder;

class ChatDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ChatMessageSeeder::class,
        ]);
    }
}
