# Правила внесення змін

## Вимоги до коду

Перед створенням pull request, переконайтесь, що код проходить **усі автоматичні перевірки**.

На проєкті використовуються інструменти статичного аналізу та форматування, зокрема:

- [Rector](https://github.com/reccor/reccor)
- [PHPStan](https://phpstan.org/)
- [Pint](https://laravel.com/docs/pint)

#### Rector

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
