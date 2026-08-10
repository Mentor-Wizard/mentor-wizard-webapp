<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Modules\MentorProgram\Models\MentorProgram;
use Modules\MentorProgram\Policies\MentorProgramPolicy;

describe('MentorProgram model', function (): void {
    it('does not expose the dead mentorSession() accessor removed during the DDD migration', function (): void {
        expect(method_exists(MentorProgram::class, 'mentorSession'))->toBeFalse();
    });

    it('does not expose the dead mentorProfiles() accessor removed during the DDD migration', function (): void {
        expect(method_exists(MentorProgram::class, 'mentorProfiles'))->toBeFalse();
    });

    it('keeps the live calendarEvents() relation used by withCount() in ListMentorProgramPage', function (): void {
        expect(method_exists(MentorProgram::class, 'calendarEvents'))->toBeTrue();
    });

    it('resolves its policy to MentorProgramPolicy via both the #[UsePolicy] attribute and Gate::policy()', function (): void {
        expect(Gate::getPolicyFor(MentorProgram::class))->toBeInstanceOf(MentorProgramPolicy::class);
    });
});
