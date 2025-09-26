# Рекомендації з безпеки

## Огляд

Безпека є критично важливою для платформи менторингу, де обробляються
персональні дані, фінансові транзакції та конфіденційна інформація. Цей документ
описує комплексний підхід до забезпечення безпеки всіх рівнів додатка.

## Загальні принципи безпеки

### Defense in Depth (Багаторівневий захист)

```
┌─────────────────────────────────────────┐
│  1. Infrastructure Security             │
├─────────────────────────────────────────┤
│  2. Network Security                    │
├─────────────────────────────────────────┤
│  3. Application Security                │
├─────────────────────────────────────────┤
│  4. Data Security                       │
├─────────────────────────────────────────┤
│  5. User Security                       │
└─────────────────────────────────────────┘
```

### Принципи

1. **Least Privilege** - мінімальні необхідні дозволи
2. **Zero Trust** - не довіряємо, завжди перевіряємо
3. **Security by Design** - безпека з самого початку
4. **Regular Audits** - регулярні перевірки безпеки

## Автентифікація та авторизація

### Стандартна Laravel автентифікація

Проект використовує стандартну сесійну автентифікацію Laravel з додатковою
підтримкою Socialite для входу через соціальні мережі.

### Спільна автентифікація (Socialite)

#### Безпечна конфігурація

```php
// config/services.php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('APP_URL') . '/auth/google/callback',
],

// Валідація в SocialiteCallback Action
class SocialiteCallback
{
    public function handle(string $driver): RedirectResponse
    {
        // Валідація підтримуваного провайдера
        if (!in_array($driver, ['google', 'github'])) {
            abort(404);
        }

        try {
            $socialUser = Socialite::driver($driver)->user();

            // Валідація email домену (за потреби)
            if (!$this->isAllowedEmailDomain($socialUser->getEmail())) {
                return redirect('/login')
                    ->withErrors(['email' => 'Домен email не дозволений']);
            }

            // Безпечне створення/оновлення користувача
            $user = $this->findOrCreateUser($socialUser, $driver);

            Auth::login($user, true);

            return redirect()->intended('/dashboard');
        } catch (Exception $e) {
            Log::error('Socialite auth failed', [
                'driver' => $driver,
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);

            return redirect('/login')
                ->withErrors(['socialite' => 'Помилка автентифікації']);
        }
    }
}
```

## Захист від OWASP Top 10

### 1. Injection Attacks

#### SQL Injection

```php
// ❌ Небезпечно - ніколи не робіть так
$users = DB::select("SELECT * FROM users WHERE email = '{$email}'");

// ✅ Безпечно - використовуйте Eloquent
$users = User::where('email', $email)->get();

// ✅ Безпечно - параметризовані запити
$users = DB::select('SELECT * FROM users WHERE email = ?', [$email]);
```

#### XSS (Cross-Site Scripting)

```php
// Blade автоматично екранує
{{ $user->bio }} // Безпечно

// Якщо потрібен HTML - використовуйте валідацію
{!! Purifier::clean($user->bio) !!}

// Request валідація
class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'bio' => ['string', 'max:1000', new NoScriptTag()],
        ];
    }
}

// Custom validation rule
class NoScriptTag implements Rule
{
    public function passes($attribute, $value): bool
    {
        return !preg_match('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', $value);
    }
}
```

### 2. Authentication & Session Management

#### Сильні паролі

```php
// Validation rules
'password' => [
    'required',
    'min:12',
    'confirmed',
    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
];

// Перевірка на скомпрометовані паролі
use Illuminate\Validation\Rules\Password;

'password' => [
    'required',
    Password::min(12)
        ->letters()
        ->mixedCase()
        ->numbers()
        ->symbols()
        ->uncompromised()
];
```

#### Session Security

```php
// config/session.php
'lifetime' => 60, // Короткий термін життя
'expire_on_close' => true,
'encrypt' => true,
'http_only' => true,
'same_site' => 'strict',
'secure' => env('SESSION_SECURE_COOKIE', true), // HTTPS only
```

#### Rate Limiting

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->throttleApi('api');

    // Кастомні rate limits
    $middleware->group('auth', [
        'throttle:login'
    ]);
})
->withRateLimiting(function (RateLimiting $rateLimiting) {
    $rateLimiting->for('login', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip())
            ->response(function () {
                return response()->json([
                    'message' => 'Занадто багато спроб входу'
                ], 429);
            });
    });
});
```

### 3. Data Protection

#### Sensitive Data Handling

```php
// Model accessors для маскування
class User extends Authenticatable
{
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    // Маскування номера телефону
    protected function phone(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->maskSensitiveData($value),
            set: fn ($value) => $this->cleanPhoneNumber($value),
        );
    }

    private function maskSensitiveData(string $value): string
    {
        if (auth()->id() !== $this->getKey() && !auth()->user()->hasRole('admin')) {
            return substr($value, 0, 3) . '***' . substr($value, -2);
        }
        return $value;
    }
}
```

#### Encryption

```php
// Sensitive fields encryption
use Illuminate\Contracts\Encryption\DecryptException;

class PaymentInformation extends Model
{
    protected $casts = [
        'card_number' => 'encrypted',
        'card_cvv' => 'encrypted',
    ];

    // Або кастомна реалізація
    public function setCardNumberAttribute($value): void
    {
        $this->attributes['card_number'] = encrypt($value);
    }

    public function getCardNumberAttribute($value): ?string
    {
        try {
            return $value ? decrypt($value) : null;
        } catch (DecryptException $e) {
            Log::error('Failed to decrypt card number', ['id' => $this->id]);
            return null;
        }
    }
}
```

### 4. CSRF Protection

```php
// Middleware для всіх форм
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\VerifyCsrfToken::class,
    ]);
})

// Винятки тільки для API endpoints
class VerifyCsrfToken extends Middleware
{
    protected $except = [
        'api/webhook/*',
        'stripe/webhook',
    ];
}
```

Vue.js з Inertia автоматично обробляє CSRF токени:

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    // Inertia автоматично додає CSRF токен
});
</script>
```

## File Upload Security

### Валідація файлів

```php
// Request validation
class UploadAvatarRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:2048', // 2MB
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000'
            ],
        ];
    }
}

// Додаткова перевірка типу файлу
class SecureFileUploadRule implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (!$value instanceof UploadedFile) {
            return false;
        }

        // Перевірка MIME type через finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $value->getPathname());
        finfo_close($finfo);

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        return in_array($mimeType, $allowedTypes);
    }
}
```

### Spatie Media Library Security

```php
// config/media-library.php
'path_generator' => \App\Support\MediaLibrary\SecurePathGenerator::class,

// Кастомний path generator
class SecurePathGenerator implements PathGeneratorInterface
{
    public function getPath(Media $media): string
    {
        // Приховуємо структуру каталогів
        return $this->getBasePath($media) . '/' . $this->generateSecureFilename($media);
    }

    private function generateSecureFilename(Media $media): string
    {
        // Генеруємо випадковий filename
        return Str::random(40) . '.' . $media->getExtensionAttribute();
    }
}

// Conversion з додатковою обробкою
class User extends Authenticatable implements HasMedia
{
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->nonQueued()
            ->performOnCollections('avatars')
            ->apply(function (Image $image) {
                // Видалення EXIF даних
                $image->getCore()->setImageProperty('exif:*', '');
            });
    }
}
```

## Web Security (Inertia.js)

### Request Validation

Проект використовує Inertia.js для SPA функціональності, тому API endpoints не
потрібні для основної функціональності.

```php
// Стандартна валідація для Inertia запитів
class StoreMentorProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('mentor');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Логування підозрілої активності
        if ($this->has('suspicious_field')) {
            SecurityLogger::logSuspiciousActivity(
                'Unexpected field in form submission',
                $this->all()
            );
        }
    }
}
```

### Web Rate Limiting

```php
// Rate limiting для web роутів
$rateLimiting->for('web', function (Request $request) {
    $user = $request->user();

    if ($user) {
        return Limit::perMinute(100)->by($user->id);
    }

    return Limit::perMinute(30)->by($request->ip());
});

// Специфічні ліміти для критичних операцій
$rateLimiting->for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

$rateLimiting->for('registration', function (Request $request) {
    return Limit::perMinute(3)->by($request->ip());
});
```

### Response Security

```php
// Inertia Page Resource з контролем даних
class MentorProfilePageResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();

        $data = [
            'id' => $this->id,
            'username' => $this->user->username,
            'bio' => $this->bio,
            'specializations' => $this->tags->pluck('name'),
            'rating' => $this->user->rating,
            'avatar_url' => $this->user->getFirstMediaUrl('avatars', 'thumb'),
        ];

        // Конфіденційні дані тільки для власника або адмінів
        if ($user && ($user->id === $this->user_id || $user->hasRole('admin'))) {
            $data['email'] = $this->user->email;
            $data['phone'] = $this->user->profile?->phone;
            $data['can_edit'] = true;
        }

        return $data;
    }
}
```

## Environment Security

### Environment Variables

```bash
# .env - приклад безпечної конфігурації
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mentor-wizard.com

# Сильні ключі
APP_KEY=base64:strong-32-character-key
JWT_SECRET=strong-secret-key

# Database credentials (окремий користувач з мінімальними правами)
DB_USERNAME=app_user
DB_PASSWORD=complex-database-password

# API keys (обмежені по IP та scope)
STRIPE_KEY=sk_live_restricted_key
GOOGLE_CLIENT_SECRET=restricted-oauth-secret

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error # в production

# Cache/Session - Redis з паролем
REDIS_PASSWORD=strong-redis-password
```

### File Permissions

```bash
# Laravel файли
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Storage і cache - писати може тільки web сервер
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Environment файл - тільки читання
chmod 600 .env

# Composer files
chmod 644 composer.json composer.lock
```

## Database Security

### Connection Security

```php
// config/database.php
'mysql' => [
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'), // Окремий користувач
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
    ]) : [],
],
```

### Query Security

```php
// Безпечні query scopes
class MentorProgram extends Model
{
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->where('published_at', '<=', now());
    }

    public function scopeOwnedBy($query, User $user)
    {
        return $query->where('mentor_id', $user->id);
    }
}

// Використання в Actions
class ListMentorProgramsAction
{
    public function handle(Request $request): Collection
    {
        // Завжди фільтруємо по власнику
        return MentorProgram::query()
            ->published()
            ->when($request->user(), function ($query, $user) {
                if (!$user->hasRole('admin')) {
                    $query->ownedBy($user);
                }
            })
            ->with(['mentor:id,username'])
            ->get();
    }
}
```

## Logging та Monitoring

### Security Logging

```php
// Custom log channel for security events
'channels' => [
    'security' => [
        'driver' => 'single',
        'path' => storage_path('logs/security.log'),
        'level' => 'info',
        'permission' => 0600,
    ],
],

// Security events logging
class SecurityLogger
{
    public static function logAuthAttempt(string $email, bool $success, string $ip): void
    {
        Log::channel('security')->info('Auth attempt', [
            'email' => $email,
            'success' => $success,
            'ip' => $ip,
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);
    }

    public static function logSuspiciousActivity(string $activity, array $context = []): void
    {
        Log::channel('security')->warning('Suspicious activity detected', [
            'activity' => $activity,
            'context' => $context,
            'ip' => request()->ip(),
            'user_id' => auth()->id(),
            'timestamp' => now(),
        ]);
    }
}
```

### Failed Job Monitoring

```php
// Job failure notifications
class FailedJobNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['slack', 'database'];
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->error()
            ->content('🚨 Critical job failed!')
            ->attachment(function ($attachment) {
                $attachment->fields([
                    'Job' => $this->job,
                    'Exception' => $this->exception,
                    'Failed at' => $this->failedAt,
                ]);
            });
    }
}
```

## Production Security Checklist

### Server Configuration

- [ ] **SSL/TLS** - Використання HTTPS зі strong cipher suites
- [ ] **HTTP Security Headers** - Налаштовані у Caddy/FrankenPHP
- [ ] **Firewall** - Відкриті тільки необхідні порти
- [ ] **Updates** - Регулярні оновлення системи та PHP
- [ ] **Backup** - Зашифровані backup'и з тестуванням відновлення

### Application Security

- [ ] **Debug Mode** - `APP_DEBUG=false` в production
- [ ] **Error Reporting** - Логування без розкриття деталей користувачам
- [ ] **Secrets** - Всі API ключі в environment variables
- [ ] **Dependencies** - Регулярні оновлення composer dependencies
- [ ] **Code Analysis** - PHPStan, Larastan для статичного аналізу

### Monitoring

```php
// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'database' => DB::select('SELECT 1')[0] ? 'connected' : 'disconnected',
        'redis' => Redis::ping() ? 'connected' : 'disconnected',
        'timestamp' => now(),
    ]);
})->middleware('throttle:10,1');
```

## Incident Response Plan

### 1. Detection

- Автоматичне сповіщення про security incidents
- Monitoring через Laravel Pulse/Telescope
- Log analysis для виявлення аномалій

### 2. Response

```php
// Emergency response Action
class EmergencyShutdownAction
{
    public function handle(): void
    {
        // Тимчасово заблокувати всі запити
        Cache::put('emergency_shutdown', true, now()->addHours(1));

        // Повідомити адмінів
        Notification::route('slack', config('notifications.security_channel'))
            ->notify(new EmergencyShutdownNotification());

        // Логування
        Log::critical('Emergency shutdown activated');
    }
}

// Middleware для emergency shutdown
class EmergencyShutdownMiddleware
{
    public function handle($request, Closure $next)
    {
        if (Cache::has('emergency_shutdown')) {
            return response('Service temporarily unavailable', 503);
        }

        return $next($request);
    }
}
```

### 3. Recovery

- Процедура відновлення з backup'ів
- Post-incident аналіз
- Оновлення security policies

## Compliance (GDPR/CCPA)

### Data Protection

```php
// GDPR compliance features
class GdprComplianceService
{
    public function exportUserData(User $user): array
    {
        return [
            'personal_info' => $user->only(['username', 'email', 'created_at']),
            'profile' => $user->profile?->only(['bio', 'specialization']),
            'mentor_programs' => $user->mentorPrograms->toArray(),
            'sessions' => $user->mentorSessions->toArray(),
            'reviews' => $user->reviewsByMenti->toArray(),
        ];
    }

    public function deleteUserData(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Anonymize instead of delete for audit trail
            $user->update([
                'username' => 'deleted_user_' . $user->id,
                'email' => 'deleted_' . $user->id . '@example.com',
                'email_verified_at' => null,
            ]);

            // Delete sensitive data
            $user->profile?->delete();
            $user->media()->delete();

            // Soft delete user
            $user->delete();
        });
    }
}
```

### Audit Trail

```php
// Model events для audit trail
class User extends Authenticatable
{
    protected static function booted(): void
    {
        static::updated(function ($user) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'user_updated',
                'model_type' => get_class($user),
                'model_id' => $user->id,
                'changes' => $user->getDirty(),
                'ip_address' => request()->ip(),
            ]);
        });
    }
}
```

Дотримання цих рекомендацій з безпеки забезпечить надійний захист платформи
Mentor Wizard та конфіденційність даних користувачів.
