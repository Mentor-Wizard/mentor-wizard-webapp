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
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

mutates(UpdateMentorProgramPage::class);

describe('Update Mentor Program', function (): void {
    beforeEach(function (): void {
        DB::statement('ALTER SEQUENCE currencies_id_seq RESTART WITH 1');
        $this->seed(CurrencySeeder::class);
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));

        Auth::login($this->user);

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

    // ///OTHER TESTS/////
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

    // it('preserves mentor_id during update', function (): void {
    //     $updateData = [
    //         'name' => 'Updated Program',
    //         'slug' => 'updated-program',
    //         'description' => 'Updated Description',
    //         'cost' => 150.0,
    //         'currency_id' => 2,
    //         'mentor_id' => 999, // Attempting to change mentor_id
    //     ];

    //     $request = mockUpdateMentorProgramRequest($updateData);
    //     expect(fn () => (new UpdateMentorProgramPage)->handle($request, $anotherMentorProgram))
    //         ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class, 'Unauthorized action.');
    // });

    // it('validates required fields', function (): void {
    //     $updateData = [
    //         'name' => '', // Empty required field
    //         'slug' => '',
    //         'description' => null,
    //         'cost' => null,
    //         'currency_id' => null,
    //     ];

    //     $request = mockUpdateMentorProgramRequest($updateData);

    //     expect(fn () => (new UpdateMentorProgramPage)->handle($request, $this->mentorProgram))
    //         ->toThrow(ValidationException::class);
    // });

    // it('handles invalid currency_id', function (): void {
    //     $updateData = [
    //         'name' => 'Updated Program',
    //         'slug' => 'updated-program',
    //         'description' => 'Updated Description',
    //         'cost' => 150.0,
    //         'currency_id' => 999, // Non-existent currency
    //     ];

    //     $request = mockUpdateMentorProgramRequest($updateData);

    //     expect(fn () => (new UpdateMentorProgramPage)->handle($request, $this->mentorProgram))
    //         ->toThrow(ValidationException::class);
    // });

    // it('prevents duplicate slugs', function (): void {
    //     // Create another program with known slug
    //     MentorProgram::factory()->create(['slug' => 'existing-slug']);

    //     $updateData = [
    //         'name' => 'Updated Program',
    //         'slug' => 'existing-slug', // Attempting to use existing slug
    //         'description' => 'Updated Description',
    //         'cost' => 150.0,
    //         'currency_id' => 2,
    //     ];

    //     $request = mockUpdateMentorProgramRequest($updateData);

    //     expect(fn () => (new UpdateMentorProgramPage)->handle($request, $this->mentorProgram))
    //         ->toThrow(ValidationException::class);
    // });

    // it('accepts decimal costs', function (): void {
    //     $updateData = [
    //         'name' => 'Updated Program',
    //         'slug' => 'updated-program',
    //         'description' => 'Updated Description',
    //         'cost' => 150.99,
    //         'currency_id' => 2,
    //     ];

    //     $request = mockUpdateMentorProgramRequest($updateData);
    //     $action = new UpdateMentorProgramPage;

    //     $action->handle($request, $this->mentorProgram);
    //     $updatedProgram = $this->mentorProgram->fresh();

    //     expect($updatedProgram->cost)->toBe(150.99);
    // });

});

function mockUpdateMentorProgramRequest(array $data): UpdateMentorProgramRequest
{
    $request = Mockery::mock(UpdateMentorProgramRequest::class);
    $request->shouldReceive('validated')->andReturn($data);
    $request->shouldReceive('user')->andReturn(Auth::user());

    return $request;
}
