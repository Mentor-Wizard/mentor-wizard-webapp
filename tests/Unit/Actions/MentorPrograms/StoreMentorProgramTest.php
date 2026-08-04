<?php

declare(strict_types=1);

use App\Actions\MentorPrograms\StoreMentorProgramPage;
use App\Enums\MentorSessionTypeEnum;
use App\Enums\RoleEnum;
use App\Http\Requests\MentorProgram\StoreMentorProgramRequest;
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

describe('StoreMentorProgramRequest Validation', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->currencies = Currency::query()->pluck('name', 'id')->toArray();

        $this->user = createAndAuthenticateMentorForStore();
        $this->prepareRequest = function (StoreMentorProgramRequest $request): void {
            $request->setContainer(app());
            $request->setRedirector(resolve(Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };
    });

    it('validates with correct data', function (): void {
        $request = new StoreMentorProgramRequest;
        $request->merge([
            'name'                  => 'Test Program Name',
            'description'           => 'Test Description',
            'slug'                  => 'test-program',
            'cost'                  => 99.99,
            'session_duration'      => 60,
            'session_type_options'  => [
                MentorSessionTypeEnum::CODE_REVIEW->value,
                MentorSessionTypeEnum::VIDEO_SESSION->value,
            ],
            'currency_id' => array_key_first($this->currencies),
        ]);
        ($this->prepareRequest)($request);

        expect($request->authorize())->toBeTrue()
            ->and($request->rules())->toBeArray()
            ->and($request->validateResolved(...))->not->toThrow(ValidationException::class);
    });

    it('fails validation with missing required fields', function (): void {
        $request = new StoreMentorProgramRequest;
        $request->merge([]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())
                ->toHaveKeys(['name', 'description', 'cost', 'currency_id']);
        }
    });

    it('fails validation with invalid cost value', function (): void {
        $request = new StoreMentorProgramRequest;
        $request->merge([
            'name'        => 'Test Program',
            'description' => 'Test Description',
            'cost'        => -10.00,
            'currency_id' => 1,
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('cost');
        }
    });

    it('fails validation with non-existent currency', function (): void {
        $request = new StoreMentorProgramRequest;
        $request->merge([
            'name'        => 'Test Program',
            'description' => 'Test Description',
            'cost'        => 99.99,
            'currency_id' => 999,
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('currency_id');
        }
    });
});

describe('Store Mentor Program', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->currencies = Currency::query()->pluck('name', 'id')->toArray();

        $this->user = createAndAuthenticateMentorForStore();
    });

    it('creates mentor program with valid data', function (): void {
        $this->validData = [
            'name'        => 'Test Program',
            'slug'        => 'test-program',
            'description' => 'Test Description',
            'cost'        => '100.00',
            'currency_id' => array_key_first($this->currencies),
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
            ->mentor_id->toBe($this->user->getKey());
    });

    it('does not modify slug if provided during creation', function (): void {
        $request = mockStoreMentorProgramRequest([
            'name'        => 'Test Program',
            'slug'        => 'custom-slug',
            'description' => 'Test Description',
            'cost'        => 100.0,
            'currency_id' => array_key_first($this->currencies),
        ]);

        $action = new StoreMentorProgramPage;
        $action->handle($request);

        $createdProgram = MentorProgram::query()->latest()->first();

        expect($createdProgram->slug)->toBe('custom-slug');
    });

    it('returns redirect response', function (): void {
        $request = mockStoreMentorProgramRequest([
            'name'        => 'Test Program',
            'slug'        => 'test-program',
            'description' => 'Test Description',
            'cost'        => 100.0,
            'currency_id' => array_key_first($this->currencies),
        ]);

        $response = (new StoreMentorProgramPage)->handle($request);

        expect($response)->toBeInstanceOf(Response::class)
            ->and($response->getTargetUrl())->toBe(route('mentor-program.list'));
    });

});

function mockStoreMentorProgramRequest(array $data): StoreMentorProgramRequest
{
    $request = Mockery::mock(StoreMentorProgramRequest::class);
    $request->shouldReceive('validated')->andReturn($data);

    return $request;
}

function createAndAuthenticateMentorForStore(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    Auth::login($user);

    return $user;
}
