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
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status')->nullable();
            $table->dateTime('start_date_time');
            $table->dateTime('end_date_time');
            $table->date('date');
            $table->integer('duration');
            $table->string('type');
            $table->string('web_link')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('mentor_program_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
