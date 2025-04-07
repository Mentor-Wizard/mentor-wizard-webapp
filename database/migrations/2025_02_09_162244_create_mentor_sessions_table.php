<?php

declare(strict_types=1);

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
        Schema::create('mentor_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'mentor_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'menti_id')->constrained()->cascadeOnDelete();
            $table->dateTime('date');
            $table->boolean('is_success')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_cancelled')->default(false);
            $table->boolean('is_date_changed')->default(false);
            $table->decimal('cost');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_sessions', function (Blueprint $table) {
            $table->dropForeign(['mentor_id']);
            $table->dropForeign(['menti_id']);
        });
        Schema::dropIfExists('mentor_sessions');
    }
};
