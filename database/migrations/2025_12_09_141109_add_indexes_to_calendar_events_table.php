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
        Schema::table('calendar_events', function (Blueprint $table): void {
            // Index for filtering by start date/time
            $table->index('start_date_time');

            // Index for filtering by date
            $table->index('date');

            // Composite index for whereBetween queries on date ranges
            $table->index(['start_date_time', 'end_date_time']);

            // Index for filtering by mentor program
            $table->index('mentor_program_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropIndex(['calendar_events_start_date_time_index']);
            $table->dropIndex(['calendar_events_date_index']);
            $table->dropIndex(['calendar_events_start_date_time_end_date_time_index']);
            $table->dropIndex(['calendar_events_mentor_program_id_index']);
        });
    }
};
