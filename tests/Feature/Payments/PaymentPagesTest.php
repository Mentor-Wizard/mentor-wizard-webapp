<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;
use App\Models\MentorSession;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->withoutVite();
});

describe('GET /payments/success', function (): void {
    it('requires authentication', function (): void {
        get(route('payments.success'))
            ->assertRedirect(route('login'));
    });

    it('renders the Success page with null payment when orderReference is missing', function (): void {
        $user = User::factory()->create();

        actingAs($user)
            ->get(route('payments.success'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Payments/Success')
                ->where('payment', null)
                ->where('payable', null)
            );
    });

    it('renders the Success page with null payment when orderReference does not match', function (): void {
        $user = User::factory()->create();

        actingAs($user)
            ->get(route('payments.success').'?orderReference=non-existent-ref')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Payments/Success')
                ->where('payment', null)
                ->where('payable', null)
            );
    });

    it('renders the Success page with payment data when orderReference matches an approved payment', function (): void {
        $user = User::factory()->create();
        $session = MentorSession::factory()->create(['menti_id' => $user->getKey()]);
        $payment = Payment::factory()->approved()->create([
            'payable_type'    => MentorSession::class,
            'payable_id'      => $session->getKey(),
            'order_reference' => 'ref-success-approved',
        ]);

        actingAs($user)
            ->get(route('payments.success').'?orderReference=ref-success-approved')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Payments/Success')
                ->where('payment.order_reference', 'ref-success-approved')
                ->where('payment.transaction_status', PaymentStatusEnum::APPROVED->value)
                ->where('payable.type', 'MentorSession')
                ->where('payable.id', $session->getKey())
            );
    });
});

describe('GET /payments/failure', function (): void {
    it('requires authentication', function (): void {
        get(route('payments.failure'))
            ->assertRedirect(route('login'));
    });

    it('renders the Failure page with null payment when orderReference is missing', function (): void {
        $user = User::factory()->create();

        actingAs($user)
            ->get(route('payments.failure'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Payments/Failure')
                ->where('payment', null)
                ->where('payable', null)
                ->where('retry_url', null)
            );
    });

    it('renders the Failure page with null payment when orderReference does not match', function (): void {
        $user = User::factory()->create();

        actingAs($user)
            ->get(route('payments.failure').'?orderReference=non-existent-ref')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Payments/Failure')
                ->where('payment', null)
                ->where('retry_url', null)
            );
    });

    it('renders the Failure page with payment data and retry_url when orderReference matches a declined payment', function (): void {
        $user = User::factory()->create();
        $session = MentorSession::factory()->create(['menti_id' => $user->getKey()]);
        $payment = Payment::factory()->declined()->create([
            'payable_type'    => MentorSession::class,
            'payable_id'      => $session->getKey(),
            'order_reference' => 'ref-failure-declined',
        ]);

        actingAs($user)
            ->get(route('payments.failure').'?orderReference=ref-failure-declined')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Payments/Failure')
                ->where('payment.order_reference', 'ref-failure-declined')
                ->where('payment.transaction_status', PaymentStatusEnum::DECLINED->value)
                ->where('payable.type', 'MentorSession')
                ->whereNot('retry_url', null)
            );
    });
});

describe('GET /payments/history', function (): void {
    it('requires authentication', function (): void {
        get(route('payments.history'))
            ->assertRedirect(route('login'));
    });

    it('returns empty data when user has no payments', function (): void {
        $user = User::factory()->create();

        actingAs($user)
            ->get(route('payments.history'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Payments/History')
                ->has('payments.data', 0)
                ->where('payments.meta.total', 0)
                ->has('filters')
            );
    });

    it('passes filter values back to the view', function (): void {
        $user = User::factory()->create();

        actingAs($user)
            ->get(route('payments.history').'?status=approved&from=2026-01-01&to=2026-12-31')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.status', 'approved')
                ->where('filters.from', '2026-01-01')
                ->where('filters.to', '2026-12-31')
            );
    });

    it('returns payments for the authenticated user (as menti) with correct structure', function (): void {
        $user = User::factory()->create();
        $session = MentorSession::factory()->create(['menti_id' => $user->getKey()]);
        $payment = Payment::factory()->approved()->create([
            'payable_type' => MentorSession::class,
            'payable_id'   => $session->getKey(),
        ]);

        actingAs($user)
            ->get(route('payments.history'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Payments/History')
                ->has('payments.data', 1)
                ->where('payments.data.0.id', $payment->getKey())
                ->where('payments.data.0.transaction_status', PaymentStatusEnum::APPROVED->value)
                ->where('payments.data.0.payable.type', 'MentorSession')
            );
    });

    it('returns payments for the authenticated user (as mentor)', function (): void {
        $mentor = User::factory()->create();
        $session = MentorSession::factory()->create(['mentor_id' => $mentor->getKey()]);
        Payment::factory()->approved()->create([
            'payable_type' => MentorSession::class,
            'payable_id'   => $session->getKey(),
        ]);

        actingAs($mentor)
            ->get(route('payments.history'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('payments.data', 1)
            );
    });

    it('filters payments by status', function (): void {
        $user = User::factory()->create();
        $session1 = MentorSession::factory()->create(['menti_id' => $user->getKey()]);
        $session2 = MentorSession::factory()->create(['menti_id' => $user->getKey()]);

        Payment::factory()->approved()->create([
            'payable_type' => MentorSession::class,
            'payable_id'   => $session1->getKey(),
        ]);
        Payment::factory()->declined()->create([
            'payable_type' => MentorSession::class,
            'payable_id'   => $session2->getKey(),
        ]);

        actingAs($user)
            ->get(route('payments.history').'?status=approved')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('payments.data', 1)
                ->where('payments.data.0.transaction_status', PaymentStatusEnum::APPROVED->value)
            );
    });

    it('does not return payments for unrelated users', function (): void {
        $stranger = User::factory()->create();
        $session = MentorSession::factory()->create();
        Payment::factory()->approved()->create([
            'payable_type' => MentorSession::class,
            'payable_id'   => $session->getKey(),
        ]);

        actingAs($stranger)
            ->get(route('payments.history'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('payments.data', 0)
            );
    });
});
