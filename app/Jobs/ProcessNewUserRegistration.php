<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Job that processes new user registration.
 * This job demonstrates a scenario where dd() or dump() cannot help with debugging
 * because the output is not visible in queue workers.
 */
class ProcessNewUserRegistration implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public array $metadata = [],
    ) {}

    /**
     * Execute the job.
     *
     * This job contains intentional bugs that require XDebug to debug:
     * 1. Complex nested array manipulation
     * 2. Silent failures in data processing
     * 3. Conditional logic that fails in specific scenarios
     */
    public function handle(): void
    {
        Log::info('Processing new user registration', ['user_id' => $this->user->getKey()]);

        // Initialize user preferences with default values
        $preferences = $this->buildUserPreferences();

        // Process metadata and merge with preferences
        // BUG: This logic has issues that are hard to debug with dd()/dump()
        $processedData = $this->processMetadata($preferences);

        // Update user with processed data
        // BUG: Incorrect key access causing silent failures
        $this->updateUserProfile($processedData);

        Log::info('User registration processing completed', ['user_id' => $this->user->getKey()]);
    }

    /**
     * Build default user preferences.
     */
    private function buildUserPreferences(): array
    {
        return [
            'notifications' => [
                'email' => true,
                'push'  => false,
                'sms'   => false,
            ],
            'privacy' => [
                'profile_visibility' => 'public',
                'show_email'         => false,
            ],
            'settings' => [
                'theme'    => 'light',
                'language' => 'en',
                'timezone' => 'UTC',
            ],
        ];
    }

    /**
     * Process metadata and merge with preferences.
     * BUG: Complex nested array manipulation with subtle errors.
     */
    private function processMetadata(array $preferences): array
    {
        $result = $preferences;

        // Process metadata if provided
        foreach ($this->metadata as $category => $values) {
            // BUG: Incorrect null coalescing - should check if key exists
            // This will fail silently when $category doesn't exist in $result
            $existing = $result[$category] ?? [];

            // BUG: array_merge doesn't work correctly with nested numeric arrays
            // This can cause data loss in specific scenarios
            $result[$category] = array_merge($existing, $values);

            // BUG: Attempting to access array keys that might not exist
            // This will throw errors in specific edge cases
            if (isset($result[$category]['override']) && $result[$category]['override'] === true) {
                $result[$category] = $values;
            }
        }

        // BUG: Incorrect validation - will pass invalid data
        // Should validate each nested level, not just top level
        throw_if(count($result) < 2, new RuntimeException('Invalid preferences structure'));

        return $result;
    }

    /**
     * Update user profile with processed data.
     * BUG: Incorrect key access causing silent failures.
     */
    private function updateUserProfile(array $data): void
    {
        // BUG: Assuming 'preferences' key exists, but it doesn't after processMetadata
        // This will create incorrect data structure
        $this->user->update([
            'preferences' => json_encode($data['preferences'] ?? $data),
        ]);

        // BUG: Attempting to log data that might not exist
        // Will fail when specific conditions are met
        if (isset($data['settings']['theme'])) {
            Log::info('User theme set', [
                'user_id' => $this->user->getKey(),
                'theme'   => $data['settings']['theme'],
                // BUG: Accessing potentially undefined nested array key
                'language' => $data['settings']['locale']['language'],
            ]);
        }
    }
}
