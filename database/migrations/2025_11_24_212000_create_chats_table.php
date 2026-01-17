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
        // old structure
        Schema::dropIfExists('chats');

        // new structure
        Schema::create('chats', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'owner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Chat::class, 'companion_chat_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->boolean('mute')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // drop new structure
        Schema::table('chat_messages', function (Blueprint $table): void {
            try {
                $table->dropForeign(['chat_id']);
            } catch (Throwable) {
            }
        });
        Schema::dropIfExists('chats');

        // restore old structure
        Schema::create('chats', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'menti_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'mentor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'coach_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

    }
};
