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
        Schema::create('mentor_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('menti_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment')->nullable();
            $table->tinyInteger('rating')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_reviews', function (Blueprint $table) {
            $table->dropForeign(['mentor_id']);
            $table->dropForeign(['menti_id']);
        });
        Schema::dropIfExists('mentor_reviews');
    }
};
