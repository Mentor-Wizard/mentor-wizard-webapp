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
        Schema::table('mentor_profiles', function (Blueprint $table): void {
            $table->index('experience_started_at');
            $table->index('rate');
            $table->index('currency_id');
        });

        Schema::table('mentor_tags', function (Blueprint $table): void {
            $table->index(['type', 'tag']);
        });

        Schema::table('mentor_profile_mentor_tag', function (Blueprint $table): void {
            $table->index('mentor_profile_id');
            $table->index('mentor_tag_id');
        });

        Schema::table('mentor_reviews', function (Blueprint $table): void {
            $table->index(['mentor_id', 'rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_profiles', function (Blueprint $table): void {
            $table->dropIndex(['experience_started_at']);
            $table->dropIndex(['rate']);
            $table->dropIndex(['currency_id']);
        });

        Schema::table('mentor_tags', function (Blueprint $table): void {
            $table->dropIndex(['type', 'tag']);
        });

        Schema::table('mentor_profile_mentor_tag', function (Blueprint $table): void {
            $table->dropIndex(['mentor_profile_id']);
            $table->dropIndex(['mentor_tag_id']);
        });

        Schema::table('mentor_reviews', function (Blueprint $table): void {
            $table->dropIndex(['mentor_id', 'rating']);
        });
    }
};
