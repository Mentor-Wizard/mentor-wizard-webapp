# Налаштування Claude Code для контриб'юторів

Цей документ описує, як налаштувати Claude Code для роботи над проєктом
`mentor-wizard-webapp`. Весь AI-assisted workflow побудований навколо Claude
Code CLI та мультиагентної системи.

## Передумови

| Інструмент          | Вимога                                                  |
| ------------------- | ------------------------------------------------------- |
| **Claude Code CLI** | Остання стабільна версія (`claude --version` ≥ 1.0)     |
| **Anthropic план**  | Pro або вище (для доступу до Opus-моделей в агентах)    |
| **Docker**          | Запущений `docker compose up -d` перед роботою з Claude |
| **Node.js + Yarn**  | Node 20+, Yarn 4.6.0 (для MCP через npx)                |

> **Важливо:** Stop-hooks в цьому проєкті автоматично запускають `pint:fix` та
> `rector:fix` після кожного Claude-сесії. Для їх роботи потрібен запущений
> Docker-стек.

## Встановлення Claude Code CLI

```bash
npm install -g @anthropic-ai/claude-code
```

Або через `brew` на macOS:

```bash
brew install anthropic/claude/claude-code
```

Авторизація:

```bash
claude
# Виконайте oauth flow у браузері
```

## Плагіни (обов'язково)

Проєкт використовує плагін **superpowers**, який надає систему скілів, агентів і
команд:

```bash
claude plugins install superpowers@superpowers-marketplace
```

Перевірка:

```bash
claude plugins list
# Має з'явитися: superpowers@superpowers-marketplace
```

## MCP-сервери

Проєкт визначає MCP-сервери у `.mcp.json` у корені репозиторію. Після
встановлення `enableAllProjectMcpServers: true` (вже є у
`.claude/settings.json`) всі сервери вмикаються автоматично — але деяким
потрібні env-змінні.

### Обов'язкові env-змінні

Додайте до `~/.zshrc` або `~/.bashrc`:

```bash
# Context7 — документація Laravel, Vue, PHP (безкоштовний ключ на upstash.com/context7)
export CONTEXT7_API_KEY="ctx7sk-your-key-here"
```

### Опціональні env-змінні

```bash
# Figma — тільки якщо ви працюєте з дизайн-макетами
export FIGMA_API_KEY="your-figma-api-key"
```

Для отримання `CONTEXT7_API_KEY` зареєструйтесь на
[upstash.com/context7](https://upstash.com/context7).

### Перевірка MCP

```bash
claude mcp list
# Має показати: laravel-boost ✓ Connected, context7 ✓ Connected
```

`laravel-boost` вимагає запущеного Docker-стеку (`docker compose up -d`).

## Hierarchy налаштувань Claude Code

```
~/.claude/settings.json          # Глобальні (ваш акаунт)
~/.claude/settings.local.json    # Глобальні особисті

.claude/settings.json            # Командні (commit'иться в репо)
.claude/settings.local.json      # Ваші локальні для цього проєкту (git-ignored)
```

`.claude/settings.json` вже містить:

- Скіли для проєкту (skillOverrides)
- Stop-hooks для code-style (pint + rector)
- MCP-сервери (enableAllProjectMcpServers)
- Модель за замовчуванням `opusplan`

### Що додавати у `.claude/settings.local.json`

Особисті permissions (щоб Claude не питав підтвердження на рутинні операції):

```json
{
  "permissions": {
    "allow": [
      "Bash(docker compose:*)",
      "Bash(git add:*)",
      "Bash(git commit:*)",
      "Bash(git push)",
      "Bash(ls:*)",
      "Bash(grep:*)",
      "mcp__laravel-boost__search-docs",
      "mcp__laravel-boost__application-info",
      "mcp__laravel-boost__database-schema",
      "mcp__laravel-boost__last-error",
      "mcp__laravel-boost__database-query"
    ]
  }
}
```

Особистий outputStyle (опціонально):

```json
{
  "outputStyle": "Concise"
}
```

## Перевірка налаштування

```bash
# 1. Версія CLI
claude --version

# 2. MCP-сервери
claude mcp list

# 3. Плагіни
claude plugins list

# 4. Тестовий запуск агента
claude --print "Summarize .claude/agents/reviewer.md in 2 sentences"
```

Очікуваний результат п.4: відповідь без помилок про `unknown model` чи
`missing MCP server`.

## Модельний плейбук

| Ситуація                       | Модель / Команда                                       |
| ------------------------------ | ------------------------------------------------------ |
| Стандартна робота (за замовч.) | `opusplan` — Opus для планування, Sonnet для виконання |
| Швидкий output, прості задачі  | `/fast` — Opus 4.6 з підвищеною швидкістю              |
| Агенти в `.claude/agents/`     | `opus` або `sonnet` (аліаси, автооновлення до 4.7)     |

> **Правило 4.7+**: **Не хардкодьте** `claude-opus-4-6` чи `claude-sonnet-4-6` у
> frontmatter агентів або скілів. Використовуйте аліаси (`opus`, `sonnet`,
> `opusplan`) — вони автоматично підхоплять 4.7 після релізу.

## Після виходу Opus 4.7 / Sonnet 4.7

Коли виходить нова модель, виконайте sanity-check:

- [ ] Запустити `reviewer` агента на маленькому PR і перевірити якість output
- [ ] Запустити `developer` агента на trivial-задачі та перевірити, що він не
      пише код напряму (делегує)
- [ ] Перевірити `devil` агента в planning-команді — чи правильно він отримує
      SendMessage
- [ ] Якщо виявлено деградацію якості — оновити промпти у відповідному
      `.claude/agents/*.md`

## Структура `.claude/` у проєкті

```
.claude/
├── agents/      # 17 спеціалізованих агентів (developer, tester, reviewer...)
├── commands/    # Slash-команди (/review-pr, /fix-ci)
├── rules/       # Правила стилю та процесу (завжди в контексті)
├── skills/      # Скіли (Laravel, Vue, Pest, DDD...)
├── settings.json       # Командні налаштування (commit'иться)
└── settings.local.json # Ваші особисті (git-ignored)
```

Повна документація агентного workflow: `CLAUDE.md` +
`.claude/rules/workflow.md`.
