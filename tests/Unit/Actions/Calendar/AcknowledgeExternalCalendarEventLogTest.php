<?php

declare(strict_types=1);

use App\Actions\Calendar\ExternalCalendarLog\AcknowledgeExternalCalendarEventLog;
use App\Enums\ExternalCalendarEventLogTypeEnum;
use App\Models\ExternalCalendarEventLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;

mutates(AcknowledgeExternalCalendarEventLog::class);

describe('AcknowledgeExternalCalendarEventLog', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);

        $this->user = User::factory()->create();
    });

    it('updates the log type to ErrorStatusViewed', function (): void {
        $log = ExternalCalendarEventLog::factory()->create([
            'type' => ExternalCalendarEventLogTypeEnum::Error,
        ]);

        $action = new AcknowledgeExternalCalendarEventLog;
        $action->handle($log);

        $this->assertDatabaseHas(ExternalCalendarEventLog::class, [
            'id'   => $log->getKey(),
            'type' => ExternalCalendarEventLogTypeEnum::ErrorStatusViewed->value,
        ]);
    });

    it('returns a redirect response with a success flash message', function (): void {
        $log = ExternalCalendarEventLog::factory()->create([
            'type' => ExternalCalendarEventLogTypeEnum::Error,
        ]);

        $action = new AcknowledgeExternalCalendarEventLog;
        $response = $action->handle($log);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getSession()->get('success'))->toBe('Error acknowledged.');
    });
});
