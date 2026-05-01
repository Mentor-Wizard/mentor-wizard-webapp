<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\ListMentorProfilePage;
use App\Filters\ExperienceLevelFilter;
use App\Filters\ProfileRateFilter;
use App\Filters\ProgramCostFilter;
use App\Filters\RatingFilter;
use App\Filters\TagLanguagesFilter;
use App\Filters\TagStacksFilter;
use App\Http\Requests\MentorProfile\ListMentorProfileRequest;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

mutates(ListMentorProfilePage::class);

describe('ListMentorProfilePage unit tests', function (): void {
    it('action class exists and has correct structure', function (): void {
        $action = new ListMentorProfilePage;

        expect($action)->toBeInstanceOf(ListMentorProfilePage::class)
            ->and(method_exists($action, 'handle'))->toBeTrue();

        $reflection = new ReflectionMethod($action, 'handle');
        expect($reflection->isPublic())->toBeTrue()
            ->and($reflection->getNumberOfParameters())->toBe(1);
    });

    it('handle method accepts ListMentorProfileRequest as its only parameter', function (): void {
        $reflection = new ReflectionMethod(ListMentorProfilePage::class, 'handle');
        $params = $reflection->getParameters();

        expect($params)->toHaveCount(1)
            ->and($params[0]->getType()->getName())->toBe(ListMentorProfileRequest::class);
    });

    it('has private applyCategory method with correct signature', function (): void {
        $reflection = new ReflectionMethod(ListMentorProfilePage::class, 'applyCategory');

        expect($reflection->isPrivate())->toBeTrue()
            ->and($reflection->getNumberOfParameters())->toBe(2);
    });

    it('handle method returns Inertia Response', function (): void {
        $reflection = new ReflectionMethod(ListMentorProfilePage::class, 'handle');
        $returnType = $reflection->getReturnType();

        expect($returnType)->not->toBeNull()
            ->and($returnType->getName())->toBe(Response::class);
    });

    it('uses AsController trait', function (): void {
        $action = new ListMentorProfilePage;
        $traits = class_uses_recursive($action);

        expect($traits)->toContain(AsController::class)
            ->and(method_exists($action, '__invoke'))->toBeTrue()
            ->and(method_exists($action, 'getMiddleware'))->toBeTrue();
    });

    it('has all required filter classes defined', function (): void {
        expect(class_exists(ProfileRateFilter::class))->toBeTrue()
            ->and(class_exists(ProgramCostFilter::class))->toBeTrue()
            ->and(class_exists(TagLanguagesFilter::class))->toBeTrue()
            ->and(class_exists(TagStacksFilter::class))->toBeTrue()
            ->and(class_exists(ExperienceLevelFilter::class))->toBeTrue()
            ->and(class_exists(RatingFilter::class))->toBeTrue()
            ->and(new ProfileRateFilter)->toBeInstanceOf(ProfileRateFilter::class)
            ->and(new ProgramCostFilter)->toBeInstanceOf(ProgramCostFilter::class)
            ->and(new TagLanguagesFilter)->toBeInstanceOf(TagLanguagesFilter::class)
            ->and(new TagStacksFilter)->toBeInstanceOf(TagStacksFilter::class)
            ->and(new ExperienceLevelFilter)->toBeInstanceOf(ExperienceLevelFilter::class)
            ->and(new RatingFilter)->toBeInstanceOf(RatingFilter::class);
    });

    it('has correct namespace and imports', function (): void {
        $reflection = new ReflectionClass(ListMentorProfilePage::class);

        expect($reflection->getNamespaceName())->toBe('App\Actions\Pages\Profile')
            ->and($reflection->getName())->toBe(ListMentorProfilePage::class)
            ->and($reflection->isInstantiable())->toBeTrue();
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
