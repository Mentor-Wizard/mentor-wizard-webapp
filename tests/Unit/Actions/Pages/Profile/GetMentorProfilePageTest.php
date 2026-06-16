<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\GetMentorProfilePage;
use App\Enums\RoleEnum;
use App\Models\Currency;
use App\Models\MentorProfile;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Inertia\Response;
use Spatie\Permission\Models\Role;

mutates(GetMentorProfilePage::class);

describe('Mentor Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
    });

    it('Mentor`s data correct', function (): void {
        $user = User::factory()->create([
            'username' => 'Test User',
            'email'    => 'test@example.com',
        ]);
        $user->profile->update([
            'name'        => 'profile name',
            'last_name'   => 'profile last_name',
            'linkedin'    => 'profile linkedin',
            'telegram'    => 'profile telegram',
            'whatsapp'    => 'profile whatsapp',
            'phone'       => 'profile phone',
        ]);
        $currencies = Currency::query()->pluck('name', 'id')->toArray();
        $currency_id = array_key_first($currencies);
        MentorProfile::factory()->create([
            'user_id'                  => $user->getKey(),
            'title'                    => 'title',
            'description'              => 'description',
            'rate'                     => 1.1,
            'currency_id'              => $currency_id,
            'experience_started_at'    => Date::now()->subYears(5)->subMonths(6)->format('Y-m-d'),
        ]);

        $user->refresh();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $action = new GetMentorProfilePage;
        $result = $action->handle(Request::create('/'), $user);
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Profile/Mentor/ViewPage')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.slug'))->toBe('test-user')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.titleBlock.name'))->toBe('profile name profile last_name')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.titleBlock.avatar'))->toBe(UserProfile::DEFAULT_AVATAR_URL)
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.titleBlock.title'))->toBe('title')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.titleBlock.description'))->toBe('description')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.titleBlock.rate'))->toBe('1.10')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.titleBlock.experience'))->toBe('5 years')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.statisticBlock.star_5'))->toBe(0)
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.statisticBlock.star_4'))->toBe(0)
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.statisticBlock.star_3'))->toBe(0)
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.statisticBlock.star_2'))->toBe(0)
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.statisticBlock.star_1'))->toBe(0);
    });

    it('throws AuthorizationException if user is not mentor', function (): void {
        $user = User::factory()->create();

        $action = new GetMentorProfilePage;

        expect(fn (): Response => $action->handle(Request::create('/'), $user))
            ->toThrow(ModelNotFoundException::class);
    });

    it('returns null calendarBlock and currentDate when mentor has no programs', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $currencies = Currency::query()->pluck('name', 'id')->toArray();
        MentorProfile::factory()->create([
            'user_id'     => $user->getKey(),
            'currency_id' => array_key_first($currencies),
        ]);

        $action = new GetMentorProfilePage;
        $result = $action->handle(Request::create('/'), $user);
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect(Arr::get($resultData->getData(), 'page.props.calendarBlock'))->toBeNull()
            ->and(Arr::get($resultData->getData(), 'page.props.currentDate'))->toBeNull();
    });

    it('returns weekDays with all 7 days in correct order', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $currencies = Currency::query()->pluck('name', 'id')->toArray();
        MentorProfile::factory()->create([
            'user_id'     => $user->getKey(),
            'currency_id' => array_key_first($currencies),
        ]);

        $action = new GetMentorProfilePage;
        $result = $action->handle(Request::create('/'), $user);
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect(Arr::get($resultData->getData(), 'page.props.weekDays'))->toBe([
            'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
        ]);
    });

    it('loads profile relationship via loadMissing', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $currencies = Currency::query()->pluck('name', 'id')->toArray();
        MentorProfile::factory()->create([
            'user_id'     => $user->getKey(),
            'currency_id' => array_key_first($currencies),
        ]);

        // Fresh user without eager-loaded relations
        $freshUser = User::query()->whereKey($user->getKey())->firstOrFail();

        $action = new GetMentorProfilePage;
        $result = $action->handle(Request::create('/'), $freshUser);
        $resultData = $result->toResponse(request())->getOriginalContent();

        // profile was loadMissing'd so the title block is available
        expect(Arr::get($resultData->getData(), 'page.component'))->toBe('Profile/Mentor/ViewPage');
    });

    it('returns mainProgram with all required keys when a main program exists', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $currencies = Currency::query()->pluck('name', 'id')->toArray();
        MentorProfile::factory()->create([
            'user_id'     => $user->getKey(),
            'currency_id' => array_key_first($currencies),
        ]);

        $program = MentorProgram::factory()->main()->create([
            'mentor_id'   => $user->getKey(),
            'name'        => 'Main Program',
            'description' => 'Program description',
        ]);

        $action = new GetMentorProfilePage;
        $result = $action->handle(Request::create('/'), $user);
        $resultData = $result->toResponse(request())->getOriginalContent();

        $mainProgram = Arr::get($resultData->getData(), 'page.props.mainProgram');

        expect($mainProgram)->toHaveKeys(['id', 'name', 'description', 'session_type_options', 'session_duration'])
            ->and($mainProgram['id'])->toBe($program->getKey())
            ->and($mainProgram['name'])->toBe('Main Program')
            ->and($mainProgram['description'])->toBe('Program description')
            ->and($mainProgram['session_duration'])->toBe($program->session_duration);
    });

    it('returns mainProgramSlug matching the main program slug', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $currencies = Currency::query()->pluck('name', 'id')->toArray();
        MentorProfile::factory()->create([
            'user_id'     => $user->getKey(),
            'currency_id' => array_key_first($currencies),
        ]);

        $program = MentorProgram::factory()->main()->create([
            'mentor_id' => $user->getKey(),
            'slug'      => 'my-main-program',
        ]);

        $action = new GetMentorProfilePage;
        $result = $action->handle(Request::create('/'), $user);
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect(Arr::get($resultData->getData(), 'page.props.mainProgramSlug'))->toBe('my-main-program');
    });

    it('returns currentDate as a date string when a main program exists', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $currencies = Currency::query()->pluck('name', 'id')->toArray();
        MentorProfile::factory()->create([
            'user_id'     => $user->getKey(),
            'currency_id' => array_key_first($currencies),
        ]);

        MentorProgram::factory()->main()->create([
            'mentor_id' => $user->getKey(),
        ]);

        $action = new GetMentorProfilePage;
        $result = $action->handle(Request::create('/'), $user);
        $resultData = $result->toResponse(request())->getOriginalContent();

        $currentDate = Arr::get($resultData->getData(), 'page.props.currentDate');

        expect($currentDate)->toBeString()
            ->and($currentDate)->toMatch('/^\d{4}-\d{2}-\d{2}$/');
    });

    it('filters programs by is_main=true so only main program is used', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $currencies = Currency::query()->pluck('name', 'id')->toArray();
        MentorProfile::factory()->create([
            'user_id'     => $user->getKey(),
            'currency_id' => array_key_first($currencies),
        ]);

        // Create main program first — observer promotes the first program to is_main=true
        MentorProgram::factory()->main()->create([
            'mentor_id' => $user->getKey(),
            'slug'      => 'the-main-one',
        ]);

        // Insert non-main program silently to bypass the observer and avoid unique constraint violation
        $nonMain = MentorProgram::factory()->make([
            'mentor_id' => $user->getKey(),
            'is_main'   => false,
            'slug'      => 'not-the-main-one',
        ]);
        $nonMain->saveQuietly();

        $action = new GetMentorProfilePage;
        $result = $action->handle(Request::create('/'), $user);
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect(Arr::get($resultData->getData(), 'page.props.mainProgramSlug'))->toBe('the-main-one');
    });
});
