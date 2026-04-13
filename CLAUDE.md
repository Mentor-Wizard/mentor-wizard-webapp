## Claude-Specific Behavior

- Use available Skills for Laravel code style, testing, architecture, Inertia, DevOps
- If a Skill applies, prefer it over repeating rules here

## IMPORTANT

1. Before writing any code, describe your approach and wait for approval.
2. If requirements are ambiguous, ask clarifying questions before writing code.
3. After finishing code, list edge cases and suggest test cases.
4. If a task requires changes to more than 3 files, stop and break it into smaller tasks.
5. When there's a bug, start by writing a test that reproduces it, then fix it.
6. Every time I correct you, reflect on what went wrong and plan to prevent it.

## Agent Dispatch (MANDATORY)

Follow the pipeline in `.claude/rules/workflow.md`. Run independent steps in parallel. Never ask the user which agent to use — decide autonomously.

Available agents: `ba`, `developer`, `frontend`, `tester`, `qa`, `reviewer`, `debugger`, `security-scanner`, `dba`, `ddd-architect`, `filament`, `devops`, `ci-cd-engineer`, `integration-architect`, `laravel-refactoring-expert`, `queue-specialist`, `docs-writer`

## Setup

See `docs/SETUP.md` for system requirements, Docker setup, and common commands.
