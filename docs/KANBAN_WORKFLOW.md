# Kanban Workflow Guide

Цей документ описує правила роботи з GitHub Project Board для проєкту
MentorWizard. Всі учасники команди повинні дотримуватись цих правил.

## Модель роботи

Проєкт працює за моделлю **flow-based Kanban** (без спринтів). Задачі беруться в
роботу за пріоритетом, по мірі звільнення capacity.

## Колонки дошки

| Колонка         | Призначення                                      | WIP Limit |
| --------------- | ------------------------------------------------ | --------- |
| **Backlog**     | Все, що ще не в роботі. Issues з label `backlog` | -         |
| **Ready**       | Пріоритизовано, є assignee, зрозумілий scope     | 8         |
| **In Progress** | Активно розробляється, є відкрита гілка          | 5         |
| **In Review**   | PR створено, чекає на review                     | 3         |
| **Done**        | PR merged в `develop`                            | -         |

### WIP Limits

Work In Progress limits обмежують кількість задач, які одночасно перебувають в
колонці. Це запобігає перевантаженню команди.

- **In Progress: max 5** (по 1 на розробника)
- **In Review: max 3** (щоб review не накопичувались)
- **Ready: max 8** (достатньо задач для вибору, але не хаос)

**Правило:** Якщо ліміт вичерпано — спочатку завершити або review'нути поточне,
потім брати нове.

## Labels

### Priority Labels

Пріоритети визначають порядок, в якому задачі беруться в роботу:

| Label               | Колір     | Значення                          |
| ------------------- | --------- | --------------------------------- |
| `priority:critical` | Червоний  | Блокує інші задачі, робити першим |
| `priority:high`     | Оранжевий | Важливо для продукту              |
| `priority:medium`   | Жовтий    | Бажано, але не горить             |
| `priority:low`      | Синій     | Коли буде час                     |

### Size Labels (T-shirt sizing)

Оцінка обсягу роботи. Допомагає при плануванні та виборі задач:

| Label     | Обсяг                              |
| --------- | ---------------------------------- |
| `size:XS` | < 2 годин                          |
| `size:S`  | 2-4 години                         |
| `size:M`  | 1-2 дні                            |
| `size:L`  | 3-5 днів                           |
| `size:XL` | 1+ тиждень (потребує декомпозиції) |

> **Правило:** Issues з label `size:XL` мають бути розбиті на sub-issues перед
> тим, як потрапити в Ready.

### Type Labels

| Label              | Значення                                |
| ------------------ | --------------------------------------- |
| `type:new-page`    | Нова сторінка, якої ще немає            |
| `type:enhancement` | Покращення існуючого функціоналу        |
| `type:system`      | Системна фіча (пошук, нотифікації тощо) |
| `feature`          | Загальний feature                       |
| `ci`               | CI/CD та DevOps                         |
| `dependencies`     | Оновлення залежностей                   |

## Milestones

Milestones відстежують прогрес до ключових цілей:

| Milestone | Що входить                                                                  | Пріоритет issues                      |
| --------- | --------------------------------------------------------------------------- | ------------------------------------- |
| **MVP**   | Landing, Пошук менторів, Профіль, Бронювання, Категорії, Dashboard, Billing | `priority:critical` + `priority:high` |
| **Beta**  | Чат розширення, Нотифікації, Schedule Management, Favorites, Ratings        | `priority:medium`                     |
| **v1.0**  | Блог, Сертифікати, Newsletter, Help Center, Legal pages                     | `priority:low`                        |

## Workflow: Як працювати з задачами

### 1. Вибір задачі

1. Відкрийте Project Board
2. Перегляньте колонку **Ready** — задачі відсортовані за пріоритетом
3. Оберіть задачу з найвищим пріоритетом, яка не заблокована
4. Перевірте поле **Blocked by** — якщо задача залежить від іншої, та ще не
   завершена, оберіть іншу
5. Призначте себе assignee

### 2. Початок роботи

1. Перемістіть issue в колонку **In Progress**
2. Створіть feature гілку від `develop`:

   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/{issue-number}-short-description
   ```

3. Почніть розробку згідно з acceptance criteria в issue

### 3. Розробка

- Дотримуйтесь [CONTRIBUTING.md](./CONTRIBUTING.md) для стандартів коду
- Пишіть тести паралельно з кодом (TDD)
- Регулярно commit'те з
  [Conventional Commits](https://www.conventionalcommits.org/)

### 4. Створення PR

1. Push гілку та створіть Pull Request в `develop`
2. В описі PR вкажіть `Closes #xxx` для автоматичного закриття issue
3. Перемістіть issue в колонку **In Review**
4. Призначте reviewer'а

### 5. Review

- Reviewer перевіряє код протягом **1 робочого дня**
- Якщо є зауваження — автор виправляє, issue залишається в In Review
- Після approve — автор merge'ить PR

### 6. Завершення

- Після merge issue автоматично переміщується в **Done**
- Якщо issue не закрилось автоматично — закрийте вручну

Перед початком роботи перевіряйте, чи залежності задачі вже виконані. Якщо issue
блокується іншою:

1. Не переміщуйте її в Ready поки блокер не завершений
2. Якщо ви вже в процесі і виявили блокер — повідомте в коментарі до issue

## Definition of Done

Issue вважається завершеною коли **всі** пункти виконані:

1. Feature tests (Pest) написані та проходять
2. PHPStan level 7 без помилок
3. Laravel Pint без зауважень
4. Rector без зауважень
5. Code review approved (мінімум 1 reviewer)
6. PR merged в `develop`
7. CI pipeline зелений (всі checks pass)
8. Acceptance criteria з issue виконані

```bash
# Команди перевірки перед створенням PR
docker compose exec app php artisan test --compact
docker compose exec app ./vendor/bin/phpstan analyse
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/rector process --dry-run
```

## Правила для Issue Management

### Створення нових issues

Нові issues повинні містити:

1. **User Story** — "Як [роль], я хочу [дія], тому що [причина]"
2. **Технічні деталі** — файли, моделі, API endpoints
3. **Acceptance criteria** — чеклист критеріїв виконання
4. **Labels** — обов'язково `priority:*` та `type:*`
5. **Milestone** — обов'язково MVP / Beta / v1.0

### Закриття issues

- Issues закриваються тільки через merge PR з `Closes #xxx`
- Якщо issue стала неактуальною — закрийте як `not planned` з коментарем
- Якщо issue замінена новою — закрийте з коментарем `Superseded by #xxx`

### Коментарі в issues

- Використовуйте коментарі для:
  - Уточнення вимог
  - Повідомлення про блокери
  - Технічних обговорень
- Тегайте (@username) для привернення уваги

## Board Views (фільтри)

Рекомендовані views для щоденної роботи:

| View           | Фільтр                     | Призначення                       |
| -------------- | -------------------------- | --------------------------------- |
| My Tasks       | `assignee:@me`             | Ваші поточні задачі               |
| Critical Path  | `label:priority:critical`  | Задачі, що блокують інших         |
| Ready for Work | column: Ready, no assignee | Задачі, які можна взяти           |
| Blocked        | has "Blocked by"           | Задачі з незакритими залежностями |

## FAQ

### Що робити, якщо WIP limit досягнуто?

Допоможіть з review задач у колонці In Review, або допоможіть колегі завершити
задачу в In Progress.

### Як оцінити size задачі?

Порівняйте з вже завершеними задачами. Якщо не впевнені — поставте `size:M` як
default і скоригуйте після початку роботи.

### Що робити з блокером під час роботи?

1. Додайте коментар в issue
2. Перемістіть issue назад у Ready (або залиште в In Progress з позначкою)
3. Візьміть іншу задачу

### Хто може змінювати пріоритети?

Пріоритети встановлюються при створенні issue та можуть бути змінені tech
lead'ом або за узгодженням команди.
