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
        Schema::create('mentor_program_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('mentor_program_id')->constrained('mentor_programs')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_program_blocks', function (Blueprint $table) {
            $table->dropForeign(['mentor_program_id']);
        });
        Schema::dropIfExists('mentor_program_blocks');
    }
};
