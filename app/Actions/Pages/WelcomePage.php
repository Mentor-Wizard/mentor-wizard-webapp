<?php

declare(strict_types=1);

namespace App\Actions\Pages;

use App\Console\Commands\CacheCategoryTree;
use App\Enums\RoleEnum;
use App\Http\Requests\WelcomePageRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class WelcomePage
{
    use AsController;

    public function handle(WelcomePageRequest $request): Response
    {
        $categoryId = $request->integer('category_id') ?: null;
        $categories = CacheCategoryTree::getOrBuild();

        $query = User::query()
            ->role(RoleEnum::MENTOR->value)
            ->with(['profile']);

        if ($categoryId !== null) {
            $this->applyCategory($query, $categoryId, $categories);
        }

        return Inertia::render('WelcomePage', [
            'canLogin'           => Route::has('login'),
            'canRegister'        => Route::has('register'),
            'laravelVersion'     => Application::VERSION,
            'phpVersion'         => PHP_VERSION,
            'categories'         => $categories,
            'selectedCategoryId' => $categoryId,
            'mentors'            => $query
                ->paginate(User::DEFAULT_MENTOR_PAGE_PAGINATION)
                ->appends($request->query()),
        ]);
    }

    /**
     * @param  Builder<User>  $query
     * @param  array<int, array{id: int, name: string, children: array<mixed>}>|null  $categories
     */
    private function applyCategory(Builder $query, int $categoryId, ?array $categories): void
    {
        $descendantIds = CacheCategoryTree::collectDescendantIds($categories, $categoryId);

        if ($descendantIds !== []) {
            $query->whereHas(
                'mentorProfile.categories',
                fn (Builder $q) => $q->whereIn('categories.id', $descendantIds),
            );
        } else {
            $query->whereRaw('1 = 0');
        }
    }
}
