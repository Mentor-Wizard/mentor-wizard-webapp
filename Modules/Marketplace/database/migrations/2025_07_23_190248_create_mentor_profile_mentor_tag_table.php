<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Marketplace\Models\MentorProfile;
use Modules\Marketplace\Models\MentorTag;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mentor_profile_mentor_tag', function (Blueprint $table): void {
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
        Schema::table('mentor_profile_mentor_tag', function (Blueprint $table): void {
            $table->dropForeign(['mentor_profile_id']);
            $table->dropForeign(['mentor_tag_id']);
        });
        Schema::dropIfExists('mentor_profile_mentor_tag');
    }
};
