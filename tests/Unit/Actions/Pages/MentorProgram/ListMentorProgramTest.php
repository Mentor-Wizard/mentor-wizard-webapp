<?php

declare(strict_types=1);

use App\Actions\Pages\MentorProgram\ListMentorProgramPage;
use App\Enums\RoleEnum;
use App\Models\Category;
use App\Models\Currency;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Response;
use Spatie\Permission\Models\Role;

mutates(ListMentorProgramPage::class);

describe('List Mentor Program', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->seed(CurrencySeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);

        $this->currencies = Currency::query()->pluck('name', 'id')->toArray();
        $this->data = [
            'mentor_id'   => $user->id,
            'name'        => 'Test Program',
            'slug'        => 'test-program',
            'description' => 'Test Description',
            'cost'        => '100.00',
            'currency_id' => array_key_first($this->currencies),
        ];

    });

    it('withCategories factory state attaches the correct number of categories to a program', function (): void {
        $program = MentorProgram::factory()->withCategories(2)->create($this->data);

        expect($program->categories()->count())->toBe(2)
            ->and($program->categories->first())->toBeInstanceOf(Category::class);
    });

    it('withCategories factory state defaults to one category', function (): void {
        $program = MentorProgram::factory()->withCategories()->create($this->data);

        expect($program->categories()->count())->toBe(1);
    });

    it('program created without withCategories has no categories', function (): void {
        $program = MentorProgram::factory()->create($this->data);

        expect($program->categories()->count())->toBe(0);
    });

    it('renders the mentor program list page', function (): void {
        MentorProgram::factory()->create($this->data);
        $action = new ListMentorProgramPage;

        $response = $action->handle();
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('MentorProgram/ListPage')
            ->and(Arr::get($result, 'props.programs.0.name'))->toBe($this->data['name'])
            ->and(Arr::get($result, 'props.programs.0.slug'))->toBe($this->data['slug'])
            ->and(Arr::get($result, 'props.programs.0.cost'))->toBe($this->data['cost'])
            ->and(Arr::get($result, 'props.programs.0.created_at'))->toBeString()
            ->and(Arr::get($result, 'props.programs.0.description'))->toBe($this->data['description'])
            ->and(Arr::get($result, 'props.programs.0.currency.id'))->toBe($this->data['currency_id']);
    });
});
