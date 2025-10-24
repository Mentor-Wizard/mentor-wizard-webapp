# Дебагінг фонових задач (Queue Jobs) з Xdebug

## Проблема

Коли ви працюєте з фоновими задачами (Jobs) в Laravel, традиційні методи
дебагінгу типу `dd()` або `dump()` **не працюють належним чином**:

1. **`dd()` зупиняє worker процес** без виводу результату, оскільки немає HTTP
   response
2. **`dump()` нічого не виведе**, бо вивід йде в worker процес, а не в браузер
3. **Логічні помилки не викликають exception**, тому Job може завершитись
   успішно, але з некоректними даними
4. **Некоректні дані тихо записуються** в базу/кеш, і ви можете не помітити
   проблему одразу

## Приклад проблеми

У проекті створено Job `ProcessNewUserRegistration`, який виконується при
реєстрації нового користувача і містить кілька логічних помилок:

```php
// app/Jobs/ProcessNewUserRegistration.php

// ПОМИЛКА 1: Неправильна структура даних після обробки metadata
private function processMetadata(array $preferences): array
{
    $result = $preferences;

    if (!empty($this->metadata)) {
        foreach ($this->metadata as $category => $values) {
            $existing = $result[$category] ?? [];

            // array_merge переіндексує числові ключі!
            $result[$category] = array_merge($existing, $values);

            // Логіка override повністю замінює дані
            if (isset($result[$category]['override']) && $result[$category]['override'] === true) {
                $result[$category] = $values;
            }
        }
    }

    return $result; // Повертає структуру без ключа 'preferences'!
}

// ПОМИЛКА 2: Доступ до неіснуючого вкладеного ключа масиву
private function updateUserProfile(array $data): void
{
    $this->user->update([
        'preferences' => json_encode($data['preferences'] ?? $data),
    ]);

    if (isset($data['settings']['theme'])) {
        Log::info('User theme set', [
            'user_id' => $this->user->getKey(),
            'theme' => $data['settings']['theme'],
            // Помилка: $data['settings']['locale'] може не існувати!
            'language' => $data['settings']['locale']['language'],
        ]);
    }
}
```

Ці помилки **не викличуть exception завжди**, але:

1. При певних умовах призведуть до Exception з "Undefined array key"
2. Створять некоректну структуру JSON в базі даних
3. Логіка override працює не так, як очікується

Спробуйте додати `dd($data)` у код - worker зупиниться, але ви не побачите
виводу в браузері або консолі.

## Рішення: Використання Xdebug

### 1. Переконайтеся, що Xdebug налаштований

У Docker контейнері проекту Xdebug вже налаштований. Перевірте
`.docker/app/xdebug.ini`:

```ini
[xdebug]
xdebug.mode=develop,debug,coverage
xdebug.start_with_request=yes
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
xdebug.idekey=PHPSTORM
```

### 2. Налаштуйте PHPStorm/VS Code

#### PHPStorm:

1. **Settings → PHP → Debug**
    - Xdebug port: `9003`
    - ✓ Can accept external connections

2. **Settings → PHP → Servers**
    - Name: `mentor-wizard` (має збігатися з `serverName` в конфігурації)
    - Host: `localhost`
    - Port: `80`
    - Debugger: `Xdebug`
    - ✓ Use path mappings:
        - `/Users/your-path/mentor-wizard-webapp` → `/var/www/html`

3. **Run → Start Listening for PHP Debug Connections** (або натисніть телефон в
   панелі)

#### VS Code:

Створіть `.vscode/launch.json`:

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

### 3. Налаштуйте Queue Worker для дебагінгу

#### Варіант A: Запустіть worker вручну в контейнері

```bash
# Увійдіть в контейнер
docker compose exec app bash

# Запустіть worker з Xdebug
php artisan queue:work --tries=1 --max-jobs=1
```

Параметри:

- `--tries=1` - одна спроба виконання Job
- `--max-jobs=1` - worker зупиниться після виконання одного Job

#### Варіант B: Налаштуйте PHPStorm CLI Interpreter

1. **Settings → PHP → CLI Interpreter**
    - Add new `From Docker, Vagrant...`
    - Select `Docker Compose`
    - Configuration files: `./docker-compose.yml`
    - Service: `app`

2. Створіть **Run Configuration**:
    - Run → Edit Configurations → Add New → PHP Script
    - File: `artisan`
    - Arguments: `queue:work --tries=1 --max-jobs=1`
    - Interpreter: ваш Docker CLI interpreter

### 4. Встановіть точки зупинки (Breakpoints)

Відкрийте файл `app/Jobs/ProcessNewUserRegistration.php` та встановіть
breakpoint на рядках:

- **Рядок 39**: Початок методу `handle()`
- **Рядок 46**: Виклик `processMetadata()` - дивимось що передається
- **Рядок 82-112**: Усередині методу `processMetadata()` - тут основна логіка
  обробки
- **Рядок 95**: Виклик `array_merge()` - перевіряємо результат
- **Рядок 99-101**: Логіка override - перевіряємо чи спрацьовує
- **Рядок 118-136**: Метод `updateUserProfile()` - тут помилка з доступом до
  масиву
- **Рядок 133**: Доступ до `$data['settings']['locale']['language']` - тут
  Exception!

### 5. Запустіть дебагінг

1. **Увімкніть прослуховування Xdebug** в IDE (телефон в PHPStorm)

2. **Диспетчіруйте Job** одним з способів:

    **Спосіб A: Через реєстрацію користувача**
    - Відкрийте форму реєстрації у браузері
    - Зареєструйте нового користувача
    - Job автоматично додасться в чергу

    **Спосіб B: Через Tinker**

    ```bash
    docker compose exec app php artisan tinker
    >>> $user = App\Models\User::factory()->create();
    >>> $metadata = [
    ...     'registration_source' => 'test',
    ...     'settings' => [
    ...         'locale' => ['language' => 'uk'],
    ...         'override' => true,
    ...     ],
    ... ];
    >>> App\Jobs\ProcessNewUserRegistration::dispatch($user, $metadata);
    ```

    **Спосіб C: Через тест**

    ```bash
    docker compose exec app ./vendor/bin/pest tests/Unit/Jobs/ProcessNewUserRegistrationTest.php
    ```

3. **Запустіть Queue Worker** (якщо ще не запущений):

    ```bash
    docker compose exec app php artisan queue:work --tries=1 --max-jobs=1
    ```

4. **Дебажте!**
    - IDE зупиниться на першому breakpoint
    - Використовуйте **Step Over (F8)**, **Step Into (F7)**, **Step Out
      (Shift+F8)**
    - Перевіряйте значення змінних у вікні **Variables/Watches**
    - Оцінюйте вирази у вікні **Evaluate Expression (Alt+F8)**

### 6. Виправте помилки

Після того як ви знайдете помилку за допомогою Xdebug, виправте код:

#### Виправлення 1: Правильна обробка metadata

```php
private function processMetadata(array $preferences): array
{
    $result = $preferences;

    if (!empty($this->metadata)) {
        foreach ($this->metadata as $category => $values) {
            // Перевіряємо чи категорія існує в preferences
            if (!isset($result[$category])) {
                $result[$category] = [];
            }

            // Використовуємо array_replace_recursive для правильного merge
            $result[$category] = array_replace_recursive($result[$category], $values);

            // Видаляємо службовий ключ override після обробки
            unset($result[$category]['override']);
        }
    }

    return $result;
}
```

#### Виправлення 2: Безпечний доступ до вкладених масивів

```php
private function updateUserProfile(array $data): void
{
    $this->user->update([
        'preferences' => json_encode($data),
    ]);

    // Безпечний доступ через оператор null-safe
    if (isset($data['settings']['theme'])) {
        $language = $data['settings']['locale']['language'] ??
                    $data['settings']['language'] ??
                    'en';

        Log::info('User theme set', [
            'user_id' => $this->user->getKey(),
            'theme' => $data['settings']['theme'],
            'language' => $language,
        ]);
    }
}
```

## Корисні команди

### Перевірка логів queue

```bash
docker compose exec app tail -f storage/logs/laravel.log
```

### Перегляд failed jobs

```bash
docker compose exec app php artisan queue:failed
```

### Retry failed job

```bash
docker compose exec app php artisan queue:retry <job-id>
```

### Очистка queue

```bash
docker compose exec app php artisan queue:flush
```

### Перезапуск worker (після змін коду)

```bash
docker compose exec app php artisan queue:restart
```

## Чому Xdebug краще за dd()/dump() для Jobs

| Метод           | Переваги                                                       | Недоліки                                               |
| --------------- | -------------------------------------------------------------- | ------------------------------------------------------ |
| **dd()/dump()** | Швидко, просто                                                 | Не працює в фонових задачах, зупиняє worker без виводу |
| **Log::info()** | Працює, можна подивитись логи                                  | Важко відслідкувати складну логіку, багато виводу      |
| **Xdebug**      | Повний контроль, step-by-step виконання, перегляд всіх змінних | Потребує налаштування IDE                              |

## Додаткові поради

1. **Використовуйте `--max-jobs=1`** при дебагінгу, щоб worker зупинявся після
   кожного Job
2. **Перевіряйте conditional breakpoints** для складних умов
3. **Використовуйте "Run to Cursor"** для швидкого переходу до потрібного місця
4. **Налаштуйте watches** для відстеження важливих змінних
5. **Використовуйте "Evaluate Expression"** для тестування виправлень без зміни
   коду

## Посилання

- [Laravel Queues Documentation](https://laravel.com/docs/12.x/queues)
- [Xdebug Documentation](https://xdebug.org/docs/)
- [PHPStorm Debugging Guide](https://www.jetbrains.com/help/phpstorm/debugging.html)
- [VS Code PHP Debug Extension](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug)

---

**Автор документації**: Claude Code **Дата створення**: 2025-10-01 **Версія**:
1.0
