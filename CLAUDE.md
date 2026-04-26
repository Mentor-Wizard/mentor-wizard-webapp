## Agent Dispatch (MANDATORY)

**STOP. Before any tool use, classify this request using the table below.**

| Request type | Action |
|---|---|
| Any `.php` file in `app/` (Action, Service, Controller, Model…) | **Dispatch to pipeline** |
| Any `.vue` file or Inertia page | **Dispatch to pipeline** |
| Migration, route, Policy, Gate, Form Request | **Dispatch to pipeline** |
| Bug investigation + fix | **Dispatch to pipeline** |
| Touches >2 files of any kind | **Dispatch to pipeline** |
| Single-line typo or comment fix | Handle directly |
| Single config key (`.env`, one key in `config/*.php`) | Handle directly |
| Documentation only (`docs/**`, `README.md`) | Handle directly (use `docs-writer` if non-trivial) |
| Changes to `.claude/` infrastructure itself | Handle directly |

**If pipeline applies → dispatch via `Agent` tool immediately. Do NOT `Read`/`Grep`/`Bash` before dispatch.**
**If ambiguous → ask exactly ONE clarifying question, then dispatch.**

Full pipeline details: `.claude/rules/workflow.md`. Run independent pipeline steps in parallel. Never ask the user which agent to use — decide autonomously.

Available agents: `ba`, `developer`, `frontend`, `tester`, `qa`, `reviewer`, `debugger`, `security-scanner`, `dba`, `ddd-architect`, `devil`, `filament`, `devops`, `integration-architect`, `laravel-refactoring-expert`, `queue-specialist`, `docs-writer`

## Claude-Specific Behavior

- Use available Skills for Laravel code style, testing, architecture, Inertia, DevOps
- If a Skill applies, prefer it over repeating rules here

## IMPORTANT

1. Before starting any task, evaluate pipeline trigger conditions in `.claude/rules/workflow.md`. If pipeline applies — start it immediately.
2. If requirements are ambiguous, ask clarifying questions before starting the pipeline.
3. After finishing the pipeline, list edge cases and suggest additional test cases.
4. If a task requires changes to more than 3 files, stop and break it into smaller tasks.
5. When there's a bug, start by writing a test that reproduces it, then fix it.
6. Every time I correct you, reflect on what went wrong and plan to prevent it.

## Setup

See `docs/SETUP.md` for system requirements, Docker setup, and common commands.
