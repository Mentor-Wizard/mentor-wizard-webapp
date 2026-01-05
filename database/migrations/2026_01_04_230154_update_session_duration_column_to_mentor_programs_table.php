<?php

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
        Schema::table('mentor_programs', function (Blueprint $table) {
                $table->integer('session_duration')->default(60)->change();;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_programs', function (Blueprint $table) {
            $table->integer('session_duration')->nullable()->change();
        });
    }
};
