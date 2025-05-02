<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed(RoleSeeder::class);
    $user = User::factory()->create();
    actingAs($user);
});

describe('Successful Scenarios', function (): void {
    it('renders the profile page', function (): void {
        $this->get(route('profile.edit'))
            ->assertStatus(Response::HTTP_OK);
    });
    it('fill profile', function (): void {
        $user = User::factory()->withProfile()->create();

        $response = $this->actingAs($user)->patch(route('profile.update-info'), [
            'linkedin'    => 'https://www.linkedin.com/in/john',
            'telegram'    => 'https://t.me/john',
            'whatsapp'    => 'https://wa.me/john',
            'description' => 'description',
            'phone'       => '+380671234567',
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertTrue($response->isRedirect(route('profile.edit')));

        $user->refresh();
        expect($user->profile->linkedin)->toBe('https://www.linkedin.com/in/john')
            ->and($user->profile->telegram)->toBe('https://t.me/john')
            ->and($user->profile->whatsapp)->toBe('https://wa.me/john')
            ->and($user->profile->description)->toBe('description')
            ->and($user->profile->phone)->toBe('+380671234567');
    });
});

describe('Unsuccessful Scenarios', function (): void {
    it('returns 404 for incorrect profile page address', function (): void {
        $this->get('/profil-ee')->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('does not allow name longer than the limit', function (): void {
        $user = User::factory()->create();

        $response = $this->patch(route('profile.update-info'), [
            'linkedin'    => 'https://www.linkedin.com/in/'.str_repeat('a', 600),
            'telegram'    => 'https://t.me/'.str_repeat('a', 600),
            'whatsapp'    => 'https://wa.me/'.str_repeat('a', 600),
            'description' => str_repeat('a', 6000),
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertFalse($response->isRedirect(route('profile.edit')));

        $user->refresh();
        $this->assertNull($user->profile);
    });
});
