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

        $date = Date::now(config('app.timezone'))->format('Y-m-d');

        $action = new CalendarsListPage;

        $reqMonth = Request::create('/calendar', 'GET', ['mode' => 'Month view', 'date' => $date, 'timezone' => config('app.timezone')]);
        $resMonth = inertiaProps($action->handle($reqMonth));
        expect($resMonth['permissions'])->toBe('edit');

        $reqWeek = Request::create('/calendar', 'GET', ['mode' => 'Week view', 'date' => $date, 'timezone' => config('app.timezone')]);
        $resWeek = inertiaProps($action->handle($reqWeek));
        expect($resWeek['permissions'])->toBe('edit');

        $reqDay = Request::create('/calendar', 'GET', ['mode' => 'Day view', 'date' => $date, 'timezone' => config('app.timezone')]);
        $resDay = inertiaProps($action->handle($reqDay));
        expect($resDay['permissions'])->toBe('edit');
    });

    it('returns view permissions for non-mentor user', function (): void {
        $user = User::factory()->create();
        // Don't assign mentor role
        Auth::login($user);

        $action = new CalendarsListPage;
        $request = Request::create('/calendar', 'GET', ['timezone' => config('app.timezone')]);
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
        $request = Request::create('/calendar', 'GET', ['timezone' => config('app.timezone'), 'mode' => 'Week view']);
        $response = $action->handle($request);

        $props = inertiaProps($response);
        expect($props['events'])->toBe([]);
    });

    it('defaults to Month view when mode is not provided', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);

        $action = new CalendarsListPage;
        // No mode parameter
        $request = Request::create('/calendar', 'GET', ['timezone' => config('app.timezone'), 'date' => Date::now()->format('Y-m-d')]);
        $response = $action->handle($request);

        $props = inertiaProps($response);
        expect($props['events'])->toBeArray();
        // Should default to Month view and call GetMonthCalendarEventsService
    });

    it('includes availableColours in response', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $action = new CalendarsListPage;
        $request = Request::create('/calendar', 'GET', ['timezone' => config('app.timezone')]);
        $response = $action->handle($request);

        $props = inertiaProps($response);
        expect($props)->toHaveKey('availableColours')
            ->and($props['availableColours'])->toBeArray()
            ->and($props['availableColours'])->not->toBeEmpty();
    });

    it('returns edit permission only when user has mentor role', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);

        $action = new CalendarsListPage;
        $request = Request::create('/calendar', 'GET', ['timezone' => config('app.timezone')]);
        $response = $action->handle($request);

        $props = inertiaProps($response);
        expect($props['permissions'])->toBe('edit');
    });

    it('requires both timezone AND user to fetch events', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $action = new CalendarsListPage;

        // Has user but no timezone
        $request1 = Request::create('/calendar', 'GET', ['mode' => 'Day view', 'date' => Date::now()->format('Y-m-d')]);
        $response1 = $action->handle($request1);
        $props1 = inertiaProps($response1);
        expect($props1['events'])->toBe([]);

        // Has timezone but no user
        Auth::logout();
        $request2 = Request::create('/calendar', 'GET', ['timezone' => config('app.timezone'), 'mode' => 'Day view', 'date' => Date::now()->format('Y-m-d')]);
        $response2 = $action->handle($request2);
        $props2 = inertiaProps($response2);
        expect($props2['events'])->toBe([]);
    });
});
