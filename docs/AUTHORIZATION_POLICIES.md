# Політики авторизації та контролю доступу

## Огляд

У проекті використовується комплексна система авторизації, що базується на
Laravel Policies та пакеті Spatie Laravel Permission для управління ролями та
дозволами.

## Структура авторизації

### Ролі системи

Система використовує enum `App\Enums\RoleGuardEnum` для визначення ролей:

- **USER** (`user`) - базова роль користувача
- **ADMIN** (`admin`) - адміністратор системи
- **SUPER_ADMIN** (`superadmin`) - супер-адміністратор
- **MENTOR** (`mentor`) - ментор, що надає послуги
- **MENTI** (`menti`) - підопічний, що отримує послуги
- **COACH** (`coach`) - коуч

### Модель User

Модель `User` використовує трейт `HasRoles` від пакету Spatie Permission:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    protected array $guard_name = [
        'web',
        RoleGuardEnum::USER->value,
        RoleGuardEnum::ADMIN->value,
        RoleGuardEnum::SUPER_ADMIN->value,
        RoleGuardEnum::MENTOR->value,
        RoleGuardEnum::MENTI->value,
        RoleGuardEnum::COACH->value,
    ];
}
```

## Laravel Policies

### Існуючі політики

#### MentorProgramPolicy

Розташування: `app/Policies/MentorProgramPolicy.php`

```php
class MentorProgramPolicy
{
    public function update(User $user, MentorProgram $mentorProgram): bool
    {
        return $mentorProgram->mentor_id === $user->getKey();
    }

    public function delete(User $user, MentorProgram $mentorProgram): bool
    {
        return $mentorProgram->mentor_id === $user->getKey();
    }
}
```

**Правила доступу:**

- Тільки власник програми (ментор) може її оновлювати
- Тільки власник програми (ментор) може її видаляти

## Застосування в роутах

### Middleware для ролей

Використовуйте middleware `role` для перевірки ролей у роутах:

```php
Route::middleware(['auth', 'role:mentor'])->group(function (): void {
    Route::get('mentor-program/create', CreateMentorProgramPage::class)
        ->name('mentor-program.create');
    Route::post('mentor-program', StoreMentorProgramPage::class)
        ->name('mentor-program.store');
});
```

### Перевірка політик у роутах

Використовуйте метод `can()` для перевірки політик безпосередньо в роутах:

```php
Route::patch('mentor-program/{mentorProgram:slug}', UpdateMentorProgramPage::class)
    ->can('update', 'mentorProgram')
    ->name('mentor-program.update');

Route::delete('mentor-program/{mentorProgram:slug}', DeleteMentorProgram::class)
    ->can('delete', 'mentorProgram')
    ->name('mentor-program.destroy');
```

## Конвенції розробки

### Створення нових політик

1. **Використовуйте Artisan команду:**

    ```bash
    php artisan make:policy PostPolicy --model=Post
    ```

2. **Розміщення:** Всі політики розміщуються в `app/Policies/`

3. **Структура методів:**
    - `viewAny()` - перегляд списку ресурсів
    - `view()` - перегляд конкретного ресурсу
    - `create()` - створення нового ресурсу
    - `update()` - оновлення ресурсу
    - `delete()` - видалення ресурсу

### Приклад повної політики

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Post;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'editor']);
    }

    public function view(User $user, Post $post): bool
    {
        return $post->is_published ||
               $post->author_id === $user->getKey() ||
               $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'editor']);
    }

    public function update(User $user, Post $post): bool
    {
        return $post->author_id === $user->getKey() ||
               $user->hasRole('admin');
    }

    public function delete(User $user, Post $post): bool
    {
        return $post->author_id === $user->getKey() ||
               $user->hasRole('admin');
    }
}
```

### Реєстрація політик

Laravel автоматично виявляє політики за конвенцією іменування, але можна явно
зареєструвати їх у `bootstrap/app.php`:

```php
use App\Models\Post;
use App\Policies\PostPolicy;

->withRouting(
    // ... інші налаштування
)
->withMiddleware(function (Middleware $middleware) {
    // ... middleware
})
->withExceptions(function (Exceptions $exceptions) {
    // ... exceptions
})
->create();

// або у service provider:
protected $policies = [
    Post::class => PostPolicy::class,
];
```

## Перевірка дозволів у коді

### У контролерах та Action класах

```php
// Перевірка через Gate
if (Gate::allows('update', $post)) {
    // Дозволено оновлювати
}

// Або використання authorize()
$this->authorize('update', $post);

// Для користувача
if ($user->can('update', $post)) {
    // Дозволено
}
```

### У Blade шаблонах

```blade
@can('update', $post)
    <a href="{{ route('posts.edit', $post) }}">Редагувати</a>
@endcan

@cannot('delete', $post)
    <span class="text-muted">Ви не можете видалити цей пост</span>
@endcannot
```

### У Vue компонентах (через Inertia)

```javascript
// Передача дозволів через Inertia
return Inertia::render('Posts/Show', [
    'post' => $post,
    'can' => [
        'update' => auth()->user()->can('update', $post),
        'delete' => auth()->user()->can('delete', $post),
    ]
]);
```

```vue
<template>
    <div>
        <button v-if="can.update" @click="editPost">Редагувати</button>
        <button v-if="can.delete" @click="deletePost">Видалити</button>
    </div>
</template>

<script setup>
defineProps({
    post: Object,
    can: Object,
});
</script>
```

## Spatie Permission - робота з ролями та дозволами

### Призначення ролей

```php
// Призначити роль користувачу
$user->assignRole('mentor');
$user->assignRole(['mentor', 'admin']);

// Синхронізація ролей
$user->syncRoles(['mentor']);

// Видалення ролі
$user->removeRole('mentor');
```

### Створення та управління ролями

```php
use Spatie\Permission\Models\Role;

// Створення ролі
$role = Role::create(['name' => 'mentor']);

// З guard
$role = Role::create(['name' => 'mentor', 'guard_name' => 'web']);
```

### Перевірка ролей

```php
// Перевірка наявності ролі
if ($user->hasRole('mentor')) {
    // Користувач має роль ментора
}

// Перевірка будь-якої з ролей
if ($user->hasAnyRole(['mentor', 'admin'])) {
    // Користувач має одну з ролей
}

// Перевірка всіх ролей
if ($user->hasAllRoles(['mentor', 'verified'])) {
    // Користувач має всі ролі
}
```

## Рекомендації

### Безпека

1. **Завжди перевіряйте дозволи на рівні роутів** - це перша лінія захисту
2. **Не покладайтеся тільки на frontend перевірки** - завжди валідуйте на
   backend

### Продуктивність

1. **Кешуйте ролі та дозволи** - Spatie Permission автоматично кешує їх
2. **Використовуйте eager loading** для ролей та дозволів при необхідності:
    ```php
    $users = User::with('roles', 'permissions')->get();
    ```

### Тестування

Завжди тестуйте політики:

```php
it('allows mentor to update their program', function () {
    $mentor = User::factory()->create();
    $mentor->assignRole('mentor');

    $program = MentorProgram::factory()->create([
        'mentor_id' => $mentor->id
    ]);

    expect($mentor->can('update', $program))->toBeTrue();
});

it('prevents non-owner from updating program', function () {
    $user = User::factory()->create();
    $program = MentorProgram::factory()->create();

    expect($user->can('update', $program))->toBeFalse();
});
```

## Приклади використання в проекті

### Структура middleware для захисту роутів

```php
// Базова авторизація
Route::middleware('auth')->group(function () {
    // Роути для авторизованих користувачів
});

// Роль-специфічні роути
Route::middleware(['auth', 'role:mentor'])->group(function () {
    // Тільки для менторів
});

Route::middleware(['auth', 'role:admin|super_admin'])->group(function () {
    // Для адмінів та супер-адмінів
});

// Комбіновані перевірки
Route::patch('mentor-program/{mentorProgram:slug}', UpdateMentorProgramPage::class)
    ->middleware(['auth', 'role:mentor'])
    ->can('update', 'mentorProgram')
    ->name('mentor-program.update');
```

Така структура забезпечує багаторівневий захист: спочатку перевіряється
авторизація, потім роль, а потім конкретні дозволи через політику.
