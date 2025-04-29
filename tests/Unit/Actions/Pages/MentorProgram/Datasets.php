<?php

declare(strict_types=1);

dataset('validMentorProgramData', [
    'full valid data' => fn (): array => [
        'name'        => 'Valid Program Name',
        'slug'        => 'valid-program-slug',
        'description' => 'Valid program description',
        'cost'        => 99.99,
        'currency_id' => 1,
    ],
    'minimal valid data' => fn (): array => [
        'name'        => 'Min Program',
        'slug'        => 'min-program',
        'description' => 'Min description',
        'cost'        => 0,
        'currency_id' => 1,
    ],
]);

dataset('invalidMentorProgramData', [
    'empty name' => fn (): array => [
        [
            'name'        => '',
            'slug'        => 'valid-slug',
            'description' => 'Valid description',
            'cost'        => 99.99,
            'currency_id' => 1,
        ],
        'errorField' => 'name',
    ],
    'too long name' => fn (): array => [
        [
            'name'        => str_repeat('a', 256),
            'slug'        => 'valid-slug',
            'description' => 'Valid description',
            'cost'        => 99.99,
            'currency_id' => 1,
        ],
        'errorField' => 'name',
    ],
    'invalid slug format' => fn (): array => [
        [
            'name'        => 'Valid Name',
            'slug'        => 123,
            'description' => 'Valid description',
            'cost'        => 99.99,
            'currency_id' => 1,
        ],
        'errorField' => 'slug',
    ],
    'negative cost' => fn (): array => [
        [
            'name'        => 'Valid Name',
            'slug'        => 'valid-slug',
            'description' => 'Valid description',
            'cost'        => -1,
            'currency_id' => 1,
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
]);
