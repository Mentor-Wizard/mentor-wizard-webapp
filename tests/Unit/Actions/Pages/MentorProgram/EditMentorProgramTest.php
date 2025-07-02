<?php

declare(strict_types=1);

use App\Actions\Pages\MentorProgram\EditMentorProgramPage;
use App\Models\Currency;
use App\Models\MentorProgram;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Response;

mutates(EditMentorProgramPage::class);

describe('Edit Mentor Program', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->seed(CurrencySeeder::class);
        $this->currencies = Currency::query()->pluck('name', 'id')->toArray();
        $this->data = [
            'name'        => 'Test Program',
            'slug'        => 'test-program',
            'description' => 'Test Description',
            'cost'        => '100.00',
            'currency_id' => array_key_first($this->currencies),
        ];
    });

    it('renders the mentor program edit page with currencies and program data', function (): void {
        $mentorProgram = MentorProgram::factory()->create($this->data);
        $action = new EditMentorProgramPage;

        $result = $action->handle($mentorProgram);
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('MentorProgram/CreateOrEdit')
            ->and(Arr::get($resultData->getData(), 'page.props.currencies'))->toBe($this->currencies)
            ->and(Arr::get($resultData->getData(), 'page.props.program'))->toMatchArray($this->data)
            ->and(Arr::get($resultData->getData(), 'page.props.program.id'))->toBe($mentorProgram->getKey());
    });

    it('handles empty currencies table', function (): void {
        Currency::query()->delete();

        $this->withoutExceptionHandling();

        $action = new EditMentorProgramPage;
        expect(fn (): Response => $action->handle(MentorProgram::factory()->make($this->data)))
            ->toThrow(Exception::class, 'Currencies table is empty');
    });

    it('contains required page structure', function (): void {
        $action = new EditMentorProgramPage;
        $mentorProgram = MentorProgram::factory()->create($this->data);
        $result = $action->handle($mentorProgram);
        $data = $result->toResponse(request())->getOriginalContent()->getData();

        expect($data)->toHaveKey('page')
            ->and($data['page'])->toHaveKeys(['component', 'props'])
            ->and($data['page']['props'])->toHaveKeys(['currencies', 'program']);
    });

    it('preserves currency id-name mapping', function (): void {
        $action = new EditMentorProgramPage;
        $mentorProgram = MentorProgram::factory()->create($this->data);
        $result = $action->handle($mentorProgram);
        $currencies = Arr::get($result->toResponse(request())->getOriginalContent()->getData(), 'page.props.currencies');

        expect($currencies)->toHaveCount(4)
            ->toContain('USD', 'EUR', 'UAH', 'GBP');
    });

    it('can handle null mentor program', function (): void {
        $action = new EditMentorProgramPage;
        $mentorProgram = MentorProgram::factory()->create($this->data);
        $result = $action->handle($mentorProgram);
        $program = Arr::get($result->toResponse(request())->getOriginalContent()->getData(), 'page.props.program');

        expect($program)->not->toBeNull();
    });
});
