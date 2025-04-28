<?php

declare(strict_types=1);

use App\Actions\Pages\MentorProgram\CreateMentorProgramPage;
use Database\Seeders\CurrencySeeder;
use Inertia\Response;
use Mockery\MockInterface;

describe('Create Mentor Program', function (): void {
    beforeEach(function (): void {
        $this->seed(CurrencySeeder::class);
        $this->currencies = collect([
            1 => 'UAH',
            2 => 'USD',
            3 => 'EUR',
            4 => 'GBP',
        ]);
        // $this->mock(Currency::class, function (MockInterface $mock) {
        //     $queryMock = Mockery::mock('query');
        //     $queryMock->shouldReceive('pluck')
        //         ->once()
        //         ->with('name', 'id')
        //         ->andReturn($this->currencies);

        //     $mock->shouldReceive('query')
        //         ->once()
        //         ->andReturn($queryMock);
        // });
        // $this->mock(Currency::class, function (MockInterface $mock) {
        //     $mock->shouldReceive('query->pluck')
        //         ->once()
        //         ->with('name', 'id')
        //         ->andReturn($this->currencies);
        // });
    });

    it('renders the mentor program creation page with currencies', function (): void {
        $action = new CreateMentorProgramPage;

        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('MentorProgram/CreateOrEdit')
            ->and(Arr::get($resultData->getData(), 'page.props.currencies'))->toBe($this->currencies->toArray());
    });
});
