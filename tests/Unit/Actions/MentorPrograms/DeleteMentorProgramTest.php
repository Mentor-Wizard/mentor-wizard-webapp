<?php

declare(strict_types=1);

use App\Actions\MentorPrograms\DeleteMentorProgram;
use App\Enums\RoleEnum;
use App\Models\Currency;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

mutates(DeleteMentorProgram::class);

describe('Destroy Mentor Program', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->currencies = Currency::query()->pluck('name', 'id')->toArray();

        $this->user = createAndAuthenticateMentorForDestroy();
        $this->request = Request::create('/')->setUserResolver(fn (): User => $this->user);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'   => $this->user->getKey(),
            'name'        => 'Test Program',
            'slug'        => 'test-program',
            'description' => 'Test Description',
            'cost'        => 100.0,
            'currency_id' => array_key_first($this->currencies),
        ]);
    });

    it('deletes mentor program and returns redirect response', function (): void {
        $action = new DeleteMentorProgram;
        $response = $action->handle($this->request, $this->mentorProgram);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('mentor-program.list'))
            ->and(MentorProgram::query()->count())->toBe(0);
    });

    it('throws exception when trying to delete non-existent program', function (): void {
        $this->mentorProgram->delete();

        expect(fn (): RedirectResponse => (new DeleteMentorProgram)->handle($this->request, $this->mentorProgram))
            ->toThrow(ModelNotFoundException::class, 'Mentor program not found.');
    });

    it("throws 403 forbidden when trying to delete another mentor's program", function (): void {
        $anotherUser = User::factory()->create();
        $anotherUser->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $anotherMentorProgram = MentorProgram::factory()->create([
            'mentor_id'   => $anotherUser->getKey(),
            'name'        => 'Another Program',
            'slug'        => 'another-program',
            'description' => 'Another Description',
            'cost'        => 75.0,
            'currency_id' => array_key_first($this->currencies),
        ]);

        try {
            (new DeleteMentorProgram)->handle($this->request, $anotherMentorProgram);
        } catch (Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
            expect($httpException->getStatusCode())->toBe(Response::HTTP_FORBIDDEN)
                ->and($httpException->getMessage())->toBe('Unauthorized action.');

            return;
        }

        $this->fail('Exception was not thrown');
    });
});

function createAndAuthenticateMentorForDestroy(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    Auth::login($user);

    return $user;
}
