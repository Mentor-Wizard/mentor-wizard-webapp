<?php

declare(strict_types=1);

use App\Actions\Pages\MentorProgram\StoreMentorProgramPage;
use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Http\Requests\MentorProgram\StoreMentorProgramRequest;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

mutates(StoreMentorProgramPage::class);

describe('Store Mentor Program', function (): void {
    beforeEach(function (): void {
        DB::statement('ALTER SEQUENCE currencies_id_seq RESTART WITH 1');
        $this->seed(CurrencySeeder::class);
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));

        Auth::login($this->user);

    });

    it('creates mentor program with valid data', function (): void {
        $this->validData = [
            'name'        => 'Test Program',
            'slug'        => 'test-program',
            'description' => 'Test Description',
            'cost'        => 100.0,
            'currency_id' => 1,
        ];
        $request = mockStoreMentorProgramRequest($this->validData);
        $action = new StoreMentorProgramPage;

        $action->handle($request);

        expect(MentorProgram::query()->count())->toBe(1)
            ->and(MentorProgram::query()->first())
            ->name->toBe($this->validData['name'])
            ->description->toBe($this->validData['description'])
            ->cost->toBe($this->validData['cost'])
            ->currency_id->toBe($this->validData['currency_id'])
            ->mentor_id->toBe($this->user->id);
    });

    it('returns redirect response', function (): void {
        $request = mockStoreMentorProgramRequest([
            'name'        => 'Test Program',
            'slug'        => 'test-program',
            'description' => 'Test Description',
            'cost'        => 100.0,
            'currency_id' => 1,
        ]);

        $response = (new StoreMentorProgramPage)->handle($request);

        expect($response)->toBeInstanceOf(Response::class)
            ->and($response->getTargetUrl())->toBe(route('mentor-program.create'));
    });

});

function mockStoreMentorProgramRequest(array $data): StoreMentorProgramRequest
{
    $request = Mockery::mock(StoreMentorProgramRequest::class);
    $request->shouldReceive('validated')->andReturn($data);

    return $request;
}
