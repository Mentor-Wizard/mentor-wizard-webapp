# Git & PR Rules

## Commit Rules

- **NEVER create commits automatically** — only commit when explicitly requested by the user
- **NEVER push to remote** without explicit user request
- **NEVER force push** or run destructive git commands without explicit approval
- When changes are ready, inform the user and wait for their instruction
- Always show `git diff` or `git status` to let the user review before committing

## Pull Request Descriptions

- **NEVER mention AI tools** (Claude, Copilot, Gemini, etc.) in PR title or body
- **NEVER include change statistics** (file count, lines added/removed)
- **NEVER add test plan checklists** — there is no QA team to execute them
- Keep PR descriptions focused on **what** changed and **why**
