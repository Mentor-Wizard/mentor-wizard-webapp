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
            $table->text('client_id')->nullable()->after('provider');
            $table->text('client_secret')->nullable()->after('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_integrations', function (Blueprint $table): void {
            $table->dropColumn(['client_id', 'client_secret']);
        });
    }
};
