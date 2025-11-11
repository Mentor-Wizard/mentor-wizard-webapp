<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\ShowCalendarEventPage;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Role;

mutates(ShowCalendarEventPage::class);

describe('ShowCalendarEventPage', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($this->user);
    });

    it('includes base props, permissions and event payload', function (): void {
        /** @var CalendarEvent $event */
        $event = CalendarEvent::factory()->create();

        $request = new Request(['timezone' => 'UTC']);
        $response = (new ShowCalendarEventPage)->handle($event, $request);

        expect($response)->toBeInstanceOf(InertiaResponse::class);
        $props = inertiaProps($response);

        expect($props)
            ->toHaveKeys(['canLogin', 'canRegister', 'laravelVersion', 'phpVersion', 'locale', 'permissions', 'event'])
            ->and($props['permissions'])->toBe('edit')
            ->and($props['event'])->toBeArray();
    });
});
