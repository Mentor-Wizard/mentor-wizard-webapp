<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['mentor_session_id']);
            $table->dropColumn('mentor_session_id');

            $table->string('payable_type')->after('id');
            $table->unsignedBigInteger('payable_id')->after('payable_type');
            $table->index(['payable_type', 'payable_id']);

            $table->string('reason')->nullable()->change();
            $table->string('reason_code')->nullable()->change();
            $table->string('payment_system')->nullable()->change();
            $table->string('card_type')->nullable()->change();
            $table->string('issue_bank_name')->nullable()->change();

            $table->integer('fee_amount')->nullable()->after('amount');
            $table->decimal('fee_percentage', 5, 4)->nullable()->after('fee_amount');
            $table->integer('net_amount')->nullable()->after('fee_percentage');

            $table->timestamp('refunded_at')->nullable()->after('issue_bank_name');
            $table->integer('refund_amount')->nullable()->after('refunded_at');

            $table->index('transaction_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['payable_type', 'payable_id']);
            $table->dropIndex(['transaction_status']);
            $table->dropIndex(['created_at']);

            $table->dropColumn([
                'payable_type',
                'payable_id',
                'fee_amount',
                'fee_percentage',
                'net_amount',
                'refunded_at',
                'refund_amount',
            ]);

            $table->foreignId('mentor_session_id')->constrained()->cascadeOnDelete();
        });
    }
};
