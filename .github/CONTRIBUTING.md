# Правила внесення змін

## AI-асистент (Claude Code)

Проєкт використовує Claude Code з мультиагентним workflow. Для налаштування локального середовища дивіться [docs/CLAUDE_CODE_SETUP.md](../docs/CLAUDE_CODE_SETUP.md).

## Вимоги до коду

Перед створенням pull request, переконайтесь, що код проходить **усі автоматичні
перевірки**.

На проєкті використовуються інструменти статичного аналізу та форматування,
зокрема:

- [Pint](//github.com/laravel/pint)
- [PHPStan](//github.com/larastan/larastan)
- [Rector](//github.com/driftingly/rector-laravel)

### Pint

- Запуск перевірки на **code style**:

    ```sh
    composer pint
    ```

    aбо

    ```sh
    ./vendor/bin/pint . --test
    ```

- Виправлення **code style**:

    ```sh
    composer pint:fix
    ```

    aбо

    ```sh
    ./vendor/bin/pint .
    ```

### PHPStan

- Запуск перевірки:

    ```sh
    composer phpstan
    ```

    aбо

    ```sh
    ./vendor/bin/phpstan analyse
    ```

### Rector

- Запуск перевірки:

    ```sh
    composer rector
    ```

    aбо

    ```sh
    ./vendor/bin/rector process --dry-run
    ```

- Виправлення коду:

    ```sh
    composer rector:fix
    ```

    aбо

    ```sh
    ./vendor/bin/rector process
    ```

Усі знайдені помилки мають бути виправлені до об'єднання коду в основну гілку.
