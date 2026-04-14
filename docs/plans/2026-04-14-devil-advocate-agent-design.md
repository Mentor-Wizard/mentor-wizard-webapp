# Devil's Advocate Agent — Design

**Date:** 2026-04-14

## Problem

The current planning pipeline (`ba` → `ddd-architect`) lacks an independent challenger. Requirements may contain hidden scope creep or false assumptions. Architecture decisions may have unexamined failure scenarios. Without an external skeptic, these issues surface during implementation or in production.

## Solution

A local agent `.claude/agents/devil.md` that lives inside planning teams and reactively challenges both requirements (from `ba`) and architecture decisions (from `ddd-architect`) via `SendMessage`.

## Agent Identity

- **File:** `.claude/agents/devil.md`
- **Model:** `opus`
- **Color:** `red`
- **Role:** Constructive skeptic — read-only, never writes code

## Allowed Tools

```yaml
tools:
  - SendMessage
  - Read
  - Glob
  - Grep
```

Explicitly excluded: `Edit`, `Write`, `Bash`, `Agent`, `WebSearch`, `WebFetch`, `TaskCreate`, `TaskUpdate`.

## Behavior Protocol

1. `ba` publishes user stories → `devil` sends `SendMessage` to `ba` questioning requirements
2. `ddd-architect` publishes architecture → `devil` sends `SendMessage` to `ddd-architect` questioning decisions
3. If agent responds with a satisfying argument → `devil` accepts and goes silent on that point
4. If agent ignores the challenge → `devil` notifies the orchestrator

## Two Levels of Challenge

| Level | Target | Questions |
|-------|--------|-----------|
| Requirements | `ba` | Is this truly needed? Hidden scope creep? Edge cases missing? What if user wants X instead of Y? |
| Architecture | `ddd-architect` | Is this the simplest approach? Failure scenarios? Overly coupled? Why Action and not Event? |

## Pipeline Integration

```
╔═════════════════════════════════════╗
║           Planning Team             ║
║  ba  |  ddd-architect  |  devil     ║
╚═════════════════════════════════════╝
                  ║
              developer ═══╗
                            ║
              ╔═════════════╩═════════════╗
              ║      Quality Gate Team     ║
              ║  tester | reviewer |       ║
              ║  security-scanner | qa     ║
              ╚═════════════╤═════════════╝
                            ║
                      docs-writer
```

**Team naming:** `plan-{feature-slug}` (e.g. `plan-mentor-booking`)

## When to Include `devil`

| Condition | Include? |
|-----------|----------|
| Task involves architectural decisions | Yes |
| `ddd-architect` is in the pipeline | Yes |
| Typo fix / config change | No |
| Simple bug without architectural impact | No |

**Rule:** `devil` appears whenever `ddd-architect` appears.

## workflow.md Changes

Add `plan-{slug}` team to Standard Feature Pipeline. Replace sequential `ba → ddd-architect` steps with a `plan-{slug}` team containing all three agents. Orchestrator waits for all challenges to be resolved before proceeding to `developer`.