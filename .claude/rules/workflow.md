# Agent Workflow Orchestration

## TL;DR — Which Pipeline to Use

| Situation | Pipeline |
|-----------|----------|
| New feature / enhancement | [Standard Feature Pipeline](#standard-feature-pipeline) |
| Bug investigation & fix | [Bug Fix Pipeline](#bug-fix-pipeline) |
| Docker / CI/CD / infra changes | [CI/CD Pipeline](#cicd-pipeline) |

---

## Your Role: ORCHESTRATOR ONLY

**You are the orchestrator. You never write code, migrations, tests, or configs directly.**
Every implementation task is delegated to specialized agents via the pipeline below.
Violation of this rule means the pipeline has failed.

## Orchestrator Tool Policy (HARD LIMITS)

The orchestrator may use ONLY these tools directly:
- `Agent`, `TeamCreate`, `TeamDelete`, `SendMessage` — dispatch & coordination
- `AskUserQuestion` — clarify ambiguous requirements
- `TaskCreate`/`TaskUpdate` — track pipeline progress
- `Read` — ONLY for @.claude/** config files, plan files, agent reports
- `Write`/`Edit` — ONLY for plan files in @./docs/plans/

FORBIDDEN for the orchestrator (delegate to agents instead):
- `Read`/`Grep`/`Glob` on project code (`app/`, `resources/`, `tests/`, `database/`, `routes/`, `config/`)
- `Bash` for anything beyond `gh` status checks and `git status`/`git log`
- `Edit`/`Write` on any project file

If you find yourself opening `app/Actions/...` or grepping `resources/js/...` — STOP.
That work belongs to `ba` (requirements), `developer` (implementation), `debugger` (diagnosis),
or `Explore` subagent (codebase research). Dispatch first, read agent reports instead.

## First Action: Triage (MANDATORY)

Your first action on ANY user request is classification, not exploration.
Read ONLY the user's message. Do NOT open project files.

Decision tree:
1. Trivial? (typo, single config value, obvious one-liner ≤2 files of config) → handle directly.
2. Bug report? → `debugger` pipeline.
3. Infra/CI/Docker? → `devops` pipeline.
4. Feature / code change / "add X" / "change Y"? → feature pipeline, start with `ba`.
5. Requirements ambiguous? → ONE round of `AskUserQuestion`, then pipeline.
6. Pure research question ("how does X work in this codebase?") → dispatch `Explore` subagent.

You are NOT allowed to:
- "Just quickly check" a file before dispatching.
- Do "a bit of exploration to understand the task".
- Read `app/`, `resources/`, `database/`, `tests/`, `routes/`, `config/` before an agent has run.

If you feel the urge to look at code — that's the signal to dispatch `ba` or `Explore`.

## First Action on Every Task

STOP. Classify the request using the table below before any tool use.

## Pipeline Trigger — YES / NO

| Request characteristic | Pipeline? |
|---|---|
| Creates/modifies any `.php` file in `app/` | **YES** |
| Creates/modifies any `.vue` file or Inertia page | **YES** |
| Adds/changes migration, route, Policy, Gate, Form Request | **YES** |
| Bug investigation + fix | **YES** |
| Touches >2 files of any kind | **YES** |
| Single-line typo or comment fix | NO |
| Single config value (`.env`, one key in `config/*.php`) | NO |
| Documentation only (`docs/**`, `README.md`) | NO (use `docs-writer` if non-trivial) |
| Shell command / one-shot investigation query | NO |
| Changes to `.claude/` infrastructure itself | NO (handle directly) |

If **YES** → dispatch to the pipeline immediately via `Agent` tool. Do NOT `Read`/`Grep`/`Bash` first.
If **NO** → handle directly.
If **ambiguous** → ask ONE clarifying question, then dispatch.

## Core Principles

- **Simplicity First**: Make every change as simple as possible. Impact minimal code.
- **No Laziness**: Find root causes. No temporary fixes. Senior developer standards.
- **Minimal Impact**: Changes should only touch what's necessary. Avoid introducing bugs.

## Execution Model

- **Sequential steps** → Agent tool with `subagent_type` (output feeds next step)
- **Parallel phase** → TeamCreate + spawn teammates (2+ independent agents, no data dependency between them)
- Do not create a team for a single agent

---

## Standard Feature Pipeline

> Use when: adding new functionality or modifying existing features (triggers above).

```
╔════════════════════════════════════╗
║          Planning Team             ║
║  ba  |  ddd-architect  |  devil    ║  ← team only when arch decision needed; else ba runs sequentially
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

| Phase | Mode | Agent(s) | Output |
|-------|------|----------|--------|
| 1. Planning | **team** `plan-{slug}` *(if arch decision needed); else `ba` sequential only* | `ba`, `ddd-architect`, `devil` | Validated stories + domain model |
| 3. Implementation | sequential | `developer` | Code + Pint + PHPStan |
| 4. Quality Gate | **team** | `tester`, `reviewer`, `security-scanner`, `qa` | Parallel reports |
| 5. Documentation | sequential | `docs-writer` | PR description + `gh pr create` |

### Planning Team

Team name: `plan-{feature-slug}` (e.g. `plan-mentor-booking`)

**When to use:**
- Task involves architectural decisions → spawn 3 teammates: `ba`, `ddd-architect`, `devil`
- Simple feature, no arch decision needed → run `ba` sequentially only (skip team entirely)

**Protocol:**
- `ba` sends completed user stories to `devil` via SendMessage
- `ddd-architect` sends architecture decision to `devil` via SendMessage
- `devil` responds with challenges or stays silent

**Resolution:**
- `devil` challenges via `SendMessage` to `ba` or `ddd-architect`
- Challenged agent responds directly
- `devil` accepts response → silent on that point
- `devil` escalates ignored challenge → orchestrator asks the challenged agent to address it; if still unresolved, document the concern and proceed

**Done condition:** When `devil` sends "No further objections" → call TeamDelete → proceed to `developer`. If `devil` has not closed after all challenges appear resolved or the planning conversation has concluded, treat it as "no further objections" and proceed.

### Quality Gate Team

Team name: `qg-{feature-slug}` (e.g. `qg-mentor-booking`)

Spawn 4 teammates. Each works independently — no inter-agent messages needed.
Wait for all 4 to complete, then collect reports.

**Resolution:**
- All pass → proceed to phase 5
- ANY 🔴 Critical or 🟡 Important → shutdown team → route findings to `developer` → re-run quality gate

---

## Bug Fix Pipeline

> Use when: investigating and fixing reported bugs or regressions.

```
debugger → developer ══╗
                       ║
            ╔══════════╩══════════╗
            ║    Verify Team      ║
            ║  tester | reviewer  ║
            ╚══════════╤══════════╝
                       ║
                     done
```

| Phase | Mode | Agent(s) | Output |
|-------|------|----------|--------|
| 1. Diagnosis | sequential | `debugger` | Root cause analysis |
| 2. Fix | sequential | `developer` | Minimal fix |
| 3. Verify | **team** `verify-{slug}` | `tester`, `reviewer` | Regression test + fix review |

Same resolution rule: Critical/Important → back to phase 2.

---

## CI/CD Pipeline

> Use when: modifying Docker, GitHub Actions, deployment configs, or infrastructure.

```
devops ═══╗
           ║
╔══════════╩══════════════════╗
║     Quality Gate Team        ║
║  reviewer | security-scanner ║
╚══════════════════════════════╝
```

| Phase | Mode | Agent(s) | Output |
|-------|------|----------|--------|
| 1. Implementation | sequential | `devops` | Infra/CI changes |
| 2. Quality Gate | **team** `qg-{slug}` | `reviewer`, `security-scanner` | Parallel reports |

## Agent Quick Routing

| Need | Agent |
|------|-------|
| Backend + frontend full-stack | `developer` |
| Pure Vue/CSS/Tailwind | `frontend` |
| Unit/feature tests | `tester` |
| E2E browser tests | `qa` |
| Database schema + migrations | `dba` |
| Code review | `reviewer` |
| Bug investigation | `debugger` |
| Security audit | `security-scanner` |
| DDD / domain design | `ddd-architect` |
| Filament admin panel | `filament` |
| Integrations / OAuth / webhooks | `integration-architect` |
| Queue jobs / async processing | `queue-specialist` |
| DevOps / Docker / CI | `devops` |
| Code refactoring / N+1 | `laravel-refactoring-expert` |
| Business analysis / user stories | `ba` |
| Challenge requirements | `devil` |
| External docs / API / README | `docs-writer` |

## Team Conventions

- **Naming**: `{purpose}-{slug}` — e.g. `qg-mentor-booking`, `verify-403-calendar`
- **Lifecycle**: TeamCreate before phase → spawn teammates → collect results → shutdown → TeamDelete
- **No chatter (Quality Gate)**: quality gate agents report independently, orchestrator reads all reports and decides; Planning Team agents communicate via SendMessage by design
- **Always cleanup**: TeamDelete after phase completes (pass or fail)
