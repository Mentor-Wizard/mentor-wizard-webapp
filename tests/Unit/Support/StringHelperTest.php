<?php

declare(strict_types=1);

use App\Support\StringHelper;

describe('StringHelper', function (): void {
    it('returns the original string if it is shorter than the maximum length', function (): void {
        $string = 'Hello, World!';
        $result = StringHelper::truncate($string, 20);

        expect($result)->toBe($string);
    });

    it('returns the original string if it is equal to the maximum length', function (): void {
        $string = 'Hello, World!';
        $result = StringHelper::truncate($string, 13);

        expect($result)->toBe($string);
    });

    it('truncates the string and appends an ellipsis if it is longer than the maximum length', function (): void {
        $string = 'Hello, World!';
        $result = StringHelper::truncate($string, 5);

        expect($result)->toBe('Hello...');
    });

    it('allows a custom ellipsis', function (): void {
        $string = 'Hello, World!';
        $result = StringHelper::truncate($string, 5, ' [...]');

        expect($result)->toBe('Hello [...]');
    });
});
