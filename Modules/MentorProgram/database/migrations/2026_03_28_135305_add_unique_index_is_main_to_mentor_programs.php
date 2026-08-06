<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE UNIQUE INDEX mentor_programs_one_main_per_mentor
            ON mentor_programs (mentor_id)
            WHERE is_main = true
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS mentor_programs_one_main_per_mentor');
    }
};
