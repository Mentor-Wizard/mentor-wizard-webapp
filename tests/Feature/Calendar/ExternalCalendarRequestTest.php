<?php

declare(strict_types=1);

use App\Enums\CalendarProviderEnum;
use App\Http\Requests\Calendar\ExternalCalendarConnectRedirectRequest;
use App\Http\Requests\Calendar\ExternalCalendarRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

mutates(ExternalCalendarRequest::class);

describe('ExternalCalendarRequest', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);

        $this->user = User::factory()->create();
    });

    describe('authorize', function (): void {
        it('returns true when a user is present on the request', function (): void {
            $request = new ExternalCalendarConnectRedirectRequest;
            $request->setUserResolver(fn (): User => $this->user);

            expect($request->authorize())->toBeTrue();
        });

        it('returns false when no user is present on the request', function (): void {
            $request = new ExternalCalendarConnectRedirectRequest;
            $request->setUserResolver(fn (): null => null);

            expect($request->authorize())->toBeFalse();
        });
    });

    describe('resolveProvider', function (): void {
        it('returns the matching enum for a valid provider route parameter', function (): void {
            $request = new ExternalCalendarConnectRedirectRequest;
            $routeMock = Mockery::mock(Route::class);
            $routeMock->shouldReceive('parameter')->andReturn('google');
            $request->setRouteResolver(fn (): Route => $routeMock);

            expect($request->resolveProvider())->toBe(CalendarProviderEnum::Google);
        });

        it('returns null for an unrecognized provider value', function (): void {
            $request = new ExternalCalendarConnectRedirectRequest;
            $routeMock = Mockery::mock(Route::class);
            $routeMock->shouldReceive('parameter')->andReturn('unknown-provider');
            $request->setRouteResolver(fn (): Route => $routeMock);

            expect($request->resolveProvider())->toBeNull();
        });

        it('returns null when no route is bound to the request', function (): void {
            $request = new ExternalCalendarConnectRedirectRequest;
            $request->setRouteResolver(fn (): null => null);

            expect($request->resolveProvider())->toBeNull();
        });
    });

    describe('failedValidation', function (): void {
        it('redirects to profile.edit with the first validation error as a flash message', function (): void {
            // ExternalCalendarRetrySyncRequest::withValidator adds error for invalid provider,
            // then calls ExternalCalendarRequest::failedValidation (no override in that subclass).
            actingAs($this->user);

            $response = post(route('external-calendar.retry', ['provider' => 'invalid_provider']));

            $response->assertRedirectToRoute('profile.edit');
            $response->assertSessionHas('error');
        });
    });

    describe('failedAuthorization', function (): void {
        it('throws HttpResponseException that redirects to profile.edit', function (): void {
            $request = new ExternalCalendarConnectRedirectRequest;

            try {
                $reflection = new ReflectionClass($request);
                $method = $reflection->getMethod('failedAuthorization');
                $method->invoke($request);

                expect(false)->toBeTrue('Expected HttpResponseException was not thrown');
            } catch (HttpResponseException $httpResponseException) {
                $response = $httpResponseException->getResponse();

                expect($response->getStatusCode())->toBe(302)
                    ->and($response->headers->get('Location'))->toContain(route('profile.edit'));
            }
        });
    });
});
