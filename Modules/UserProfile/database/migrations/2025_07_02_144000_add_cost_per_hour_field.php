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
        Schema::whenTableDoesntHaveColumn('user_profiles', 'cost_per_hour', function (Blueprint $table): void {
            $table->decimal('cost_per_hour')->nullable()->after('description');
            $table->foreignIdFor(Currency::class)->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::whenTableHasColumn('user_profiles', 'cost_per_hour', function (Blueprint $table): void {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
            $table->dropColumn('cost_per_hour');
        });
    }
};
