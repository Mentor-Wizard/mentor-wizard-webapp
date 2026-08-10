<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_programs', function (Blueprint $table): void {
            $table->json('session_type_options')->nullable()->after('session_duration_options');
            $table->boolean('need_confirmation')->default(false)->after('session_type_options');
        });
    }

    public function down(): void
    {
        Schema::table('mentor_programs', function (Blueprint $table): void {
            $table->dropColumn(['session_type_options', 'need_confirmation']);
        });
    }
};
