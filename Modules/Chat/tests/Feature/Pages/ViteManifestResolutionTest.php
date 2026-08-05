<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

/*
 * The rest of the suite runs with `withoutVite()` (see tests/TestCase.php), which stubs the
 * `@vite` Blade directive out entirely. That makes every "does the root Blade template
 * actually render?" failure invisible to feature tests. This test deliberately restores the
 * real Vite handler so that a root-template/manifest mismatch surfaces as a test failure
 * instead of a production 500.
 *
 * Sibling of Modules/Calendar/tests/Feature/Pages/ViteManifestResolutionTest.php, added when
 * the Chat frontend moved into Modules/Chat/resources/js.
 */
describe('Root template renders against the real Vite manifest', function (): void {
    beforeEach(function (): void {
        if (! file_exists(public_path('build/manifest.json'))) {
            $this->markTestSkipped('Vite manifest missing - run `yarn build` first.');
        }

        $this->seed(RoleSeeder::class);
        $this->withVite();

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    });

    it('renders the chat page', function (): void {
        $this->actingAs($this->user)
            ->get(route('page.chat'))
            ->assertStatus(Response::HTTP_OK);
    });
});
