<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\RoleGuardEnum;

mutates(RoleGuardEnum::class);

describe('RoleGuardEnum', function (): void {
    it('has the expected values', function (): void {
        expect(RoleGuardEnum::USER->value)->toBe('user')
            ->and(RoleGuardEnum::ADMIN->value)->toBe('admin')
            ->and(RoleGuardEnum::SUPER_ADMIN->value)->toBe('superadmin')
            ->and(RoleGuardEnum::MENTOR->value)->toBe('mentor')
            ->and(RoleGuardEnum::MENTI->value)->toBe('menti')
            ->and(RoleGuardEnum::COACH->value)->toBe('coach');
    });

    it('has the correct number of cases', function (): void {
        expect(RoleGuardEnum::cases())->toHaveCount(6);
    });
});
