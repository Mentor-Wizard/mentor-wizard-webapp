<?php

declare(strict_types=1);

namespace Modules\Chat\Tests\Unit\Actions\Pages;

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Arr;
use Inertia\Response;
use Modules\Chat\Actions\Pages\GetChatPage;
use Spatie\Permission\Models\Role;

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
        $result = $action->handle();
        $response = $result->toResponse(request());
        $original = $response->getOriginalContent();

        expect($result)
            ->toBeInstanceOf(Response::class)
            ->and(Arr::get($original->getData(), 'page.component'))->toBe('Chat/ChatPage');
    });

});
