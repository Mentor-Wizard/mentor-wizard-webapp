# План: Розгортання через Dokploy з використанням Docker Compose

Цей план описує кроки для налаштування автоматизованого розгортання в Dokploy з
використанням Docker Compose.

## Фаза 1: Підготовка конфігурації (Docker & Compose)

- [x] **Створення compose.prod.yml**
    - Створити файл `compose.prod.yml` на основі `compose.yml`.
    - Налаштувати використання зовнішніх образів (замість build).
    - Додати мережу `dokploy-network`.
    - Налаштувати лейбли Traefik для сервісів `app` та `websockets`.
    - Видалити dev-специфічні налаштування (volumes, ports mapping для локальної
      розробки).

- [ ] **Оптимізація Dockerfile**
    - Перевірити `docker/php/Dockerfile` на відповідність production вимогам.
      (Вже перевірено, виглядає добре, але варто переконатися, що всі необхідні
      розширення є).

## Фаза 2: GitHub Actions (CI/CD)

- [ ] **Налаштування секретів**
    - `GITHUB_TOKEN` (автоматично доступний для GHCR).
    - Dokploy секрети: `DOKPLOY_API_KEY`, `DOKPLOY_URL`.
    - (Опціонально) `DOCKER_REGISTRY` якщо не `ghcr.io`.

- [ ] **Створення Workflow `deploy.yml`**
    - Налаштувати трігери (push to main).
    - Job: Build & Push Docker Image to GHCR.
        - Image: `ghcr.io/${{ github.repository }}:main`.
    - Job: Trigger Dokploy Deployment (webhook).
        - Dokploy підтягне оновлений `ghcr.io/...:main`.

## Фаза 3: Налаштування Dokploy

- [ ] **Створення проекту в Dokploy**
    - Налаштувати проект типу "Docker Compose".
    - Додати змінні оточення (ENVs) з `.env.example` (адаптовані під прод).
    - Задати `DOCKER_IMAGE=ghcr.io/username/repo:main` (для Stage).
    - Вставити вміст `compose.prod.yml`.
    - Додати Registry Credentials в Dokploy (для доступу до GHCR, якщо приватний
      репо).

## Фаза 4: Тестування та Валідація

- [ ] **Тестовий деплой**
    - Запустити пайплайн.
    - Перевірити логи build та push.
    - Перевірити статус в Dokploy.
    - Перевірити доступність сайту.
