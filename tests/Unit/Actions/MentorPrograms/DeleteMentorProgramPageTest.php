<?php

declare(strict_types=1);

use App\Actions\MentorPrograms\DeleteMentorProgramPage;
use App\Enums\RoleEnum;
use App\Models\Currency;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

describe('Delete Mentor Program Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->currencies = Currency::query()->pluck('name', 'id')->toArray();

        $this->user = createAndAuthenticateMentorForDeletePage();
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
        $action = new DeleteMentorProgramPage;
        $response = $action->handle($this->request, $this->mentorProgram);

        expect($response)->toBeInstanceOf(RedirectResponse::class)->and($response->getTargetUrl())->toBe(route('mentor-program.create'))->and(MentorProgram::query()->count())->toBe(0);
    });

    it('handles deletion of non-existent mentor program', function (): void {
        $nonExistentProgram = MentorProgram::factory()->create([
            'mentor_id'   => $this->user->getKey(),
            'name'        => 'Non-existent Program',
            'slug'        => 'non-existent-program',
            'description' => 'Non-existent Description',
            'cost'        => 100.0,
            'currency_id' => array_key_first($this->currencies),
        ]);

        $nonExistentProgram->forceDelete();

        $nonExistentProgram->exists = false;

        $action = new DeleteMentorProgramPage;
        $response = $action->handle($this->request, $nonExistentProgram);

        expect($response)->toBeInstanceOf(RedirectResponse::class)->and($response->getTargetUrl())->toBe(route('mentor-program.create'));
    });
});

function createAndAuthenticateMentorForDeletePage(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    Auth::login($user);

    return $user;
}
