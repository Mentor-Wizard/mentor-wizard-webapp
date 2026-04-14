# Agent Workflow Orchestration

## Core Principles

- **Simplicity First**: Make every change as simple as possible. Impact minimal code.
- **No Laziness**: Find root causes. No temporary fixes. Senior developer standards.
- **Minimal Impact**: Changes should only touch what's necessary. Avoid introducing bugs.

## When to Use the Pipeline

Use when ANY applies:
- Creates or modifies a Laravel Action class
- Requires a database migration
- Adds or changes a route, controller, or Form Request
- Adds or changes a Vue component or Inertia page
- Involves authorization logic (Policy, Gate, middleware)
- Touches more than 2 files

If none apply (e.g. typo fix, config value) — skip the pipeline.

## Execution Model

- **Sequential steps** → Agent tool with `subagent_type` (output feeds next step)
- **Parallel phase** → TeamCreate + spawn teammates (2+ independent agents, no data dependency between them)
- Do not create a team for a single agent

## Standard Feature Pipeline

```
ba → ddd-architect? → developer ═══╗
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
| 1. Requirements | sequential | `ba` | User stories, scope |
| 2. Architecture | sequential *(skip if no arch decision)* | `ddd-architect` | Domain model, placement |
| 3. Implementation | sequential | `developer` | Code + Pint + PHPStan |
| 4. Quality Gate | **team** | `tester`, `reviewer`, `security-scanner`, `qa` | Parallel reports |
| 5. Documentation | sequential | `docs-writer` | PR description + `gh pr create` |

### Quality Gate Team

Team name: `qg-{feature-slug}` (e.g. `qg-mentor-booking`)

Spawn 4 teammates. Each works independently — no inter-agent messages needed.
Wait for all 4 to complete, then collect reports.

**Resolution:**
- All pass → proceed to phase 5
- ANY 🔴 Critical or 🟡 Important → shutdown team → route findings to `developer` → re-run quality gate

## Bug Fix Pipeline

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

## CI/CD Pipeline

Replace `developer` with `devops` (infra) or `ci-cd-engineer` (GitHub Actions).
Quality gate reduces to `reviewer` + `security-scanner` (no tester/qa for infra changes).

## Team Conventions

- **Naming**: `{purpose}-{slug}` — e.g. `qg-mentor-booking`, `verify-403-calendar`
- **Lifecycle**: TeamCreate before phase → spawn teammates → collect results → shutdown → TeamDelete
- **No chatter**: quality gate agents report independently, orchestrator reads all reports and decides
- **Always cleanup**: TeamDelete after phase completes (pass or fail)
