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
        Schema::dropIfExists('attachments');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('hash_name')->comment('file name on disk');
            $table->string('file_name')->comment('real file name');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->timestamps();
        });
    }
};
