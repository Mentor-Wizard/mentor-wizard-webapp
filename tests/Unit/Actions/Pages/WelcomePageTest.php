<?php

declare(strict_types=1);

use App\Actions\Pages\WelcomePage;
use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Application;
use Illuminate\Routing\RouteCollection;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

mutates(WelcomePage::class);

describe('WelcomePage Action', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('returns correct Inertia response', function (): void {
        $mockRouteCollection = Mockery::mock(RouteCollection::class);
        $mockRouteCollection->shouldReceive('getRoutesByName')->andReturn([]);

        Route::shouldReceive('has')
            ->with('login')
            ->once()
            ->andReturn(true);
        Route::shouldReceive('has')
            ->with('register')
            ->once()
            ->andReturn(true);

        Route::shouldReceive('getRoutes')->andReturn($mockRouteCollection);

        $action = new WelcomePage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Welcome')
            ->and(Arr::get($resultData->getData(), 'page.props'))->toMatchArray([
                'canLogin'       => true,
                'canRegister'    => true,
                'phpVersion'     => PHP_VERSION,
                'laravelVersion' => Application::VERSION,
            ])
            ->and(fn ($result): Pest\Mixins\Expectation => expect(Arr::get($resultData->getData(), 'page.props.mentors'))->toBeArray());
    });

    it('includes mentors in the response with pagination', function (): void {
        $role = Role::findByName(RoleEnum::MENTOR->value, 'web');

        User::factory()->count(7)->create()->each(function (User $user) use ($role): void {
            $user->syncRoles($role);
        });

        $response = $this->get('/');
        $response->assertStatus(SymfonyResponse::HTTP_OK);

        $response->assertInertia(function ($page): void {
            $page->has('mentors')
                ->has('mentors.data', 5)
                ->has('mentors.data.0.profile')
                ->has('mentors.links')
                ->has('mentors.meta');

            $mentors = $page->toArray()['props']['mentors']['data'];
            expect($mentors[0]['profile']['avatar'])->toBeNull();
        });
    });

    it('properly loads profile media relationship for mentors', function (): void {
        $role = Role::findByName(RoleEnum::MENTOR->value, 'web');
        $user = User::factory()->create();
        $user->syncRoles($role);

        // Set up the profile with media
        $user->profile()->update([
            'name'      => 'Test Name',
            'last_name' => 'Test Last Name',
        ]);

        $name = urlencode($user->profile->name.' '.$user->profile->last_name);
        $avatarUrl = sprintf('https://ui-avatars.com/api/?name=%s&background=random&size=256&format=png', $name);
        $user->profile->addMediaFromUrl($avatarUrl)
            ->usingFileName('avatar.png')
            ->toMediaCollection('avatar');

        $welcomePage = new WelcomePage;
        $reflection = new ReflectionMethod($welcomePage, 'handle');
        $welcomePageCode = file_get_contents($reflection->getFileName());

        expect($welcomePageCode)->toContain("with(['profile', 'profile.media'])");

        $result = $welcomePage->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();
        $mentors = Arr::get($resultData->getData(), 'page.props.mentors.data');

        expect($mentors)->toBeArray();
        expect($mentors[0]['profile'])->toHaveKey('avatar');

        $avatarUrl = $mentors[0]['profile']['avatar'];
        expect($avatarUrl)->not()->toBeNull();
        expect($avatarUrl)->toBeString();
        expect($avatarUrl)->not()->toBeEmpty();

    });
});
