<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\RoleEnum;

mutates(RoleEnum::class);

describe('RoleEnum', function (): void {
    it('has the expected values', function (): void {
        expect(RoleEnum::USER->value)->toBe('user')
            ->and(RoleEnum::ADMIN->value)->toBe('admin')
            ->and(RoleEnum::SUPER_ADMIN->value)->toBe('superadmin')
            ->and(RoleEnum::MENTOR->value)->toBe('mentor')
            ->and(RoleEnum::MENTI->value)->toBe('menti')
            ->and(RoleEnum::COACH->value)->toBe('coach');
    });

    it('has the correct number of cases', function (): void {
        expect(RoleEnum::cases())->toHaveCount(6);
    });
});
