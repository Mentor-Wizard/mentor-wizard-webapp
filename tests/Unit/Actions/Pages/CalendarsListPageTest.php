<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\CalendarsListPage;
use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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

        $date = Carbon::now('UTC')->format('Y-m-d');

        $action = new CalendarsListPage;

        $reqMonth = Request::create('/calendar', 'GET', ['mode' => 'Month view', 'date' => $date]);
        $resMonth = inertiaProps($action->handle($reqMonth));
        expect($resMonth['permissions'])->toBe('edit');

        $reqWeek = Request::create('/calendar', 'GET', ['mode' => 'Week view', 'date' => $date]);
        $resWeek = inertiaProps($action->handle($reqWeek));
        expect($resWeek['permissions'])->toBe('edit');

        $reqDay = Request::create('/calendar', 'GET', ['mode' => 'Day view', 'date' => $date]);
        $resDay = inertiaProps($action->handle($reqDay));
        expect($resDay['permissions'])->toBe('edit');
    });
});
