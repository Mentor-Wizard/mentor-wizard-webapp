<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Modules\MentorProgram\Models\MentorProgram;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

/*
 * The rest of the suite runs with `withoutVite()` (see tests/TestCase.php), which stubs the
 * `@vite` Blade directive out entirely. That makes every "does the root Blade template
 * actually render?" failure invisible to feature tests. These tests deliberately restore the
 * real Vite handler so that a root-template/manifest mismatch surfaces as a test failure
 * instead of a production 500 (mirrors Modules/Calendar/tests/Feature/Pages/ViteManifestResolutionTest.php).
 */
describe('Root template renders against the real Vite manifest', function (): void {
    beforeEach(function (): void {
        if (! file_exists(public_path('build/manifest.json'))) {
            $this->markTestSkipped('Vite manifest missing - run `yarn build` first.');
        }

        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->withVite();

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->program = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);
    });

    it('renders the mentor program creation page', function (): void {
        $this->actingAs($this->mentor)
            ->get(route('mentor-program.create'))
            ->assertStatus(Response::HTTP_OK);
    });

    it('renders the mentor program edit page', function (): void {
        $this->actingAs($this->mentor)
            ->get(route('mentor-program.edit', $this->program->slug))
            ->assertStatus(Response::HTTP_OK);
    });

    it('renders the mentor program list page', function (): void {
        $this->actingAs($this->mentor)
            ->get(route('mentor-program.list'))
            ->assertStatus(Response::HTTP_OK);
    });
});
