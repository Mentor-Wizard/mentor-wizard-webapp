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
        Schema::create('mentor_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('title', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('rate');
            $table->foreignIdFor(Currency::class)->constrained();
            $table->date('experience_started_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_profiles', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['currency_id']);
        });
        Schema::dropIfExists('mentor_profiles');
    }
};
