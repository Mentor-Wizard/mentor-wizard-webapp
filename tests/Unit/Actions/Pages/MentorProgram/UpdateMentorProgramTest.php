<?php

declare(strict_types=1);

use App\Actions\Pages\MentorProgram\UpdateMentorProgramPage;
use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Http\Requests\MentorProgram\UpdateMentorProgramRequest;
use App\Models\Currency;
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
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->currencies = Currency::query()->pluck('name', 'id')->toArray();

        $this->user = createAndAuthenticateMentorForUpdate();
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
    })->with([
        'full valid data' => fn (): array => [
            'name'        => 'Valid Program Name',
            'slug'        => 'valid-program-slug',
            'description' => 'Valid program description',
            'cost'        => 99.99,
            'currency_id' => array_key_first($this->currencies),
        ],
        'minimal valid data' => fn (): array => [
            'name'        => 'Min Program',
            'slug'        => 'min-program',
            'description' => 'Min description',
            'cost'        => 0,
            'currency_id' => array_key_first($this->currencies),
        ],
    ]);

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
    })->with([
        'empty name' => fn (): array => [
            [
                'name'        => '',
                'slug'        => 'valid-slug',
                'description' => 'Valid description',
                'cost'        => 99.99,
                'currency_id' => array_key_first($this->currencies),
            ],
            'errorField' => 'name',
        ],
        'too long name' => fn (): array => [
            [
                'name'        => str_repeat('a', 256),
                'slug'        => 'valid-slug',
                'description' => 'Valid description',
                'cost'        => 99.99,
                'currency_id' => array_key_first($this->currencies),
            ],
            'errorField' => 'name',
        ],
        'invalid slug format' => fn (): array => [
            [
                'name'        => 'Valid Name',
                'slug'        => 123,
                'description' => 'Valid description',
                'cost'        => 99.99,
                'currency_id' => array_key_first($this->currencies),
            ],
            'errorField' => 'slug',
        ],
        'negative cost' => fn (): array => [
            [
                'name'        => 'Valid Name',
                'slug'        => 'valid-slug',
                'description' => 'Valid description',
                'cost'        => -1,
                'currency_id' => array_key_first($this->currencies),
            ],
            'errorField' => 'cost',
        ],
        'non-existent currency' => fn (): array => [
            [
                'name'        => 'Valid Name',
                'slug'        => 'valid-slug',
                'description' => 'Valid description',
                'cost'        => 99.99,
                'currency_id' => 999,
            ],
            'errorField' => 'currency_id',
        ],

    ]);

    it('prepares slug for validation', function (): void {
        $request = new UpdateMentorProgramRequest;
        $request->merge([
            'name'        => 'Test Program Name',
            'description' => 'Test Description',
            'cost'        => 99.99,
            'currency_id' => array_key_first($this->currencies),
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
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->currencies = Currency::query()->pluck('name', 'id')->toArray();

        $this->user = createAndAuthenticateMentorForUpdate();

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'   => $this->user->id,
            'name'        => 'Original Program',
            'slug'        => 'original-program',
            'description' => 'Original Description',
            'cost'        => 50.0,
            'currency_id' => array_key_first($this->currencies),
        ]);
    });

    it('updates mentor program with valid data', function (): void {
        $updateData = [
            'name'        => 'Updated Program',
            'slug'        => $this->mentorProgram->slug,
            'description' => 'Updated Description',
            'cost'        => 150.0,
            'currency_id' => array_keys($this->currencies)[1],
        ];

        $request = mockUpdateMentorProgramRequest($updateData);
        $action = new UpdateMentorProgramPage;

        $action->handle($request, $this->mentorProgram);

        $updatedProgram = $this->mentorProgram->fresh();

        expect($updatedProgram)
            ->name->toBe($updateData['name'])
            ->slug->toBe($this->mentorProgram->slug)
            ->description->toBe($updateData['description'])
            ->cost->toBe($updateData['cost'])
            ->currency_id->toBe($updateData['currency_id'])
            ->mentor_id->toBe($this->user->id);
    });

    it('returns redirect response with correct route', function (): void {
        $updateData = [
            'name'        => 'Updated Program',
            'slug'        => $this->mentorProgram->slug,
            'description' => 'Updated Description',
            'cost'        => 150.0,
            'currency_id' => array_keys($this->currencies)[1],
        ];

        $request = mockUpdateMentorProgramRequest($updateData);
        $response = (new UpdateMentorProgramPage)->handle($request, $this->mentorProgram);

        expect($response)
            ->toBeInstanceOf(Response::class)
            ->and($response->getTargetUrl())->toBe(route('mentor-program.edit', $this->mentorProgram->slug));
    });

    it('throws exception when trying to update non-existent program', function (): void {
        $updateData = [
            'name'        => 'Updated Program',
            'slug'        => $this->mentorProgram->slug,
            'description' => 'Updated Description',
            'cost'        => 150.0,
            'currency_id' => array_keys($this->currencies)[1],
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
            'currency_id' => array_key_first($this->currencies),
        ]);

        $updateData = [
            'name'        => 'Trying to Update',
            'slug'        => $this->mentorProgram->slug,
            'description' => 'Trying to Update Description',
            'cost'        => 150.0,
            'currency_id' => array_keys($this->currencies)[1],
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
            'currency_id' => array_key_first($this->currencies),
        ]);

        $updateData = [
            'name'        => 'Trying to Update',
            'slug'        => $this->mentorProgram->slug,
            'description' => 'Trying to Update Description',
            'cost'        => 150.0,
            'currency_id' => array_keys($this->currencies)[1],
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

function createAndAuthenticateMentorForUpdate(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));
    Auth::login($user);

    return $user;
}
