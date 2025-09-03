<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\ListMentorProfilePage;
use App\Filters\ProfileRateFilter;
use App\Filters\ProgramCostFilter;
use App\Filters\TagLanguagesFilter;
use App\Filters\TagStacksFilter;

mutates(ListMentorProfilePage::class);

describe('ListMentorProfilePage unit tests', function (): void {
    it('action class exists and has correct structure', function (): void {
        $action = new ListMentorProfilePage;

        expect($action)->toBeInstanceOf(ListMentorProfilePage::class);
        expect(method_exists($action, 'handle'))->toBeTrue();

        $reflection = new ReflectionMethod($action, 'handle');
        expect($reflection->isPublic())->toBeTrue();
        expect($reflection->getNumberOfParameters())->toBe(0);
    });

    it('uses AsController trait', function (): void {
        $action = new ListMentorProfilePage;
        $traits = class_uses_recursive($action);

        expect($traits)->toContain(Lorisleiva\Actions\Concerns\AsController::class);

        expect(method_exists($action, '__invoke'))->toBeTrue();
        expect(method_exists($action, 'getMiddleware'))->toBeTrue();
    });

    it('has all required filter classes defined', function (): void {
        expect(class_exists(ProfileRateFilter::class))->toBeTrue();
        expect(class_exists(ProgramCostFilter::class))->toBeTrue();
        expect(class_exists(TagLanguagesFilter::class))->toBeTrue();
        expect(class_exists(TagStacksFilter::class))->toBeTrue();

        // Test that they can be instantiated without errors
        expect(new ProfileRateFilter)->toBeInstanceOf(ProfileRateFilter::class);
        expect(new ProgramCostFilter)->toBeInstanceOf(ProgramCostFilter::class);
        expect(new TagLanguagesFilter)->toBeInstanceOf(TagLanguagesFilter::class);
        expect(new TagStacksFilter)->toBeInstanceOf(TagStacksFilter::class);
    });

    it('has correct namespace and imports', function (): void {
        $reflection = new ReflectionClass(ListMentorProfilePage::class);

        expect($reflection->getNamespaceName())->toBe('App\Actions\Pages\Profile');
        expect($reflection->getName())->toBe(ListMentorProfilePage::class);
        expect($reflection->isInstantiable())->toBeTrue();
    });

    it('handle method returns expected type hint structure', function (): void {
        $reflection = new ReflectionMethod(ListMentorProfilePage::class, 'handle');

        expect($reflection->getName())->toBe('handle');
        expect($reflection->isStatic())->toBeFalse();
        expect($reflection->hasReturnType())->toBeFalse();
    });

    it('action can be instantiated without dependencies', function (): void {
        $reflection = new ReflectionClass(ListMentorProfilePage::class);
        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            expect($constructor->getNumberOfRequiredParameters())->toBe(0);
        }

        $action = new ListMentorProfilePage;
        expect($action)->toBeInstanceOf(ListMentorProfilePage::class);
    });
});
