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
        Schema::whenTableHasColumn('user_profiles', 'whatsapp', function (Blueprint $table): void {
            $table->string('whatsapp', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::whenTableHasColumn('user_profiles', 'whatsapp', function (Blueprint $table): void {
            $table->string('whatsapp', 20)->nullable()->change();
        });
    }
};
