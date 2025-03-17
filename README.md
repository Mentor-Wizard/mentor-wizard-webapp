![Release](https://img.shields.io/github/v/release/Mentor-Wizard/mentor-wizard-webapp)
![Build](https://github.com/Mentor-Wizard/mentor-wizard-webapp/actions/workflows/ci.yml/badge.svg)
![Contributors](https://img.shields.io/github/contributors/Mentor-Wizard/mentor-wizard-webapp)


# Mentor Wizard

Mentor Wizard - це сучасний веб-застосунок, розроблений на базі фреймворку Laravel, що виконує управління менторами та студентами для освіти, наповнений функціоналом сучасного веб-додатка.

## Вимоги

Для розгортання проєкту локально на вашому комп'ютері необхідно мати:
- PHP v8.4 або новішу версію
- Composer
- PostgreSQL
- Redis
- Node.js та Yarn

## Установка

Виконайте наступні кроки, щоб налаштувати проєкт локально:

### 1. Клонування репозиторію

Склонуйте репозиторій проєкту:

```bash
git clone git@github.com:Mentor-Wizard/mentor-wizard-webapp.git
cd mentor-wizard-webapp
```

### 2. Запуск контейнерів Docker

Виконайте команду:

```bash
docker compose up -d
```

### ~~3. Встановлення локальних SSL сертифікатів~~

```bash
docker compose exec app openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout ./docker/nginx/certs/ssl.key -out ./docker/nginx/certs/ssl.crt -subj "/C=UA/ST=Kyiv/L=Kyiv/O=Company/OU=IT Department/CN=localhost"

```

### 4. Установка залежностей Laravel

Виконайте команду:

```bash
docker compose exec -it app composer install
```

### 5. Скопіюйте .env файл

Виконайте команду:

```bash
docker compose exec -it app cp .env.example .env
```

### 6. Згенеруйте ключ для Laravel

Виконайте команду:

```bash
docker compose exec app php artisan key:generate
```

### 7. Запустіть міграцію

Виконайте команду:

```bash
docker compose exec app php artisan migrate
```

### 8. Встановіть залежності NodeJS

Виконайте команду:

```bash
docker compose exec app yarn install
```

### 9. Компіляція frontend

Виконайте команду:

```bash
docker compose exec app yarn dev
```

## Тестування

Перед початком тестування виконайте наступні налаштування.

Скопіюйте `.env.example` в `.env.testing`.

Замніть в `.env.testing` блок з підключенням до БД:
```dotenv
DB_CONNECTION=pgsql
DB_HOST=mw-db-test
DB_DATABASE=test_mw_db
DB_USERNAME=test_mw_user
DB_PASSWORD=test_mw_user_password
```

### 1. Запуск тестів

Щоб запустити тестування, виконайте:

```bash
docker compose exec app php artisan test
```

### 2. Запуск аналізатора коду PHPStan

Щоб запустити аналізатор, виконайте:

```bash
docker compose exec app ./vendor/bin/phpstan analyse --memory-limit=2G
```

### 3. Запуск тестів з coverage

Щоб запустити тестування, виконайте:

```bash
docker compose exec app php artisan test --coverage
```

### 4. Мутаційні тести

**Всі тести мають бути покриті мутаційними тестами.**

Щоб додати мутаційні тести обовʼязково додавайте метод `covers(...)` до ваших тестів.

Наприклад:
```php
covers(TodoController::class); // or mutates(TodoController::class);
 
it('list todos', function () {
    $this->getJson('/todos')->assertStatus(200);
});
```
Детальніше [тут](https://pestphp.com/docs/mutation-testing).

Щоб запустити тестування з мутаціями, виконайте:

```bash
docker compose exec app php artisan test --mutate --covered-only --min=100
```
Або в паралельному режимі:
```bash
docker compose exec app php artisan test --mutate --covered-only --min=100 --parallel
```

## DDEV Інсталяція

### Вимоги

- [DDEV](//ddev.readthedocs.io/en/stable/users/install/ddev-installation)
- [Docker Compose](//docs.docker.com/compose/install)

### Інсталяція

- Запуск DDEV. Це збілдить всі неодхідні конетйнери згідно налаштувань у
  [`.ddev/config.yaml`](.ddev/config.yaml)

    ```sh
    ddev start
    ```

- Згенеруйте ключ для Laravel та запустіть міграції:

    ```sh
    ddev artisan key:generate; ddev artisan migrate
    ```

- Для коректної роботи `octane` та `reverb` налаштуйте наступні змінні у [`.env`](.env):

    ```dotenv
    OCTANE_HTTPS=true

    REVERB_HOST=${DDEV_HOSTNAME}
    REVERB_PORT=8443
    REVERB_SCHEME=https
    ```

### Тестування

- Для запуску тестування потрбіно увімкнути `Xdebug` (він вимкнений за замовчуванням):

    ```sh
    ddev xdebug on
    ```

## Найменування

### Назви гілок
Вимоги описані у файлі

```
.validate-branch-namerc.json
```
Автовалідація імен гілок Git перед пушем їх у віддалений репозиторій
[validate-branch-name](https://www.npmjs.com/package/validate-branch-name) package

### Конвенція для комітів
Кожне повідомлення коміту має відповідати [конвенції комітів](https://www.conventionalcommits.org/).

Автоматична перевірка повідомлень комітів виконується через `commit-msg` git-хук. Усі налаштування описані у файлі

## Мерж-коміт із напівлінійною історією

Використовуйте лінійну історію git. Детальніше читайте у [документації](https://docs.gitlab.com/ee/user/project/merge_requests/methods/#merge-commit-with-semi-linear-history).

## Ліцензія

Цей проєкт ліцензований за ліцензією [MIT](https://opensource.org/licenses/MIT).
