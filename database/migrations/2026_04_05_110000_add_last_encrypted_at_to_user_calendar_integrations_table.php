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
            $table->timestamp('last_encrypted_at')->nullable()->after('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_integrations', function (Blueprint $table): void {
            $table->dropColumn('last_encrypted_at');
        });
    }
};
