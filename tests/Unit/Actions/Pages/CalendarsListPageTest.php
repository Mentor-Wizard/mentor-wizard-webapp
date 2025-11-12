<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\CalendarsListPage;
use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Role;

mutates(CalendarsListPage::class);

describe('CalendarsListPage', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('returns default mode and date when missing, includes base props, unauthenticated yields null events and view permissions', function (): void {
        $action = new CalendarsListPage;
        $request = Request::create('/calendar', 'GET');
        $response = $action->handle($request);

        expect($response)
            ->toBeInstanceOf(InertiaResponse::class);

        $props = inertiaProps($response);

        expect($props)
            ->toHaveKeys(['canLogin', 'canRegister', 'laravelVersion', 'phpVersion', 'locale']);
    });

    it('returns week/daily/month events for mentor user depending on mode', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);

        $date = Date::now('UTC')->format('Y-m-d');

        $action = new CalendarsListPage;

        $reqMonth = Request::create('/calendar', 'GET', ['mode' => 'Month view', 'date' => $date, 'timezone' => 'UTC']);
        $resMonth = inertiaProps($action->handle($reqMonth));
        expect($resMonth['permissions'])->toBe('edit');

        $reqWeek = Request::create('/calendar', 'GET', ['mode' => 'Week view', 'date' => $date, 'timezone' => 'UTC']);
        $resWeek = inertiaProps($action->handle($reqWeek));
        expect($resWeek['permissions'])->toBe('edit');

        $reqDay = Request::create('/calendar', 'GET', ['mode' => 'Day view', 'date' => $date, 'timezone' => 'UTC']);
        $resDay = inertiaProps($action->handle($reqDay));
        expect($resDay['permissions'])->toBe('edit');
    });

    it('returns view permissions for non-mentor user', function (): void {
        $user = User::factory()->create();
        // Don't assign mentor role
        Auth::login($user);

        $action = new CalendarsListPage;
        $request = Request::create('/calendar', 'GET', ['timezone' => 'UTC']);
        $response = $action->handle($request);

        $props = inertiaProps($response);
        expect($props['permissions'])->toBe('view');
    });

    it('uses current date when date param is missing', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);

        $action = new CalendarsListPage;
        $request = Request::create('/calendar', 'GET', ['timezone' => 'Europe/Kyiv', 'mode' => 'Month view']);
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(InertiaResponse::class);
        // Should not throw error and should use Date::now()
    });

    it('returns empty events array when timezone is missing', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);

        $action = new CalendarsListPage;
        $request = Request::create('/calendar', 'GET', ['mode' => 'Day view', 'date' => Date::now()->format('Y-m-d')]);
        $response = $action->handle($request);

        $props = inertiaProps($response);
        expect($props['events'])->toBe([]);
    });

    it('returns empty events array when user is not authenticated', function (): void {
        Auth::logout();

        $action = new CalendarsListPage;
        $request = Request::create('/calendar', 'GET', ['timezone' => 'UTC', 'mode' => 'Week view']);
        $response = $action->handle($request);

        $props = inertiaProps($response);
        expect($props['events'])->toBe([]);
    });
});
