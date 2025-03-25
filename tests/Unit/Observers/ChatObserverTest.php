<?php

declare(strict_types=1);

use App\Models\Chat;
use App\Observers\ChatObserver;
use Illuminate\Support\Facades\Log;

mutates(ChatObserver::class);

describe('ChatObserver', function () {
    beforeEach(function () {
        $this->chatObserver = new ChatObserver;
    });

    describe('saved method scenarios', function () {
        it('should not delete chat when at least one user ID is present', function () {
            $chat = Mockery::mock(Chat::class, function ($mock) {
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

        it('should delete chat when all user IDs are null', function () {
            $chat = Mockery::mock(Chat::class, function ($mock) {
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

        it('should log delete event with correct context', function () {
            $chat = Mockery::mock(Chat::class, function ($mock) {
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
                ->withArgs(function ($message, $context) {
                    expect($message)->toBe('Deleting chat because all user IDs are NULL.')
                        ->and($context)->toBe(['chat' => 42]);

                    return true;
                });

            $chat->shouldReceive('delete')->once();

            $this->chatObserver->saved($chat);
        });
    });
});
