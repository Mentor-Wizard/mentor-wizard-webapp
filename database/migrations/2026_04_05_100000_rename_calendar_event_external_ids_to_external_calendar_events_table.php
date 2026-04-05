<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('calendar_event_external_ids', 'external_calendar_events');
    }

    public function down(): void
    {
        Schema::rename('external_calendar_events', 'calendar_event_external_ids');
    }
};
