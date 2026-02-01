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
        Schema::table('chats', function (Blueprint $table): void {
            // drop foreign keys
            $table->dropForeign(['menti_id']);
            $table->dropForeign(['mentor_id']);
            $table->dropForeign(['coach_id']);

            // drop columns
            $table->dropColumn([
                'menti_id',
                'mentor_id',
                'coach_id',
            ]);

            // add new column
            $table->string('name')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table): void {
            // remove new column
            $table->dropColumn('name');

            // restore old columns
            $table->foreignId('menti_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('mentor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};
