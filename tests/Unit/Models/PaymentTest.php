<?php

declare(strict_types=1);

use App\Models\MentorSession;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;

covers(Payment::class);

describe('Payment Model', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        $this->mentor = User::factory()->create();
        $this->menti = User::factory()->create();

        $this->mentorSession = MentorSession::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
        ]);
    });

    it('can create session with basic attributes with relations', function () {
        $payment = Payment::factory()->create([
            'mentor_session_id' => $this->mentorSession->getKey(),
            'order_reference' => '111hjjj',
            'amount' => 1,
            'currency' => 'UAH',
            'transaction_status' => 'success',
            'reason' => 'pay',
            'reason_code' => '001',
            'payment_system' => 'novapay',
            'card_type' => 'visa',
            'issue_bank_name' => 'bank',
        ]);

        expect($payment)->toBeInstanceOf(Payment::class)
            ->and($payment->mentor_session_id)->toBe($this->mentorSession->getKey())
            ->and($payment->order_reference)->toBe('111hjjj')
            ->and($payment->amount)->toBe(1)
            ->and($payment->currency)->toBe('UAH')
            ->and($payment->transaction_status)->toBe('success')
            ->and($payment->reason)->toBe('pay')
            ->and($payment->reason_code)->toBe('001')
            ->and($payment->payment_system)->toBe('novapay')
            ->and($payment->card_type)->toBe('visa')
            ->and($payment->issue_bank_name)->toBe('bank')
            ->and($payment->mentorSession)->toBeInstanceOf(MentorSession::class)
            ->and($payment->mentorSession->getKey())->toBe($this->mentorSession->getKey());
    });

    it('can create session with basic attributes and casts are correct', function () {
        $payment = Payment::factory()->create([
            'mentor_session_id' => $this->mentorSession->getKey(),
            'order_reference' => '111hjjj',
            'amount' => 2,
            'currency' => 'UAH',
            'transaction_status' => 'success',
            'reason' => 'pay',
            'reason_code' => '00',
            'payment_system' => 'novapay',
            'card_type' => 'visa',
            'issue_bank_name' => 'bank',
        ]);

        expect($payment)->toBeInstanceOf(Payment::class)
            ->and($payment->mentor_session_id)->toBeInt()
            ->and($payment->order_reference)->toBeString()
            ->and($payment->amount)->toBeInt()
            ->and($payment->currency)->toBeString()
            ->and($payment->transaction_status)->toBeString()
            ->and($payment->reason)->toBeString()
            ->and($payment->reason_code)->toBeString()
            ->and($payment->payment_system)->toBeString()
            ->and($payment->card_type)->toBeString()
            ->and($payment->issue_bank_name)->toBeString();
    });

    it('cascades on mentor session deletion', function () {
        $payment = Payment::factory()->create([
            'mentor_session_id' => $this->mentorSession->getKey(),
            'order_reference' => '111hjjj',
            'amount' => 1,
            'currency' => 'UAH',
            'transaction_status' => 'success',
            'reason' => 'pay',
            'reason_code' => '01',
            'payment_system' => 'novapay',
            'card_type' => 'visa',
            'issue_bank_name' => 'bank',
        ]);

        $this->mentorSession->delete();

        $this->assertDatabaseMissing('payments', [
            'id' => $payment->getKey(),
        ]);
    });

    it('has correctly defined fillable attributes', function () {
        $payment = new Payment;

        expect($payment->getFillable())->toBe([
            'mentor_session_id',
            'order_reference',
            'amount',
            'currency',
            'transaction_status',
            'reason',
            'reason_code',
            'payment_system',
            'card_type',
            'issue_bank_name',
        ]);
    });

    it('throws an exception when mass assigning unauthorized attributes', function () {
        $payment = new Payment;

        $payment->fill([
            'mentor_session_id' => $this->mentorSession->getKey(),
            'order_reference' => '222eeee',
            'amount' => 1,
            'currency' => 'USD',
            'transaction_status' => 'success',
            'reason' => 'pay',
            'reason_code' => '200',
            'payment_system' => 'novapay',
            'card_type' => 'visa',
            'issue_bank_name' => 'bank',
            'extra_field' => 'unexpected',
        ]);
    })->throws(MassAssignmentException::class);

    it('has a precisely defined cast configuration', function () {
        $reflectionMethod = new ReflectionMethod(Payment::class, 'casts');
        $payment = new Payment;
        $casts = $reflectionMethod->invoke($payment);

        expect($casts)->toBe([
            'mentor_session_id' => 'int',
            'order_reference' => 'string',
            'amount' => 'int',
            'currency' => 'string',
            'transaction_status' => 'string',
            'reason' => 'string',
            'reason_code' => 'string',
            'payment_system' => 'string',
            'card_type' => 'string',
            'issue_bank_name' => 'string',
        ]);
    });

    it('has precisely defined fillable attributes and mass assignment works correctly', function () {
        $data = [
            'mentor_session_id' => $this->mentorSession->getKey(),
            'order_reference' => 'ttt333',
            'amount' => 1,
            'currency' => 'EUR',
            'transaction_status' => 'success',
            'reason' => 'pay-pay',
            'reason_code' => '05',
            'payment_system' => 'paypal',
            'card_type' => 'masterecard',
            'issue_bank_name' => 'bankname',
        ];

        $payment = Payment::factory()->create($data);

        expect($payment->mentor_session_id)->toBe($this->mentorSession->getKey())
            ->and($payment->order_reference)->toBeString()
            ->and($payment->amount)->toBeInt()
            ->and($payment->currency)->toBeString()
            ->and($payment->transaction_status)->toBeString()
            ->and($payment->reason)->toBeString()
            ->and($payment->reason_code)->toBeString()
            ->and($payment->payment_system)->toBeString()
            ->and($payment->card_type)->toBeString()
            ->and($payment->issue_bank_name)->toBeString()
            ->and($payment->mentorSession)->toBeInstanceOf(MentorSession::class)
            ->and($payment->mentorSession->getKey())->toBe($this->mentorSession->getKey());

        MentorSession::create(array_merge($data, ['extra_field' => 'test']));
    })->throws(MassAssignmentException::class);
});
