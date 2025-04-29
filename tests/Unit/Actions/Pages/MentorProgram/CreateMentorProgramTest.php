<?php

declare(strict_types=1);

use App\Actions\Pages\MentorProgram\CreateMentorProgramPage;
use App\Models\Currency;
use Database\Seeders\CurrencySeeder;
use Inertia\Response;

mutates(CreateMentorProgramPage::class);

describe('Create Mentor Program', function (): void {
    beforeEach(function (): void {
        DB::statement('ALTER SEQUENCE currencies_id_seq RESTART WITH 1');
        $this->seed(CurrencySeeder::class);
        $this->currencies = [
            1 => 'UAH',
            2 => 'USD',
            3 => 'EUR',
            4 => 'GBP',
        ];
    });

    it('renders the mentor program creation page with currencies', function (): void {
        $action = new CreateMentorProgramPage;

        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('MentorProgram/CreateOrEdit')
            ->and(Arr::get($resultData->getData(), 'page.props.currencies'))->toBe($this->currencies);
    });

    it('handles empty currencies table', function (): void {
        Currency::query()->delete();

        $action = new CreateMentorProgramPage;
        expect(fn (): Response => $action->handle())->toThrow(Exception::class, 'Currencies table is empty');
    });

    it('contains required page structure', function (): void {
        $action = new CreateMentorProgramPage;
        $result = $action->handle();
        $data = $result->toResponse(request())->getOriginalContent()->getData();

        expect($data)->toHaveKey('page')
            ->and($data['page'])->toHaveKeys(['component', 'props'])
            ->and($data['page']['props'])->toHaveKey('currencies');
    });

    it('preserves currency id-name mapping', function (): void {
        $action = new CreateMentorProgramPage;
        $result = $action->handle();
        $currencies = Arr::get($result->toResponse(request())->getOriginalContent()->getData(), 'page.props.currencies');

        expect($currencies)->toHaveCount(4)
            ->and($currencies[1])->toBe('UAH')
            ->and($currencies[2])->toBe('USD')
            ->and($currencies[3])->toBe('EUR')
            ->and($currencies[4])->toBe('GBP');
    });
});
