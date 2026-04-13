# Agent Workflow Orchestration

## Core Principles

- **Simplicity First**: Make every change as simple as possible. Impact minimal code.
- **No Laziness**: Find root causes. No temporary fixes. Senior developer standards.
- **Minimal Impact**: Changes should only touch what's necessary. Avoid introducing bugs.

## Standard Feature Pipeline

Use when ANY applies:
- Creates or modifies a Laravel Action class
- Requires a database migration
- Adds or changes a route, controller, or Form Request
- Adds or changes a Vue component or Inertia page
- Involves authorization logic (Policy, Gate, middleware)
- Touches more than 2 files

If none apply (e.g. typo fix, config value) — skip the pipeline.

| Step | Agent                              | Output                                                  |
|------|------------------------------------|---------------------------------------------------------|
| 1    | `ba`                               | Requirements, user stories, scope                       |
| 2    | `ddd-architect` *(if arch decision)* | Domain model, Action vs Service vs Observer placement |
| 3    | `developer`                        | Working code + Pint + PHPStan                           |
| 4    | `tester`                           | Unit + feature tests, mutation testing                  |
| 5    | `reviewer`                         | Review report — loops back to 3–4 if Critical/Important |
| 6    | `security-scanner`                 | OWASP scan, auth/authz findings                         |
| 7    | `qa`                               | E2E browser results via Playwright                      |
| 8    | `docs-writer`                      | PR description + `gh pr create`                         |

Run independent steps in parallel where possible.

## CI/CD Tasks

Replace `developer` with `devops` (infra) or `ci-cd-engineer` (GitHub Actions workflows).

## Bug Fix Pipeline

1. `debugger` — root cause analysis
2. `developer` — implement fix
3. `tester` — regression test