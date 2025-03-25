<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use JsonException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class PhpstanToGitlab extends Command
{
    public const int DEPTH = 512;

    protected $signature = 'phpstan:convert {inputFile} {outputFile=codequality.json}';

    protected $description = 'Convert PHPStan JSON output to GitLab Code Quality format';

    /**
     * @throws FileNotFoundException
     * @throws JsonException
     */
    public function handle(): int
    {
        $inputFile = $this->argument('inputFile');
        $outputFile = $this->argument('outputFile');

        if (! File::exists($inputFile)) {
            $this->error("File not found: {$inputFile}");

            return SymfonyCommand::FAILURE;
        }

        $phpstanData = json_decode(File::get($inputFile), true, self::DEPTH, JSON_THROW_ON_ERROR);

        $gitlabReport = collect(Arr::get($phpstanData, 'files', []))
            ->flatMap(function (array $issues, string $filePath): Collection {
                $sanitizedFilePath = preg_replace('/^\/var\/www\//', '', $filePath);

                return collect(Arr::get($issues, 'messages'))->map(
                    function (array $message) use ($sanitizedFilePath): array {
                        return [
                            'description' => Arr::get($message, 'message'),
                            'fingerprint' => md5($sanitizedFilePath.Arr::get($message, 'message')),
                            'severity' => 'major',
                            'location' => [
                                'path' => $sanitizedFilePath,
                                'lines' => [
                                    'begin' => Arr::get($message, 'line', 1),
                                ],
                            ],
                        ];
                    }
                );
            })
            ->values()
            ->toArray();

        File::put($outputFile, json_encode($gitlabReport, JSON_PRETTY_PRINT));

        $this->info("GitLab Code Quality report generated: {$outputFile}");

        return SymfonyCommand::SUCCESS;
    }
}
