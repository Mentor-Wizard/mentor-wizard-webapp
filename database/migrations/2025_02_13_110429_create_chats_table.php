<?php

declare(strict_types=1);

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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'menti_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'mentor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'coach_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign('mentor_id');
            $table->dropForeign('menti_id');
            $table->dropForeign('coach_id');
        });
        Schema::dropIfExists('chats');
    }
};
