<?php

declare(strict_types=1);

use App\Enums\CalendarEventStatusEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\MentorSession;
use App\Models\Payment;
use App\Models\User;
use App\Policies\PaymentPolicy;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

mutates(PaymentPolicy::class);

describe('PaymentPolicy (Unit)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->policy = new PaymentPolicy;
    });

    describe('initiate', function (): void {
        it('allows the menti to initiate payment when session calendar event is CONFIRMED', function (): void {
            $menti = User::factory()->create();
            $session = MentorSession::factory()->create(['menti_id' => $menti->getKey()]);
            CalendarEvent::factory()->create([
                'mentor_session_id' => $session->getKey(),
                'status'            => CalendarEventStatusEnum::CONFIRMED,
            ]);

            expect($this->policy->initiate($menti, $session))->toBeTrue();
        });

        it('allows the menti to initiate payment when session calendar event is PENDING_PAYMENT', function (): void {
            $menti = User::factory()->create();
            $session = MentorSession::factory()->create(['menti_id' => $menti->getKey()]);
            CalendarEvent::factory()->create([
                'mentor_session_id' => $session->getKey(),
                'status'            => CalendarEventStatusEnum::PENDING_PAYMENT,
            ]);

            expect($this->policy->initiate($menti, $session))->toBeTrue();
        });

        it('denies initiate when user is not the session menti', function (): void {
            $menti = User::factory()->create();
            $otherUser = User::factory()->create();
            $session = MentorSession::factory()->create(['menti_id' => $menti->getKey()]);
            CalendarEvent::factory()->create([
                'mentor_session_id' => $session->getKey(),
                'status'            => CalendarEventStatusEnum::CONFIRMED,
            ]);

            expect($this->policy->initiate($otherUser, $session))->toBeFalse();
        });

        it('denies initiate when calendar event status is PENDING_MENTOR_CONFIRMATION', function (): void {
            $menti = User::factory()->create();
            $session = MentorSession::factory()->create(['menti_id' => $menti->getKey()]);
            CalendarEvent::factory()->create([
                'mentor_session_id' => $session->getKey(),
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            ]);

            expect($this->policy->initiate($menti, $session))->toBeFalse();
        });

        it('denies initiate when calendar event is cancelled', function (): void {
            $menti = User::factory()->create();
            $session = MentorSession::factory()->create(['menti_id' => $menti->getKey()]);
            CalendarEvent::factory()->create([
                'mentor_session_id' => $session->getKey(),
                'status'            => CalendarEventStatusEnum::CANCELLED,
            ]);

            expect($this->policy->initiate($menti, $session))->toBeFalse();
        });

        it('denies initiate when session has no calendar event', function (): void {
            $menti = User::factory()->create();
            $session = MentorSession::factory()->create(['menti_id' => $menti->getKey()]);

            expect($this->policy->initiate($menti, $session))->toBeFalse();
        });

        it('allows any non-mentor user to initiate payment for a MentorProgram', function (): void {
            $mentor = User::factory()->create();
            $mentor->assignRole(RoleEnum::MENTOR->value);

            $buyer = User::factory()->create();
            $program = MentorProgram::factory()->create(['mentor_id' => $mentor->getKey()]);

            expect($this->policy->initiate($buyer, $program))->toBeTrue();
        });

        it('denies the mentor from initiating payment for their own MentorProgram', function (): void {
            $mentor = User::factory()->create();
            $mentor->assignRole(RoleEnum::MENTOR->value);

            $program = MentorProgram::factory()->create(['mentor_id' => $mentor->getKey()]);

            expect($this->policy->initiate($mentor, $program))->toBeFalse();
        });
    });

    describe('view', function (): void {
        it('allows the menti of the session to view the payment', function (): void {
            $menti = User::factory()->create();
            $session = MentorSession::factory()->create(['menti_id' => $menti->getKey()]);
            $payment = Payment::factory()->create([
                'payable_type' => MentorSession::class,
                'payable_id'   => $session->getKey(),
            ]);

            expect($this->policy->view($menti, $payment))->toBeTrue();
        });

        it('allows the mentor of the session to view the payment', function (): void {
            $mentor = User::factory()->create();
            $session = MentorSession::factory()->create(['mentor_id' => $mentor->getKey()]);
            $payment = Payment::factory()->create([
                'payable_type' => MentorSession::class,
                'payable_id'   => $session->getKey(),
            ]);

            expect($this->policy->view($mentor, $payment))->toBeTrue();
        });

        it('denies view for unrelated users', function (): void {
            $stranger = User::factory()->create();
            $session = MentorSession::factory()->create();
            $payment = Payment::factory()->create([
                'payable_type' => MentorSession::class,
                'payable_id'   => $session->getKey(),
            ]);

            expect($this->policy->view($stranger, $payment))->toBeFalse();
        });
    });

    describe('refund', function (): void {
        it('allows admin to refund', function (): void {
            $admin = User::factory()->create();
            $admin->assignRole(RoleEnum::ADMIN->value);

            expect($this->policy->refund($admin))->toBeTrue();
        });

        it('allows super admin to refund', function (): void {
            $superAdmin = User::factory()->create();
            $superAdmin->assignRole(RoleEnum::SUPER_ADMIN->value);

            expect($this->policy->refund($superAdmin))->toBeTrue();
        });

        it('denies refund for regular user', function (): void {
            $user = User::factory()->create();

            expect($this->policy->refund($user))->toBeFalse();
        });
    });
});
