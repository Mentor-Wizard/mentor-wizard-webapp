<?php

declare(strict_types=1);

use App\Models\Chat;
use App\Models\MentorSession;
use App\Models\CalendarEvent;
use App\Observers\ChatObserver;
use App\Observers\CalendarEventObserver;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Log;

mutates(ChatObserver::class);

describe('ChatObserver', function (): void {
    beforeEach(function (): void {
        $this->chatObserver = new ChatObserver;
    });

    describe('saved method scenarios', function (): void {
        it('should not delete chat when at least one user ID is present', function (): void {
            $chat = Mockery::mock(Chat::class, function ($mock): void {
                $mock->shouldReceive('getAttribute')
                    ->with('mentor_id')->andReturn(1)
                    ->shouldReceive('getAttribute')
                    ->with('menti_id')->andReturn(null)
                    ->shouldReceive('getAttribute')
                    ->with('coach_id')->andReturn(null);
            });

            Log::shouldReceive('info')->never();
            $chat->shouldReceive('delete')->never();

            $this->chatObserver->saved($chat);
        });

        it('should delete chat when all user IDs are null', function (): void {
            $chat = Mockery::mock(Chat::class, function ($mock): void {
                $mock->shouldReceive('getKey')->once()->andReturn(1);
                $mock->shouldReceive('getAttribute')
                    ->with('mentor_id')->andReturn(null)
                    ->shouldReceive('getAttribute')
                    ->with('menti_id')->andReturn(null)
                    ->shouldReceive('getAttribute')
                    ->with('coach_id')->andReturn(null);
            });

            Log::shouldReceive('info')
                ->once()
                ->with('Deleting chat because all user IDs are NULL.', ['chat' => 1]);

            $chat->shouldReceive('delete')->once();

            $this->chatObserver->saved($chat);
        });

        it('should log delete event with correct context', function (): void {
            $chat = Mockery::mock(Chat::class, function ($mock): void {
                $mock->shouldReceive('getKey')->once()->andReturn(42);
                $mock->shouldReceive('getAttribute')
                    ->with('mentor_id')->andReturn(null)
                    ->shouldReceive('getAttribute')
                    ->with('menti_id')->andReturn(null)
                    ->shouldReceive('getAttribute')
                    ->with('coach_id')->andReturn(null);
            });

            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($message, $context): true {
                    expect($message)->toBe('Deleting chat because all user IDs are NULL.')
                        ->and($context)->toBe(['chat' => 42]);

                    return true;
                });

            $chat->shouldReceive('delete')->once();

            $this->chatObserver->saved($chat);
        });
    });
});

describe('CalendarEventObserver', function (): void {
    beforeEach(function (): void {
        $this->observer = new CalendarEventObserver;
    });

    it('creates a mentor session when status changes to CONFIRMED and host exists', function (): void {
        $host = User::factory()->create();

        $event = Mockery::mock(CalendarEvent::class, function ($mock) use ($host): void {
            $mock->shouldReceive('wasChanged')->with('status')->andReturn(true);
            $mock->shouldReceive('getAttribute')->with('status')->andReturn(CalendarEventStatusEnum::CONFIRMED->value);
            $mock->shouldReceive('getAttribute')->with('mentor_program_id')->andReturn(123);

            $relation = Mockery::mock(stdClass::class);
            $relation->shouldReceive('wherePivot')->with('role', CalendarEventRoleEnum::HOST->value)->andReturnSelf();
            $relation->shouldReceive('first')->andReturn($host);

            $mock->shouldReceive('calendarEventUsers')->andReturn($relation);
        });

        expect(MentorSession::query()->count())->toBe(0);
        $this->observer->updated($event);

        expect(MentorSession::query()->count())->toBe(1)
            ->and(MentorSession::query()->first()->mentor_id)->toBe((int) $host->getKey());
    });

    it('does not create session when status was not changed', function (): void {
        $event = Mockery::mock(CalendarEvent::class, function ($mock): void {
            $mock->shouldReceive('wasChanged')->with('status')->andReturn(false);
        });

        $existing = MentorSession::query()->count();
        $this->observer->updated($event);
        expect(MentorSession::query()->count())->toBe($existing);
    });

    it('does not create session when mentor_program_id is missing', function (): void {
        $event = Mockery::mock(CalendarEvent::class, function ($mock): void {
            $mock->shouldReceive('wasChanged')->with('status')->andReturn(true);
            $mock->shouldReceive('getAttribute')->with('status')->andReturn(CalendarEventStatusEnum::CONFIRMED->value);
            $mock->shouldReceive('getAttribute')->with('mentor_program_id')->andReturn(null);
        });

        $existing = MentorSession::query()->count();
        $this->observer->updated($event);
        expect(MentorSession::query()->count())->toBe($existing);
    });

    it('does not create session when host user is not found', function (): void {
        $event = Mockery::mock(CalendarEvent::class, function ($mock): void {
            $mock->shouldReceive('wasChanged')->with('status')->andReturn(true);
            $mock->shouldReceive('getAttribute')->with('status')->andReturn(CalendarEventStatusEnum::CONFIRMED->value);
            $mock->shouldReceive('getAttribute')->with('mentor_program_id')->andReturn(55);

            $relation = Mockery::mock(stdClass::class);
            $relation->shouldReceive('wherePivot')->with('role', CalendarEventRoleEnum::HOST->value)->andReturnSelf();
            $relation->shouldReceive('first')->andReturn(null);
            $mock->shouldReceive('calendarEventUsers')->andReturn($relation);
        });

        $existing = MentorSession::query()->count();
        $this->observer->updated($event);
        expect(MentorSession::query()->count())->toBe($existing);
    });
});
