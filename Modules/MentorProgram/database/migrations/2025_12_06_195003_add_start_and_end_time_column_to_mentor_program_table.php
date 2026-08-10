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
        Schema::table('mentor_programs', static function (Blueprint $table): void {
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_programs', function (Blueprint $table): void {
            $table->dropColumn('start_time');
            $table->dropColumn('end_time');
        });
    }
};
