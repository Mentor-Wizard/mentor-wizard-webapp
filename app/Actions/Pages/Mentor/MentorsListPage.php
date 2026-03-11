<?php

declare(strict_types=1);

namespace App\Actions\Pages\Mentor;

use App\Enums\TagEnum;
use App\Filters\ExperienceLevelFilter;
use App\Filters\ProfileRateFilter;
use App\Filters\ProgramCostFilter;
use App\Filters\RateInUsdSort;
use App\Filters\RatingFilter;
use App\Filters\TagLanguagesFilter;
use App\Filters\TagStacksFilter;
use App\Models\Currency;
use App\Models\MentorProfile;
use App\Models\MentorTag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class MentorsListPage
{
    use AsController;

    public function handle(Request $request): Response
    {
        /** @var QueryBuilder<MentorProfile> $query */
        $query = QueryBuilder::for(MentorProfile::class, $request);

        $mentors = $query /** @phpstan-ignore method.notFound */
            ->with([
                'mentorPrograms',
                'mentorTags',
                'currency',
                'user' => fn ($q) => $q->with('profile')
                    ->withCount('mentorReviews')
                    ->withAvg('mentorReviews', 'rating'),
            ])
            ->allowedFilters([
                AllowedFilter::custom('rate', new ProfileRateFilter),
                AllowedFilter::custom('cost', new ProgramCostFilter),
                AllowedFilter::custom('languages', new TagLanguagesFilter),
                AllowedFilter::custom('stacks', new TagStacksFilter),
                AllowedFilter::custom('experience', new ExperienceLevelFilter),
                AllowedFilter::custom('rating', new RatingFilter),
            ])
            ->allowedSorts([
                'id',
                AllowedSort::custom('rate', new RateInUsdSort),
                'experience_started_at',
            ])
            ->paginate(6)
            ->appends($request->query())
            ->through(function ($mentor): array {
                $user = $mentor->user;
                $profile = $user?->profile;

                return [
                    'id'       => $mentor->getKey(),
                    'slug'     => $user->slug,
                    'name'     => mb_trim(($profile?->name ?? '').' '.($profile?->last_name ?? '')), /** @phpstan-ignore nullsafe.neverNull, nullsafe.neverNull */
                    'title'    => $mentor->title,
                    'price'    => $mentor->rate ?? $profile?->cost_per_hour,
                    'currency' => [
                        'code'   => $mentor->currency?->name ?? 'USD', /** @phpstan-ignore nullsafe.neverNull */
                        'symbol' => $mentor->currency?->symbol ?? '$', /** @phpstan-ignore nullsafe.neverNull */
                    ],
                    'tags' => $mentor->mentorTags
                        ->where('type', TagEnum::STACK)
                        ->pluck('tag')
                        ->toArray(),
                    'rating'     => $user->mentor_reviews_avg_rating ? round((float) $user->mentor_reviews_avg_rating, 1) : 0,
                    'reviews'    => $user->mentor_reviews_count ?? 0,
                    'experience' => $mentor->experience_started_at
                        ? now()->diff($mentor->experience_started_at)->y
                        : 0,
                    'image'             => $profile?->avatar,
                    'availability'      => 'today',
                    'availabilityLabel' => 'Available now',
                ];
            });

        return Inertia::render('Mentor/MentorsListPage', [
            'mentors'     => $mentors,
            'filtersData' => [
                'stackOptions'    => $this->getStackOptions(),
                'languageOptions' => $this->getLanguageOptions(),
                'currencyOptions' => $this->getCurrencyOptions(),
            ],
            'queryParams' => $request->only(['filter', 'sort', 'page']),
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getStackOptions(): array
    {
        return $this->getTagOptions(TagEnum::STACK);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getLanguageOptions(): array
    {
        return $this->getTagOptions(TagEnum::LANGUAGE);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getTagOptions(TagEnum $type): array
    {
        return MentorTag::query()->where('type', $type)
            ->select('tag')
            ->distinct()
            ->orderBy('tag')
            ->get()
            ->map(fn (MentorTag $tag): array => [
                'value' => $tag->tag,
                'label' => $tag->tag,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getCurrencyOptions(): array
    {
        return Currency::query()->orderBy('name')
            ->get()
            ->map(fn (Currency $currency): array => [
                'value' => $currency->name,
                'label' => sprintf('%s (%s)', $currency->name, $currency->symbol),
            ])
            ->values()
            ->all();
    }
}
