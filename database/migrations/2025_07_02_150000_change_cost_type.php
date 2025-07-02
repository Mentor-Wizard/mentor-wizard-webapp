<?php

declare(strict_types=1);

use App\Models\Currency;
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
        Schema::table('mentor_programs', function (Blueprint $table): void {
            $table->decimal('cost')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_programs', function (Blueprint $table): void {
            $table->float('cost')->change();
        });
    }
};
