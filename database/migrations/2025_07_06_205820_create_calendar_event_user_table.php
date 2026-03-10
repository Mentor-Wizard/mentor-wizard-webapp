<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
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
        Schema::create('calendar_event_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignIdFor(CalendarEvent::class, 'calendar_event_id')
                ->constrained()->cascadeOnDelete();
            $table->string('role')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->string('colour')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_event_user');
    }
};
