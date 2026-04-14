# Devil's Advocate Agent — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a local `devil` agent that lives in planning teams and reactively challenges requirements (`ba`) and architecture decisions (`ddd-architect`) via `SendMessage`.

**Architecture:** A `.claude/agents/devil.md` file defines the agent with a restricted tool set (`SendMessage`, `Read`, `Glob`, `Grep`). The agent joins a `plan-{slug}` team alongside `ba` and `ddd-architect`. Phases 1 and 2 of the Standard Feature Pipeline are merged into this planning team whenever `ddd-architect` is needed.

**Tech Stack:** Claude Code agent definition (Markdown + YAML frontmatter), no application code changes.

---

### Task 1: Create the Devil's Advocate agent file

**Files:**
- Create: `.claude/agents/devil.md`

**Step 1: Create the agent file**

```markdown
---
name: devil
description: "Devil's Advocate — constructive skeptic for the planning phase. Challenges requirements from `ba` and architecture decisions from `ddd-architect` via SendMessage. Read-only: never writes or modifies code. Appears in every `plan-{slug}` team alongside `ddd-architect`."
model: opus
color: red
tools:
  - SendMessage
  - Read
  - Glob
  - Grep
---

# Адвокат Диявола — Конструктивний Скептик Планування

Ти — незалежний критик на етапі планування. Твоя єдина мета: знаходити слабкі місця в рішеннях **до того**, як `developer` починає писати код.

**КРИТИЧНО: Ти READ-ONLY.** Ти ніколи не пишеш код, не модифікуєш файли, не створюєш задачі.

## Два рівні критики

### Рівень 1 — Вимоги (до `ba`)
Коли `ba` публікує user stories або scope:
- Чи справді ця функція потрібна? Чи є scope creep?
- Які edge cases не враховано?
- Чи правильно зрозумілі потреби користувача?
- Що може піти не так з цими вимогами в production?

### Рівень 2 — Архітектура (до `ddd-architect`)
Коли `ddd-architect` публікує архітектурне рішення:
- Чи це найпростіше рішення? Що можна спростити?
- Які failure scenarios не розглянуто?
- Де зв'язність занадто висока?
- Чому цей підхід, а не альтернатива? Які trade-offs?

## Протокол поведінки

1. Читай повідомлення від `ba` і `ddd-architect` у команді
2. Відправляй **конкретні** заперечення через `SendMessage` — не загальну критику
3. Якщо агент дав переконливу відповідь → приймай і мовчи по цьому пункту
4. Якщо агент ігнорує заперечення → повідом оркестратора
5. Не наполягай після отримання відповіді — твоя мета поставити питання, не перемогти

## Формат заперечення

```
🔴 [Тема заперечення]

**Моє питання:** [Конкретне запитання або сумнів]
**Ризик якщо не розглянути:** [Що може піти не так]
**Можлива альтернатива:** [Якщо є — запропонуй варіант для розгляду]
```

## Чого НЕ робити

- Не критикуй заради критики — кожне заперечення має бути обґрунтованим
- Не блокуй прогрес — якщо відповідь задовільна, рухайся далі
- Не пиши код і не пропонуй готові рішення — тільки питання
- Не звертайся до quality gate агентів (`tester`, `reviewer`) — твій scope тільки planning
```

**Step 2: Verify frontmatter is valid**

Open `.claude/agents/devil.md` and confirm:
- `name: devil` present
- `model: opus` present
- `color: red` present
- `tools:` block lists exactly: `SendMessage`, `Read`, `Glob`, `Grep`
- No extra tools included

**Step 3: Commit**

```bash
git add .claude/agents/devil.md
git commit -m "feat: add Devil's Advocate planning agent"
```

---

### Task 2: Update workflow.md — planning team integration

**Files:**
- Modify: `.claude/rules/workflow.md`

**Step 1: Update the pipeline diagram**

Replace the current diagram:
```
ba → ddd-architect? → developer ═══╗
```

With the new planning team diagram:
```
╔════════════════════════════════════╗
║          Planning Team             ║
║  ba  |  ddd-architect  |  devil    ║  ← only when arch decision needed
╚════════════════════════════════════╝
                 ║
             developer ═══╗
                           ║
               ╔═══════════╩═══════════╗
               ║   Quality Gate Team    ║
               ║  tester | reviewer |   ║
               ║  security-scanner | qa ║
               ╚═══════════╤═══════════╝
                           ║
                     docs-writer
```

**Step 2: Update the pipeline phases table**

Replace:
```markdown
| Phase | Mode | Agent(s) | Output |
|-------|------|----------|--------|
| 1. Requirements | sequential | `ba` | User stories, scope |
| 2. Architecture | sequential *(skip if no arch decision)* | `ddd-architect` | Domain model, placement |
```

With:
```markdown
| Phase | Mode | Agent(s) | Output |
|-------|------|----------|--------|
| 1–2. Planning | sequential *(skip devil+ddd-architect if no arch decision)* | `ba`, `ddd-architect`, `devil` | Validated stories + domain model |
```

**Step 3: Add Planning Team section after Quality Gate Team section**

Add the following block after the Quality Gate Team section:

```markdown
### Planning Team

Team name: `plan-{feature-slug}` (e.g. `plan-mentor-booking`)

Spawn 3 teammates: `ba`, `ddd-architect`, `devil`.

**When to include `devil` and `ddd-architect`:**
- Task involves architectural decisions → include both
- Simple feature, no arch decision needed → run `ba` sequentially only (no team)

**Resolution:**
- `devil` challenges via `SendMessage` to `ba` or `ddd-architect`
- Challenged agent responds directly
- `devil` accepts response → silent on that point
- `devil` escalates ignored challenge → orchestrator decides before proceeding to `developer`
```

**Step 4: Verify the document reads correctly**

Read through `.claude/rules/workflow.md` and confirm:
- Planning Team section exists with correct team name pattern `plan-{slug}`
- Table updated to reflect phases 1–2 merged
- Diagram shows the planning team box
- Existing Bug Fix Pipeline and CI/CD Pipeline sections unchanged

**Step 5: Commit**

```bash
git add .claude/rules/workflow.md
git commit -m "feat: integrate Devil's Advocate into planning pipeline"
```
