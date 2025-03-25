<?php

declare(strict_types=1);

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
        Schema::create('mentor_program_block_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menti_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentor_program_block_id')->constrained('mentor_program_blocks')->cascadeOnDelete();
            $table->boolean('is_completed')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_program_block_progresses', function (Blueprint $table) {
            $table->dropForeign(['menti_id']);
            $table->dropForeign(['mentor_program_block_id']);
        });
        Schema::dropIfExists('mentor_program_block_progresses');
    }
};
