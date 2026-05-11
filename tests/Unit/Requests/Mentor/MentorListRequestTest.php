<?php

declare(strict_types=1);

use App\Http\Requests\Mentor\MentorListRequest;
use Illuminate\Support\Facades\Validator;

mutates(MentorListRequest::class);

describe('MentorListRequest validation rules', function (): void {
    beforeEach(function (): void {
        $this->request = new MentorListRequest;
    });

    it('passes when no parameters are provided', function (): void {
        $validator = Validator::make([], $this->request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('authorizes all requests', function (): void {
        expect($this->request->authorize())->toBeTrue();
    });

    describe('filter.stacks', function (): void {
        it('passes with a valid stacks string', function (): void {
            $validator = Validator::make(
                ['filter' => ['stacks' => 'Laravel,React']],
                $this->request->rules(),
            );

            expect($validator->passes())->toBeTrue();
        });

        it('fails when stacks exceeds 500 characters', function (): void {
            $validator = Validator::make(
                ['filter' => ['stacks' => str_repeat('a', 501)]],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('filter.stacks'))->toBeTrue();
        });

        it('passes when stacks is exactly 500 characters', function (): void {
            $validator = Validator::make(
                ['filter' => ['stacks' => str_repeat('a', 500)]],
                $this->request->rules(),
            );

            expect($validator->passes())->toBeTrue();
        });
    });

    describe('filter.languages', function (): void {
        it('passes with a valid languages string', function (): void {
            $validator = Validator::make(
                ['filter' => ['languages' => 'PHP,JavaScript']],
                $this->request->rules(),
            );

            expect($validator->passes())->toBeTrue();
        });

        it('fails when languages exceeds 500 characters', function (): void {
            $validator = Validator::make(
                ['filter' => ['languages' => str_repeat('b', 501)]],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('filter.languages'))->toBeTrue();
        });
    });

    describe('filter.experience', function (): void {
        it('passes with valid experience values', function (): void {
            foreach (['entry', 'mid', 'senior', 'expert'] as $level) {
                $validator = Validator::make(
                    ['filter' => ['experience' => $level]],
                    $this->request->rules(),
                );

                expect($validator->passes())->toBeTrue(sprintf("Expected '%s' to pass", $level));
            }
        });

        it('fails with an invalid experience value', function (): void {
            $validator = Validator::make(
                ['filter' => ['experience' => 'beginner']],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('filter.experience'))->toBeTrue();
        });

        it('fails with an arbitrary string experience value', function (): void {
            $validator = Validator::make(
                ['filter' => ['experience' => 'invalid_level']],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('filter.experience'))->toBeTrue();
        });
    });

    describe('filter.rating', function (): void {
        it('passes with rating of 1', function (): void {
            $validator = Validator::make(
                ['filter' => ['rating' => 1]],
                $this->request->rules(),
            );

            expect($validator->passes())->toBeTrue();
        });

        it('passes with rating of 5', function (): void {
            $validator = Validator::make(
                ['filter' => ['rating' => 5]],
                $this->request->rules(),
            );

            expect($validator->passes())->toBeTrue();
        });

        it('fails when rating is below 1', function (): void {
            $validator = Validator::make(
                ['filter' => ['rating' => 0]],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('filter.rating'))->toBeTrue();
        });

        it('fails when rating exceeds 5', function (): void {
            $validator = Validator::make(
                ['filter' => ['rating' => 6]],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('filter.rating'))->toBeTrue();
        });

        it('fails when rating is not numeric', function (): void {
            $validator = Validator::make(
                ['filter' => ['rating' => 'high']],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('filter.rating'))->toBeTrue();
        });
    });

    describe('filter.rate', function (): void {
        it('passes with valid min and max rate', function (): void {
            $validator = Validator::make(
                ['filter' => ['rate' => ['min' => 10, 'max' => 100]]],
                $this->request->rules(),
            );

            expect($validator->passes())->toBeTrue();
        });

        it('passes with min only', function (): void {
            $validator = Validator::make(
                ['filter' => ['rate' => ['min' => 50]]],
                $this->request->rules(),
            );

            expect($validator->passes())->toBeTrue();
        });

        it('fails with max only when min is absent (gte rule requires referenced field)', function (): void {
            $validator = Validator::make(
                ['filter' => ['rate' => ['max' => 200]]],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('filter.rate.max'))->toBeTrue();
        });

        it('fails when rate.min is negative', function (): void {
            $validator = Validator::make(
                ['filter' => ['rate' => ['min' => -1]]],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('filter.rate.min'))->toBeTrue();
        });

        it('fails when rate.max exceeds 10000', function (): void {
            $validator = Validator::make(
                ['filter' => ['rate' => ['max' => 10001]]],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('filter.rate.max'))->toBeTrue();
        });

        it('fails when rate.max is less than rate.min', function (): void {
            $validator = Validator::make(
                ['filter' => ['rate' => ['min' => 200, 'max' => 100]]],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('filter.rate.max'))->toBeTrue();
        });

        it('passes when rate.max equals rate.min', function (): void {
            $validator = Validator::make(
                ['filter' => ['rate' => ['min' => 100, 'max' => 100]]],
                $this->request->rules(),
            );

            expect($validator->passes())->toBeTrue();
        });
    });

    describe('filter.cost', function (): void {
        it('passes with valid min and max cost', function (): void {
            $validator = Validator::make(
                ['filter' => ['cost' => ['min' => 0, 'max' => 5000]]],
                $this->request->rules(),
            );

            expect($validator->passes())->toBeTrue();
        });

        it('fails when cost.max is less than cost.min', function (): void {
            $validator = Validator::make(
                ['filter' => ['cost' => ['min' => 500, 'max' => 100]]],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('filter.cost.max'))->toBeTrue();
        });

        it('fails when cost.min is negative', function (): void {
            $validator = Validator::make(
                ['filter' => ['cost' => ['min' => -10]]],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('filter.cost.min'))->toBeTrue();
        });
    });

    describe('sort', function (): void {
        it('passes with allowed sort values', function (): void {
            $allowedSorts = [
                'id', '-id',
                'rate', '-rate',
                'experience_started_at', '-experience_started_at',
            ];

            foreach ($allowedSorts as $sort) {
                $validator = Validator::make(
                    ['sort' => $sort],
                    $this->request->rules(),
                );

                expect($validator->passes())->toBeTrue(sprintf("Expected sort '%s' to pass", $sort));
            }
        });

        it('fails with an invalid sort value', function (): void {
            $validator = Validator::make(
                ['sort' => 'name'],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('sort'))->toBeTrue();
        });

        it('fails with an arbitrary sort string', function (): void {
            $validator = Validator::make(
                ['sort' => 'invalid_field'],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('sort'))->toBeTrue();
        });
    });

    describe('page', function (): void {
        it('passes with a valid page number', function (): void {
            $validator = Validator::make(
                ['page' => 2],
                $this->request->rules(),
            );

            expect($validator->passes())->toBeTrue();
        });

        it('fails when page is 0', function (): void {
            $validator = Validator::make(
                ['page' => 0],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('page'))->toBeTrue();
        });

        it('fails when page is negative', function (): void {
            $validator = Validator::make(
                ['page' => -1],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('page'))->toBeTrue();
        });

        it('fails when page is not an integer', function (): void {
            $validator = Validator::make(
                ['page' => 'abc'],
                $this->request->rules(),
            );

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('page'))->toBeTrue();
        });

        it('passes when page is 1', function (): void {
            $validator = Validator::make(
                ['page' => 1],
                $this->request->rules(),
            );

            expect($validator->passes())->toBeTrue();
        });
    });

    describe('combined valid request', function (): void {
        it('passes with all valid filter parameters combined', function (): void {
            $validator = Validator::make(
                [
                    'filter' => [
                        'stacks'     => 'Laravel,React',
                        'languages'  => 'PHP,JavaScript',
                        'experience' => 'senior',
                        'rating'     => 4,
                        'rate'       => ['min' => 50, 'max' => 150],
                        'cost'       => ['min' => 0, 'max' => 1000],
                    ],
                    'sort' => '-rate',
                    'page' => 1,
                ],
                $this->request->rules(),
            );

            expect($validator->passes())->toBeTrue();
        });
    });
});
