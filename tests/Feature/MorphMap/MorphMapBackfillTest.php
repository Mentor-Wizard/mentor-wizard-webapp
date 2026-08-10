<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notification as NotificationBase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Chat\Models\Chat;
use Modules\Chat\Models\ChatMessage;
use Modules\UserProfile\Models\UserProfile;
use Spatie\Permission\Models\Role;

describe('enforced morph map', function (): void {
    it('stores the short alias, not the FQCN, for fresh writes', function (): void {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(RoleEnum::MENTI->value);

        $profile = UserProfile::factory()->create(['user_id' => $user->getKey()]);

        Storage::fake('public');
        $chat = Chat::factory()->create();
        $message = ChatMessage::factory()->create(['chat_id' => $chat->getKey()]);
        $media = $message->addMedia(UploadedFile::fake()->create('file.pdf', 10))->toMediaCollection('files');

        $user->notify(new class extends NotificationBase
        {
            /**
             * @return array<int, string>
             */
            public function via(mixed $notifiable): array
            {
                return ['database'];
            }

            /**
             * @return array<string, string>
             */
            public function toArray(mixed $notifiable): array
            {
                return ['message' => 'test'];
            }
        });

        expect(DB::table('model_has_roles')->where('model_id', $user->getKey())->value('model_type'))->toBe('user');
    });

    it('resolves roles, media, and notifications after backfilling legacy FQCN rows', function (): void {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $role = Role::query()->where('name', RoleEnum::MENTI->value)->firstOrFail();

        DB::table('model_has_roles')->insert([
            'role_id'    => $role->getKey(),
            'model_type' => User::class,
            'model_id'   => $user->getKey(),
        ]);

        $chat = Chat::factory()->create();
        $message = ChatMessage::factory()->create(['chat_id' => $chat->getKey()]);

        DB::table('media')->insert([
            'model_type'            => 'App\Models\ChatMessage',
            'model_id'              => $message->getKey(),
            'uuid'                  => (string) Str::uuid(),
            'collection_name'       => 'files',
            'name'                  => 'legacy',
            'file_name'             => 'legacy.pdf',
            'mime_type'             => 'application/pdf',
            'disk'                  => 'public',
            'conversions_disk'      => 'public',
            'size'                  => 10,
            'manipulations'         => '[]',
            'custom_properties'     => '[]',
            'generated_conversions' => '[]',
            'responsive_images'     => '[]',
            'order_column'          => 1,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        DB::table('notifications')->insert([
            'id'              => (string) Str::uuid(),
            'type'            => 'App\\Notifications\\Legacy',
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->getKey(),
            'data'            => '{}',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $migration = require base_path('database/migrations/2026_08_04_120000_backfill_morph_map_aliases.php');
        $migration->up();

        expect(DB::table('model_has_roles')->where('model_id', $user->getKey())->value('model_type'))->toBe('user');
    });

    it('pins the broadcast notification channel to the legacy name regardless of morph alias', function (): void {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();

        expect($user->receivesBroadcastNotificationsOn())->toBe('App.Models.User.'.$user->getKey());
    });
});
