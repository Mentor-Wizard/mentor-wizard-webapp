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
            $table->text('access_token')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_calendar_integrations', function (Blueprint $table): void {
            $table->text('access_token')->nullable(false)->change();
        });
    }
};
