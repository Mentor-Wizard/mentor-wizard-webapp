<?php

declare(strict_types=1);

use App\Models\Chat;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // we are deleting old tables. The chat structure has been changed
        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->dropForeign(['chat_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('chat_messages');

        Schema::table('chats', function (Blueprint $table): void {
            $table->dropForeign(['mentor_id']);
            $table->dropForeign(['menti_id']);
            $table->dropForeign(['coach_id']);
        });
        Schema::dropIfExists('chats');

        // updated chat structure
        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['sender_id', 'receiver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('chat_messages', function (Blueprint $table): void {
            $table->dropForeign(['sender_id']);
            $table->dropForeign(['receiver_id']);
        });
        Schema::dropIfExists('chat_messages');

        Schema::create('chats', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'menti_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'mentor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'coach_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Chat::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }
};
