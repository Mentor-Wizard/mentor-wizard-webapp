<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\User;
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
        Schema::create('mentor_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'mentor_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('slug')->nullable();
            $table->text('description')->nullable();
            $table->float('cost');
            $table->foreignIdFor(Currency::class)->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_programs', function (Blueprint $table) {
            $table->dropForeign(['mentor_id']);
            $table->dropForeign(['currency_id']);
        });
        Schema::dropIfExists('mentor_programs');
    }
};
