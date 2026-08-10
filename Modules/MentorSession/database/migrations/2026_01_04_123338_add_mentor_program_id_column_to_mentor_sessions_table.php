<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\MentorProgram\Models\MentorProgram;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mentor_sessions', function (Blueprint $table): void {
            $table->foreignIdFor(MentorProgram::class, 'mentor_program_id')
                ->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mentor_sessions', function (Blueprint $table): void {
            $table->dropColumn('mentor_program_id');
        });
    }
};
