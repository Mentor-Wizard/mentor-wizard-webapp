<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_calendar_integrations', function (Blueprint $table): void {
            $table->string('calendar_id')->nullable()->after('access_token');
            $table->string('calendar_name')->nullable()->after('calendar_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_integrations', function (Blueprint $table): void {
            $table->dropColumn(['calendar_id', 'calendar_name']);
        });
    }
};
