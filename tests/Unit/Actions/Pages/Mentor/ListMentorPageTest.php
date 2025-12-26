<?php

declare(strict_types=1);

use App\Actions\Pages\Mentor\MentorsListPage;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Testing\AssertableInertia;

mutates(MentorsListPage::class);

describe('ListMentorPage', function (): void {
    it('renders the mentor list page', function (): void {
        $action = new MentorsListPage;
        $request = Request::create('/mentors', 'GET');

        $response = $action->handle($request);

        expect($response)
            ->toBeInstanceOf(Response::class);
    });

    it('returns mentors and total count in props', function (): void {
        $this->get(route('pages.mentors'))
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('Mentor/ListPage')
                ->has('mentors')
                ->has('total')
            );
    });

    it('accepts filter parameters from request', function (): void {
        $action = new MentorsListPage;
        $request = Request::create('/mentors', 'GET', [
            'expertise'    => ['web-dev', 'mobile-dev'],
            'experience'   => ['senior'],
            'priceMin'     => 50,
            'priceMax'     => 150,
            'ratings'      => [5, 4],
            'availability' => ['today'],
        ]);

        $response = $action->handle($request);

        expect($response)
            ->toBeInstanceOf(Response::class);
    });
});
