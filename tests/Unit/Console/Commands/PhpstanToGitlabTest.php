<?php

declare(strict_types=1);

use App\Console\Commands\PhpstanToGitlab;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

mutates(PhpstanToGitlab::class);

beforeEach(function () {
    File::shouldReceive('exists')->andReturn(false)->byDefault();
    File::shouldReceive('get')->byDefault();
    File::shouldReceive('put')->byDefault();
});

it('returns an error when the file is not found', function () {
    File::shouldReceive('exists')->with('nonexistent.json')->once()->andReturn(false);
    File::shouldNotReceive('get');
    File::shouldNotReceive('put');

    $this->artisan('phpstan:convert', [
        'inputFile' => 'nonexistent.json',
    ])
        ->expectsOutput('File not found: nonexistent.json')
        ->assertExitCode(SymfonyCommand::FAILURE);
});

it('generates a valid GitLab report from a PHPStan file', function () {
    $inputJson = json_encode([
        'files' => [
            '/var/www/app/SomeClass.php' => [
                'messages' => [
                    [
                        'message' => 'Some error found',
                        'line' => 10,
                    ],
                    [
                        'message' => 'Another error found',
                        'line' => 25,
                    ],
                ],
            ],
        ],
    ]);

    $expectedOutputJson = json_encode([
        [
            'description' => 'Some error found',
            'fingerprint' => md5('app/SomeClass.php'.'Some error found'),
            'severity' => 'major',
            'location' => [
                'path' => 'app/SomeClass.php',
                'lines' => [
                    'begin' => 10,
                ],
            ],
        ],
        [
            'description' => 'Another error found',
            'fingerprint' => md5('app/SomeClass.php'.'Another error found'),
            'severity' => 'major',
            'location' => [
                'path' => 'app/SomeClass.php',
                'lines' => [
                    'begin' => 25,
                ],
            ],
        ],
    ], JSON_PRETTY_PRINT);

    File::shouldReceive('exists')->with('input.json')->once()->andReturn(true);
    File::shouldReceive('get')->with('input.json')->once()->andReturn($inputJson);
    File::shouldReceive('put')->with('codequality.json', $expectedOutputJson)->once();

    $this->artisan('phpstan:convert', [
        'inputFile' => 'input.json',
        'outputFile' => 'codequality.json',
    ])
        ->expectsOutput('GitLab Code Quality report generated: codequality.json')
        ->assertExitCode(SymfonyCommand::SUCCESS);
});

it('generates a GitLab report from an empty PHPStan result', function () {
    $emptyJson = json_encode(['files' => []]);

    File::shouldReceive('exists')->with('empty.json')->once()->andReturn(true);
    File::shouldReceive('get')->with('empty.json')->once()->andReturn($emptyJson);
    File::shouldReceive('put')->with('codequality.json', json_encode([], JSON_PRETTY_PRINT))->once();

    $this->artisan('phpstan:convert', [
        'inputFile' => 'empty.json',
    ])
        ->expectsOutput('GitLab Code Quality report generated: codequality.json')
        ->assertExitCode(SymfonyCommand::SUCCESS);
});

it('handles missing messages and line keys in PHPStan results correctly', function () {
    $noMessageKeyJson = json_encode([
        'files' => [
            '/var/www/app/FileWithoutMessages.php' => [],
            '/var/www/app/FileWithEmptyMessages.php' => [
                'messages' => [],
            ],
            '/var/www/app/FileWithMessageNoLine.php' => [
                'messages' => [
                    ['message' => 'Error without line'],
                ],
            ],
        ],
    ]);

    $expectedOutput = json_encode([
        [
            'description' => 'Error without line',
            'fingerprint' => md5('app/FileWithMessageNoLine.php'.'Error without line'),
            'severity' => 'major',
            'location' => [
                'path' => 'app/FileWithMessageNoLine.php',
                'lines' => ['begin' => 1],
            ],
        ],
    ], JSON_PRETTY_PRINT);

    File::shouldReceive('exists')->with('no_message_key.json')->once()->andReturn(true);
    File::shouldReceive('get')->with('no_message_key.json')->once()->andReturn($noMessageKeyJson);
    File::shouldReceive('put')->with('codequality.json', $expectedOutput)->once();

    $this->artisan('phpstan:convert', [
        'inputFile' => 'no_message_key.json',
    ])
        ->expectsOutput('GitLab Code Quality report generated: codequality.json')
        ->assertExitCode(SymfonyCommand::SUCCESS);
});

it('throws an exception for malformed JSON in the input file', function () {
    File::shouldReceive('exists')->with('invalid_json.json')->once()->andReturn(true);
    File::shouldReceive('get')->with('invalid_json.json')->once()->andReturn('{invalid json]');

    $command = app(PhpstanToGitlab::class);
    $command->setLaravel(app());

    $this->artisan('phpstan:convert', [
        'inputFile' => 'invalid_json.json',
    ]);
})->throws(JsonException::class);

it('uses correct json_decode parameters explicitly', function () {
    $validJson = '{"files":[]}';

    File::shouldReceive('exists')->with('valid_input.json')->once()->andReturn(true);

    File::shouldReceive('get')
        ->once()
        ->with('valid_input.json')
        ->andReturn($validJson);

    File::shouldReceive('put')
        ->once()
        ->with('codequality.json', '[]');

    $this->artisan('phpstan:convert', [
        'inputFile' => 'valid_input.json',
    ])->assertExitCode(SymfonyCommand::SUCCESS);
});
