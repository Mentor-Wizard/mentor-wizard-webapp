<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\UpdateProfileInfoPage;
use App\Actions\Pages\Profile\UpdateProfileMainPage;
use App\Http\Requests\Profile\UpdateProfileInfoRequest;
use App\Http\Requests\Profile\UpdateProfileMainRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;

mutates(UpdateProfileInfoPage::class);

describe('Update Info Profile', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('updates user info profile successfully', function (User $user, array $updateData): void {
        Auth::login($user);

        $request = mockUpdateInfoProfileRequest($updateData, $user);
        $action = new UpdateProfileInfoPage;
        $result = $action->handle($request);

        expect($result)->toBeInstanceOf(RedirectResponse::class)
            ->and($result->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($result->getTargetUrl())->toBe(route('profile.edit'));
    })->with([
        'info updated user with new email' => fn (): array => [
            'user' => User::factory()->withProfile()->create(),
            'updateData' => [
                'linkedin'    => 'https://www.linkedin.com/in/john',
                'telegram'    => 'https://t.me/john',
                'whatsapp'    => 'https://wa.me/john',
                'description' => 'description',
                'phone'       => '+380671234567',
            ],
        ]
    ]);

    it('check data update', function (): void {
        $user = User::factory()->withProfile()->create();
        $updateData = [
            'linkedin'    => 'https://www.linkedin.com/in/john',
            'telegram'    => 'https://t.me/john',
            'whatsapp'    => 'https://wa.me/380671234578',
            'description' => 'description',
            'phone'       => '+380671234567',
        ];

        $action = new UpdateProfileInfoPage;
        $reflection = new ReflectionClass(UpdateProfileInfoPage::class);
        $method = $reflection->getMethod('dataUpdate');
        $method->setAccessible(true);

        $request = mockUpdateInfoProfileRequest($updateData, $user);
        $data = $method->invoke($action, $request);
        expect($data['linkedin'])->toBe('https://www.linkedin.com/in/john')
            ->and($data['telegram'])->toBe('https://t.me/john')
            ->and($data['whatsapp'])->toBe('https://wa.me/380671234578')
            ->and($data['description'])->toBe('description')
            ->and($data['phone'])->toBe('+380671234567');
    });
});

function mockUpdateInfoProfileRequest(array $data, User $user): UpdateProfileMainRequest|MockInterface
{
    $request = Mockery::mock(UpdateProfileInfoRequest::class);
    $request->shouldReceive('get')->with('linkedin')->andReturn($data['linkedin']);
    $request->shouldReceive('get')->with('telegram')->andReturn($data['telegram']);
    $request->shouldReceive('get')->with('whatsapp')->andReturn($data['whatsapp']);
    $request->shouldReceive('get')->with('description')->andReturn($data['description']);
    $request->shouldReceive('get')->with('phone')->andReturn($data['phone']);

    return $request;
}
