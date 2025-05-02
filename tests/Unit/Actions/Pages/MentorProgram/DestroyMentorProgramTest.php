<?php

declare(strict_types=1);

use App\Actions\Pages\MentorProgram\DestroyMentorProgramPage;
use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

mutates(DestroyMentorProgramPage::class);

describe('Destroy Mentor Program', function (): void {
    beforeEach(function (): void {
        DB::statement('ALTER SEQUENCE currencies_id_seq RESTART WITH 1');
        $this->seed(CurrencySeeder::class);
        $this->seed(RoleSeeder::class);

        $this->user = createAndAuthenticateMentorForDestroy();
        $this->request = Request::create('/')->setUserResolver(fn (): User => $this->user);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'   => $this->user->id,
            'name'        => 'Test Program',
            'slug'        => 'test-program',
            'description' => 'Test Description',
            'cost'        => 100.0,
            'currency_id' => 1,
        ]);
    });

    it('deletes mentor program and returns redirect response', function (): void {
        $action = new DestroyMentorProgramPage;
        $response = $action->handle($this->request, $this->mentorProgram);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('mentor-program.create'))
            ->and(MentorProgram::query()->count())->toBe(0);
    });

    it('throws exception when trying to delete non-existent program', function (): void {
        $this->mentorProgram->delete();

        expect(fn (): RedirectResponse => (new DestroyMentorProgramPage)->handle($this->request, $this->mentorProgram))
            ->toThrow(ModelNotFoundException::class, 'Mentor program not found.');
    });

    it("throws 403 forbidden when trying to delete another mentor's program", function (): void {
        $anotherUser = User::factory()->create();
        $anotherUser->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));

        $anotherMentorProgram = MentorProgram::factory()->create([
            'mentor_id'   => $anotherUser->id,
            'name'        => 'Another Program',
            'slug'        => 'another-program',
            'description' => 'Another Description',
            'cost'        => 75.0,
            'currency_id' => 1,
        ]);

        try {
            (new DestroyMentorProgramPage)->handle($this->request, $anotherMentorProgram);
        } catch (Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
            expect($httpException->getStatusCode())->toBe(403)
                ->and($httpException->getMessage())->toBe('Unauthorized action.');

            return;
        }

        $this->fail('Exception was not thrown');
    });
});

function createAndAuthenticateMentorForDestroy(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));
    Auth::login($user);

    return $user;
}
