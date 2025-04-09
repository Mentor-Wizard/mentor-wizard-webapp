# Правила внесення змін

## Вимоги до коду

Перед створенням pull request, переконайтесь, що код проходить **усі автоматичні перевірки**.

На проєкті використовуються інструменти статичного аналізу та форматування, зокрема:

- [Rector](//github.com/driftingly/rector-laravel)
- [PHPStan](//phpstan.org/)
- [Pint](//github.com/laravel/pint)

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
