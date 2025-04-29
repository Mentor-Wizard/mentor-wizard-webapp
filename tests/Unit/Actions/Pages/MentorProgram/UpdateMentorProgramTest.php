<?php

declare(strict_types=1);

use App\Actions\Pages\MentorProgram\UpdateMentorProgramPage;
use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Http\Requests\MentorProgram\UpdateMentorProgramRequest;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

mutates(UpdateMentorProgramPage::class);

describe('UpdateMentorProgramRequest Validation', function (): void {
    beforeEach(function (): void {
        DB::statement('ALTER SEQUENCE currencies_id_seq RESTART WITH 1');
        $this->seed(CurrencySeeder::class);
        $this->seed(RoleSeeder::class);

        $this->user = createAndAuthenticateMentor();
        $this->prepareRequest = function (UpdateMentorProgramRequest $request): void {
            $request->setContainer(app());
            $request->setRedirector(app(Illuminate\Routing\Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };
    });

    it('validates with correct data', function (array $validData): void {
        $request = new UpdateMentorProgramRequest;
        $request->merge($validData);
        ($this->prepareRequest)($request);

        expect($request->authorize())->toBeTrue();
        expect($request->rules())->toBeArray();
        expect(fn () => $request->validateResolved())->not->toThrow(ValidationException::class);
    })->with('validMentorProgramData');

    it('fails validation with invalid data', function (array $invalidData, string $errorField): void {
        $request = new UpdateMentorProgramRequest;
        $request->merge($invalidData);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey($errorField);
        }
    })->with('invalidMentorProgramData');

    it('prepares slug for validation', function (): void {
        $request = new UpdateMentorProgramRequest;
        $request->merge([
            'name'        => 'Test Program Name',
            'description' => 'Test Description',
            'cost'        => 99.99,
            'currency_id' => 1,
        ]);

        ($this->prepareRequest)($request);

        $request->validateResolved();

        expect($request->all())
            ->toHaveKey('slug')
            ->and($request->get('slug'))->toBe('test-program-name');
    });
});

describe('Update Mentor Program', function (): void {
    beforeEach(function (): void {
        DB::statement('ALTER SEQUENCE currencies_id_seq RESTART WITH 1');
        $this->seed(CurrencySeeder::class);
        $this->seed(RoleSeeder::class);

        $this->user = createAndAuthenticateMentor();

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'   => $this->user->id,
            'name'        => 'Original Program',
            'slug'        => 'original-program',
            'description' => 'Original Description',
            'cost'        => 50.0,
            'currency_id' => 1,
        ]);
    });

    it('updates mentor program with valid data', function (): void {
        $updateData = [
            'name'        => 'Updated Program',
            'slug'        => 'updated-program',
            'description' => 'Updated Description',
            'cost'        => 150.0,
            'currency_id' => 2,
        ];

        $request = mockUpdateMentorProgramRequest($updateData);
        $action = new UpdateMentorProgramPage;

        $action->handle($request, $this->mentorProgram);

        $updatedProgram = $this->mentorProgram->fresh();

        expect($updatedProgram)
            ->name->toBe($updateData['name'])
            ->slug->toBe($updateData['slug'])
            ->description->toBe($updateData['description'])
            ->cost->toBe($updateData['cost'])
            ->currency_id->toBe($updateData['currency_id'])
            ->mentor_id->toBe($this->user->id);
    });

    it('returns redirect response with correct route', function (): void {
        $updateData = [
            'name'        => 'Updated Program',
            'slug'        => 'updated-program',
            'description' => 'Updated Description',
            'cost'        => 150.0,
            'currency_id' => 2,
        ];

        $request = mockUpdateMentorProgramRequest($updateData);
        $response = (new UpdateMentorProgramPage)->handle($request, $this->mentorProgram);

        expect($response)
            ->toBeInstanceOf(Response::class)
            ->and($response->getTargetUrl())->toBe(route('mentor-program.edit', $updateData['slug']));
    });

    it('throws exception when trying to update non-existent program', function (): void {
        $updateData = [
            'name'        => 'Updated Program',
            'slug'        => 'updated-program',
            'description' => 'Updated Description',
            'cost'        => 150.0,
            'currency_id' => 2,
        ];

        $request = mockUpdateMentorProgramRequest($updateData);
        $this->mentorProgram->delete();

        expect(fn (): Response => (new UpdateMentorProgramPage)->handle($request, $this->mentorProgram))
            ->toThrow(ModelNotFoundException::class);
    });

    it("throws exception when trying to update another mentor's program", function (): void {
        // Create another user/mentor
        $anotherUser = User::factory()->create();
        $anotherUser->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));

        // Create a program owned by another mentor
        $anotherMentorProgram = MentorProgram::factory()->create([
            'mentor_id'   => $anotherUser->id,
            'name'        => 'Another Program',
            'slug'        => 'another-program',
            'description' => 'Another Description',
            'cost'        => 75.0,
            'currency_id' => 1,
        ]);

        $updateData = [
            'name'        => 'Trying to Update',
            'slug'        => 'trying-to-update',
            'description' => 'Trying to Update Description',
            'cost'        => 150.0,
            'currency_id' => 2,
        ];

        $request = mockUpdateMentorProgramRequest($updateData);

        expect(fn (): Response => (new UpdateMentorProgramPage)->handle($request, $anotherMentorProgram))
            ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class, 'Unauthorized action.');
    });

    it("throws 403 forbidden when trying to update another mentor's program", function (): void {
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

        $updateData = [
            'name'        => 'Trying to Update',
            'slug'        => 'trying-to-update',
            'description' => 'Trying to Update Description',
            'cost'        => 150.0,
            'currency_id' => 2,
        ];

        $request = mockUpdateMentorProgramRequest($updateData);

        try {
            (new UpdateMentorProgramPage)->handle($request, $anotherMentorProgram);
        } catch (Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
            expect($httpException->getStatusCode())->toBe(403)
                ->and($httpException->getMessage())->toBe('Unauthorized action.');

            return;
        }

        $this->fail('Exception was not thrown');
    });

});

function mockUpdateMentorProgramRequest(array $data, ?User $user = null): UpdateMentorProgramRequest
{
    $request = Mockery::mock(UpdateMentorProgramRequest::class);
    $request->shouldReceive('validated')->andReturn($data);
    $request->shouldReceive('user')->andReturn($user ?? Auth::user());

    return $request;
}

function createAndAuthenticateMentor(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));
    Auth::login($user);

    return $user;
}
