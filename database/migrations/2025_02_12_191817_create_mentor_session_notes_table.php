<?php

declare(strict_types=1);

use App\Models\MentorSession;
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
        Schema::create('mentor_session_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MentorSession::class)->constrained()->cascadeOnDelete();
            $table->text('notes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_session_notes', function (Blueprint $table) {
            $table->dropForeign(['mentor_session_id']);
        });
        Schema::dropIfExists('mentor_session_notes');
    }
};
