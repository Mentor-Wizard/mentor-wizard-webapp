<?php

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
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('unique_id');
            $table->string('title');
            $table->string("status")->nullable();
            $table->dateTime("start_date_time");
            $table->integer("duration")->nullable();
            $table->string("type");
            $table->string("web_link");
            $table->text("description")->nullable();
            $table->foreignId('mentor_program_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
