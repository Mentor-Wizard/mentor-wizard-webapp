<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\ShowCalendarEventPage;
use App\Enums\RoleEnum;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
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
        /** @var Event $event */
        $event = Event::factory()->create();

        $response = (new ShowCalendarEventPage)->handle((string) $event->getKey());

        expect($response)->toBeInstanceOf(InertiaResponse::class);
        $props = inertiaProps($response);

        expect($props)
            ->toHaveKeys(['canLogin', 'canRegister', 'laravelVersion', 'phpVersion', 'locale', 'permissions', 'event'])
            ->and($props['permissions'])->toBe('edit')
            ->and($props['event'])->toBeArray();
    });
});
