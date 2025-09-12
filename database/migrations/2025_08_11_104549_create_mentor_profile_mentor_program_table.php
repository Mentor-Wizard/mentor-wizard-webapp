<?php

declare(strict_types=1);

use App\Models\MentorProfile;
use App\Models\MentorProgram;
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
        Schema::create('mentor_profile_mentor_program', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(MentorProfile::class, 'mentor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(MentorProgram::class, 'mentor_program_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_profile_mentor_program', function (Blueprint $table): void {
            $table->dropForeign(['mentor_profile_id']);
            $table->dropForeign(['mentor_program_id']);
        });
        Schema::dropIfExists('mentor_profile_mentor_program');
    }
};
