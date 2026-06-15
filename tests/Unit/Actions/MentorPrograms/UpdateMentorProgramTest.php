<?php

declare(strict_types=1);

use App\Actions\MentorPrograms\UpdateMentorProgramPage;
use App\Enums\MentorSessionTypeEnum;
use App\Enums\RoleEnum;
use App\Http\Requests\MentorProgram\UpdateMentorProgramRequest;
use App\Models\Currency;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Routing\Redirector;
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
            $request->setRedirector(resolve(Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };
    });

    it('validates with correct data', function (array $validData): void {
        $request = new UpdateMentorProgramRequest;
        $request->merge($validData);
        ($this->prepareRequest)($request);

        expect($request->authorize())->toBeTrue()
            ->and($request->rules())->toBeArray()
            ->and($request->validateResolved(...))->not->toThrow(ValidationException::class);
    })->with([
        'full valid data' => fn (): array => [
            'name'                  => 'Valid Program Name',
            'slug'                  => 'valid-program-slug',
            'description'           => 'Valid program description',
            'cost'                  => 99.99,
            'session_duration'      => 60,
            'session_type_options'  => [
                MentorSessionTypeEnum::CODE_REVIEW->value,
                MentorSessionTypeEnum::VIDEO_SESSION->value,
            ],
            'currency_id'           => array_key_first($this->currencies),
        ],
        'minimal valid data' => fn (): array => [
            'name'                  => 'Min Program',
            'slug'                  => 'min-program',
            'description'           => 'Min description',
            'cost'                  => 0,
            'session_duration'      => 60,
            'session_type_options'  => [
                MentorSessionTypeEnum::CODE_REVIEW->value,
                MentorSessionTypeEnum::VIDEO_SESSION->value,
            ],
            'currency_id'       => array_key_first($this->currencies),
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
        'end_time before start_time' => fn (): array => [
            [
                'name'        => 'Valid Name',
                'slug'        => 'valid-slug',
                'description' => 'Valid description',
                'cost'        => 99.99,
                'currency_id' => array_key_first($this->currencies),
                'start_time'  => '2026-05-01 10:00',
                'end_time'    => '2026-05-01 09:00',
            ],
            'errorField' => 'end_time',
        ],

    ]);

    describe('Update Mentor Program', function (): void {
        beforeEach(function (): void {
            $this->seed(RoleSeeder::class);
            $this->seed(CurrencySeeder::class);
            $this->currencies = Currency::query()->pluck('name', 'id')->toArray();

            $this->user = createAndAuthenticateMentorForUpdate();

            $this->mentorProgram = MentorProgram::factory()->create([
                'mentor_id'   => $this->user->getKey(),
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
                'cost'        => '150.00',
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
                ->mentor_id->toBe($this->user->getKey());
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
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);

        return $user;
    }
});
