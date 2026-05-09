<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_mentor_profile', function (Blueprint $table): void {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentor_profile_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'mentor_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_mentor_profile');
    }
};
