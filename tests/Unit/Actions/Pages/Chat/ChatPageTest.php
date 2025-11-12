<?php

declare(strict_types=1);

namespace Chat;

use App\Actions\Pages\Chat\GetChatPage;
use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Arr;
use Inertia\Response;
use Spatie\Permission\Models\Role;

mutates(GetChatPage::class);

describe('Chat Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('Mentor`s data correct', function (): void {
        $user = User::factory()->create([
            'username' => 'Test User',
            'email'    => 'test@example.com',
        ]);
        $user->profile->update([
            'name'        => 'profile name',
            'last_name'   => 'profile last_name',
            'linkedin'    => 'profile linkedin',
            'telegram'    => 'profile telegram',
            'whatsapp'    => 'profile whatsapp',
            'phone'       => 'profile phone',
        ]);

        $user->refresh();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $action = new GetChatPage;
        $result = $action->handle($user);
        $response = $result->toResponse(request());
        $original = $response->getOriginalContent();

        expect($result)
            ->toBeInstanceOf(Response::class)
            ->and(Arr::get($original->getData(), 'page.component'))->toBe('Chat/ChatPage')
            ->and(Arr::get($original->getData(), 'page.props.user.username'))->toBe('Test User')
            ->and(Arr::get($original->getData(), 'page.props.user.profile.name'))->toBe('profile name')
            ->and(Arr::get($original->getData(), 'page.props.user.profile.last_name'))->toBe('profile last_name')
            ->and(Arr::get($original->getData(), 'page.props.user.profile.linkedin'))->toBe('profile linkedin')
            ->and(Arr::get($original->getData(), 'page.props.user.profile.telegram'))->toBe('profile telegram')
            ->and(Arr::get($original->getData(), 'page.props.user.profile.whatsapp'))->toBe('profile whatsapp')
            ->and(Arr::get($original->getData(), 'page.props.user.profile.phone'))->toBe('profile phone');
    });

});
