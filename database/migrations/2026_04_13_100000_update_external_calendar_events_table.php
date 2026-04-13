<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_calendar_events', function (Blueprint $table): void {
            $table->string('external_event_id')->nullable()->change();
            $table->string('sync_status')->nullable()->after('external_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('external_calendar_events', function (Blueprint $table): void {
            $table->dropColumn('sync_status');
            $table->string('external_event_id')->nullable(false)->change();
        });
    }
};
