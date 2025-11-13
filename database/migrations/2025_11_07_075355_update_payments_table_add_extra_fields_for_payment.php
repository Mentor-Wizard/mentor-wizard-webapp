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
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['mentor_session_id']);
            $table->unsignedBigInteger('mentor_session_id')->nullable()->change();
            $table->foreign('mentor_session_id')
                ->references('id')
                ->on('mentor_sessions')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->string('reason')->nullable()->change();
            $table->string('reason_code')->nullable()->change();
            $table->string('issue_bank_name')->nullable()->change();

            $table->string('transaction_id')->nullable()->unique()->after('id');
            $table->string('payment_type')->after('transaction_status');
            $table->integer('refund_amount')->nullable()->after('amount');
            $table->timestamp('refunded_at')->nullable()->after('refund_amount');
            $table->string('card_pan', 20)->nullable()->after('card_type');
            $table->string('phone', 20)->nullable()->after('card_pan');
            $table->string('account_number', 50)->nullable()->after('phone');
            $table->string('rectoken')->nullable()->after('account_number');
            $table->boolean('is_regular')->default(false)->after('rectoken');
            $table->unsignedBigInteger('parent_payment_id')->nullable()->after('is_regular');
            $table->json('metadata')->nullable()->after('parent_payment_id');

            $table->foreign('parent_payment_id')
                ->references('id')
                ->on('payments')
                ->restrictOnDelete();

            $table->index('mentor_session_id');
            $table->index('parent_payment_id');
            $table->index('transaction_status');
            $table->index('payment_type');
            $table->index('created_at');
            $table->index('order_reference');
            $table->index('is_regular');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['mentor_session_id']);
            $table->dropForeign(['parent_payment_id']);

            $table->dropIndex(['mentor_session_id']);
            $table->dropIndex(['parent_payment_id']);
            $table->dropIndex(['transaction_status']);
            $table->dropIndex(['payment_type']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['order_reference']);
            $table->dropUnique(['transaction_id']);
            $table->dropIndex(['is_regular']);

            $table->dropColumn([
                'transaction_id',
                'payment_type',
                'refund_amount',
                'refunded_at',
                'card_pan',
                'phone',
                'account_number',
                'rectoken',
                'is_regular',
                'parent_payment_id',
                'metadata',
            ]);

            $table->string('reason')->nullable(false)->change();
            $table->string('reason_code')->nullable(false)->change();
            $table->string('issue_bank_name')->nullable(false)->change();

            $table->unsignedBigInteger('mentor_session_id')
                ->nullable(false)
                ->change();

            $table->foreign('mentor_session_id')
                ->references('id')
                ->on('mentor_sessions')
                ->cascadeOnDelete();
        });
    }
};
