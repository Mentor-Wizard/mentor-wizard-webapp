![Release](https://img.shields.io/github/v/release/Mentor-Wizard/mentor-wizard-webapp)
![Build](https://github.com/Mentor-Wizard/mentor-wizard-webapp/actions/workflows/ci.yml/badge.svg?branch=develop)
![Contributors](https://img.shields.io/github/contributors/Mentor-Wizard/mentor-wizard-webapp?branch=develop)
[![codecov](https://codecov.io/gh/Mentor-Wizard/mentor-wizard-webapp/graph/badge.svg?token=R65AS6PVOP)](https://codecov.io/gh/Mentor-Wizard/mentor-wizard-webapp)

# Mentor Wizard

Mentor Wizard - сучасна платформа для менторингу, побудована на Laravel 12,
Vue.js та Inertia.js. Система забезпечує повноцінне управління менторськими
програмами, сесіями, профілями та комунікацією між менторами та учнями.

## 🚀 Основні можливості

- 👥 **Управління профілями** - менторів та учнів
- 📚 **Менторські програми** - створення, публікація, управління
- 💬 **Чат-система** - комунікація в реальному часі
- ⭐ **Система рейтингів** - відгуки та оцінки
- 🔐 **Автентифікація** - стандартна та через соціальні мережі (OAuth)
- 🎨 **Сучасний UI** - з використанням Tailwind CSS та Inertia.js

## 🛠 Технологічний стек

### Backend

- **Laravel 12** - PHP фреймворк
- **Laravel Octane** (FrankenPHP) - високопродуктивний сервер
- **PostgreSQL 17** - база даних
- **Redis 7.2+** - кешування та черги

### Frontend

- **Vue.js 3** - прогресивний JavaScript фреймворк
- **Inertia.js** - modern monolith архітектура
- **Tailwind CSS** - utility-first CSS фреймворк
- **Yarn 4.6.0** - менеджер пакетів

### Інструменти розробки

- **Pest PHP** - фреймворк для тестування
- **PHPStan** - статичний аналізатор
- **Laravel Pint** - форматування коду
- **Rector** - автоматична модернізація коду
- **Docker** - контейнеризація

## 📋 Вимоги

- **PHP 8.5+**
- **Composer**
- **Node.js 24+** з **Yarn 4.15+**
- **Docker & Docker Compose**
- **PostgreSQL 17**
- **Redis 7.2+**

## ⚡ Швидкий старт

Детальні інструкції з налаштування та розгортання проєкту доступні у
**[docs/CONTRIBUTING.md](docs/CONTRIBUTING.md#2-налаштування-середовища-розробки)**.

## 📖 Документація

Детальна документація доступна в директорії `docs/`:

- **[CONTRIBUTING.md](docs/CONTRIBUTING.md)** - гід для контриб'юторів
- **[ACTIONS_ARCHITECTURE.md](docs/ACTIONS_ARCHITECTURE.md)** - архітектура
  Laravel Actions
- **[FRONTEND_ARCHITECTURE.md](docs/FRONTEND_ARCHITECTURE.md)** - архітектура
  Vue.js + Inertia.js
- **[TESTING_STRATEGY.md](docs/TESTING_STRATEGY.md)** - стратегія тестування
- **[SECURITY_GUIDELINES.md](docs/SECURITY_GUIDELINES.md)** - рекомендації з
  безпеки
- **[AUTHORIZATION_POLICIES.md](docs/AUTHORIZATION_POLICIES.md)** - політики
  авторизації
- **[NAMING_CONVENTIONS.md](docs/NAMING_CONVENTIONS.md)** - конвенції іменування

## 🤝 Для контриб'юторів

Ознайомтеся з **[docs/CONTRIBUTING.md](docs/CONTRIBUTING.md)** для детальних
інструкцій щодо:

- Налаштування середовища розробки та Git hooks
- Workflow розробки (Git Flow)
- Тестування (unit, feature, mutation tests)
- Стандарти якості коду
- Процес code review та створення pull requests

### Конвенції

**Назви гілок:**

- `feature/*` - нові функції
- `bugfix/*` - виправлення багів
- `hotfix/*` - критичні виправлення

**Commit повідомлення:** Використовуємо
[Conventional Commits](https://www.conventionalcommits.org/):

```
feat(auth): add user avatar upload
fix(mentor): resolve program validation
docs(api): update authentication endpoints
```

**Git merge стратегія:** Використовуємо merge commit з напівлінійною історією.
Детальніше у
[документації GitLab](https://docs.gitlab.com/ee/user/project/merge_requests/methods/#merge-commit-with-semi-linear-history).

## 🔧 Laravel Boost MCP

Проект налаштовано для роботи з Laravel Boost MCP сервером для AI-асистентів
(Claude, Cursor тощо):

```bash
# MCP конфігурація
.junie/mcp/mcp.json
```

**Доступні інструменти:**

- Database queries, schema, connections
- Config та environment variables
- Artisan commands
- Tinker для debugging
- Log entries та browser logs
- Routes та URL generation
- Documentation search

## 📝 Ліцензія

Проєкт ліцензовано за [MIT License](https://opensource.org/licenses/MIT).

---

**Зроблено з ❤️ командою Mentor Wizard**
