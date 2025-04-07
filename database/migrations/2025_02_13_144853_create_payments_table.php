<?php

declare(strict_types=1);

use App\Models\MentorSession;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MentorSession::class, 'mentor_session_id')->constrained()->cascadeOnDelete();
            $table->string('order_reference');
            $table->integer('amount');
            $table->string('currency', 3);
            $table->string('transaction_status');
            $table->string('reason');
            $table->string('reason_code');
            $table->string('payment_system');
            $table->string('card_type');
            $table->string('issue_bank_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['mentor_session_id']);
        });
        Schema::dropIfExists('payments');
    }
};
