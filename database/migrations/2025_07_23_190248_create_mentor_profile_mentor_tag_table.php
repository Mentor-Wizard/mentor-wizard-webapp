<?php

use App\Models\MentorProfile;
use App\Models\MentorTag;
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
        Schema::create('mentor_profile_mentor_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MentorProfile::class, 'mentor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(MentorTag::class, 'mentor_tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentor_profile_mentor_tag');
    }
};
