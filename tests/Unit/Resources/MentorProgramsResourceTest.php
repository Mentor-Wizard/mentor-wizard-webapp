<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Http\Resources\MentorProgramsResource;
use App\Models\Currency;
use App\Models\MentorProgram;
use App\Models\MentorProgramBlock;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

covers(MentorProgramsResource::class);

describe('Mentor Programs Resource', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
    });

    it('correctly transforms user resource', function (): void {
        $user = User::factory()->create([
            'username' => 'Test User',
            'email'    => 'test@example.com',
        ]);

        $user->refresh();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $currency = Currency::query()->first();
        $program = MentorProgram::factory()->create([
            'mentor_id'        => $user->id,
            'name'             => 'name program',
            'slug'             => 'slug program',
            'description'      => 'description program',
            'cost'             => 1.1,
            'currency_id'      => $currency->id,
        ]);

        MentorProgramBlock::factory()->count(5)->create([
            'mentor_program_id'        => $program->id,
        ]);

        $resource = MentorProgramsResource::make($program)->resolve();

        expect($resource)->toMatchArray([
            'id'          => $program->id,
            'name'        => 'name program',
            'slug'        => 'slug program',
            'description' => 'description program',
            'cost'        => 1.1,
            'currency'    => '₴',
        ])
            ->and($resource['blocks'])->toBeArray()
            ->toHaveCount(5)
            ->each(fn ($block) => expect($block->value)->toHaveKeys([
                'id',
                'name',
                'description',
            ]));
    });
});
