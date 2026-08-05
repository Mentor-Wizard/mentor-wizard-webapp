<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_calendar_event_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('external_calendar_event_id')
                ->nullable()
                ->constrained('external_calendar_events')
                ->nullOnDelete();
            $table->foreignId('calendar_event_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('provider');
            $table->string('type');
            $table->text('message');
            $table->timestamps();

            $table->index(['calendar_event_id', 'type']);
            $table->index('external_calendar_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_calendar_event_logs');
    }
};
