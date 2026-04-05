<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_event_external_ids', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('external_event_id');
            $table->timestamps();

            $table->unique(['calendar_event_id', 'user_id', 'provider']);
            $table->index(['calendar_event_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_external_ids');
    }
};
