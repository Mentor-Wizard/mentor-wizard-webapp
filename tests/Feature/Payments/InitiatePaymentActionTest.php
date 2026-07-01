<?php

declare(strict_types=1);

use App\Enums\CalendarEventStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\Currency;
use App\Models\MentorProgram;
use App\Models\MentorSession;
use App\Models\Payment;
use App\Models\User;
use App\Support\CurrencyConverter;
use AratKruglik\WayForPay\Facades\WayForPay;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

describe('InitiatePaymentAction', function (): void {
    it('requires authentication', function (): void {
        post(route('payments.initiate'), [
            'payable_type' => 'mentor_session',
            'payable_id'   => 1,
        ])->assertRedirect(route('login'));
    });

    it('returns 422 when payable_type is invalid', function (): void {
        WayForPay::shouldReceive('purchase')->never();

        $user = User::factory()->create();

        actingAs($user)
            ->post(route('payments.initiate'), [
                'payable_type' => 'invalid_type',
                'payable_id'   => 1,
            ])
            ->assertSessionHasErrors('payable_type');
    });

    it('returns 422 when payable_id is missing', function (): void {
        WayForPay::shouldReceive('purchase')->never();

        $user = User::factory()->create();

        actingAs($user)
            ->post(route('payments.initiate'), [
                'payable_type' => 'mentor_session',
            ])
            ->assertSessionHasErrors('payable_id');
    });

    it('returns 403 when the user is not authorized to pay for the session', function (): void {
        WayForPay::shouldReceive('purchase')->never();

        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $session = MentorSession::factory()->create(['menti_id' => $owner->getKey()]);
        CalendarEvent::factory()->create([
            'mentor_session_id' => $session->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
        ]);

        actingAs($stranger)
            ->post(route('payments.initiate'), [
                'payable_type' => 'mentor_session',
                'payable_id'   => $session->getKey(),
            ])
            ->assertForbidden();

        expect(Payment::query()->count())->toBe(0);
    });

    it('creates a PENDING payment and returns WayForPay HTML for an authorized session', function (): void {
        WayForPay::shouldReceive('purchase')->once()->andReturn('<form id="wfp"></form>');

        $menti = User::factory()->create();
        $session = MentorSession::factory()->create([
            'menti_id' => $menti->getKey(),
            'cost'     => 250.50,
        ]);
        CalendarEvent::factory()->create([
            'mentor_session_id' => $session->getKey(),
            'status'            => CalendarEventStatusEnum::PENDING_PAYMENT,
        ]);

        $response = actingAs($menti)
            ->post(route('payments.initiate'), [
                'payable_type' => 'mentor_session',
                'payable_id'   => $session->getKey(),
            ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');

        expect($response->getContent())->toContain('wfp');

        assertDatabaseHas(Payment::class, [
            'payable_type'       => MentorSession::class,
            'payable_id'         => $session->getKey(),
            'amount'             => CurrencyConverter::toCents(250.50),
            'currency'           => 'UAH',
            'transaction_status' => PaymentStatusEnum::PENDING->value,
        ]);
    });

    it('resolves the currency from the session program when present', function (): void {
        WayForPay::shouldReceive('purchase')->once()->andReturn('<html></html>');

        $usd = Currency::factory()->create(['name' => 'USD']);
        $mentor = User::factory()->create();
        $mentor->assignRole(RoleEnum::MENTOR->value);

        $program = MentorProgram::factory()->create([
            'mentor_id'   => $mentor->getKey(),
            'currency_id' => $usd->getKey(),
        ]);

        $menti = User::factory()->create();
        $session = MentorSession::factory()->create([
            'menti_id'          => $menti->getKey(),
            'mentor_program_id' => $program->getKey(),
        ]);
        CalendarEvent::factory()->create([
            'mentor_session_id' => $session->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
        ]);

        actingAs($menti)
            ->post(route('payments.initiate'), [
                'payable_type' => 'mentor_session',
                'payable_id'   => $session->getKey(),
            ])
            ->assertOk();

        assertDatabaseHas(Payment::class, [
            'payable_id' => $session->getKey(),
            'currency'   => 'USD',
        ]);
    });

    it('creates a PENDING payment for an authorized mentor program purchase', function (): void {
        WayForPay::shouldReceive('purchase')->once()->andReturn('<html></html>');

        $eur = Currency::factory()->create(['name' => 'EUR']);
        $mentor = User::factory()->create();
        $mentor->assignRole(RoleEnum::MENTOR->value);

        $program = MentorProgram::factory()->create([
            'mentor_id'   => $mentor->getKey(),
            'currency_id' => $eur->getKey(),
            'cost'        => 99.99,
        ]);

        $buyer = User::factory()->create();

        actingAs($buyer)
            ->post(route('payments.initiate'), [
                'payable_type' => 'mentor_program',
                'payable_id'   => $program->getKey(),
            ])
            ->assertOk();

        assertDatabaseHas(Payment::class, [
            'payable_type'       => MentorProgram::class,
            'payable_id'         => $program->getKey(),
            'amount'             => CurrencyConverter::toCents(99.99),
            'currency'           => 'EUR',
            'transaction_status' => PaymentStatusEnum::PENDING->value,
        ]);
    });

    it('returns 403 when the mentor tries to pay for their own program', function (): void {
        WayForPay::shouldReceive('purchase')->never();

        $mentor = User::factory()->create();
        $mentor->assignRole(RoleEnum::MENTOR->value);

        $program = MentorProgram::factory()->create(['mentor_id' => $mentor->getKey()]);

        actingAs($mentor)
            ->post(route('payments.initiate'), [
                'payable_type' => 'mentor_program',
                'payable_id'   => $program->getKey(),
            ])
            ->assertForbidden();

        expect(Payment::query()->count())->toBe(0);
    });
});
