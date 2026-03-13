# Agent Workflow Orchestration

## Standard Feature Pipeline

When executing a non-trivial feature task, follow this agent pipeline in order:

### Step 1: Analysis (BA Agent)
- Analyze the task requirements
- Break down into user stories with acceptance criteria
- Identify affected domains and dependencies
- Output: clear requirements, scope definition, implementation roadmap

### Step 2: Implementation (Developer Agent)
- Write backend + frontend code following the architecture
- Use Actions pattern, Inertia.js, Vue 3 Composition API
- Run Pint + PHPStan after code changes
- Output: working code changes

### Step 3: Security Review (Security Scanner Agent)
- Scan new code for OWASP Top 10 vulnerabilities
- Check auth/authz (Policies, Form Requests)
- Verify no credential leaks, no PII in logs
- Output: security findings with severity ratings

### Step 4: E2E Verification (QA Agent)
- Verify user flows work in browser via Playwright
- Check responsive design, accessibility
- Test integration points
- Output: E2E test results, screenshots if needed

### Step 5: Test Coverage (Tester Agent)
- Write unit tests for Actions, Services, Observers
- Write feature tests for HTTP endpoints
- Run mutation testing to verify test quality
- Output: test files, coverage report

### Step 6: Report & PR (DocsWriter Agent)
- Write a summary report of all changes made
- Create PR description (what changed, why, which files)
- Create the PR automatically via `gh pr create`
- PR description rules: no AI mentions, no stats, no test checklists

## Architecture Tasks

For tasks involving architectural decisions or domain modeling:
- Insert **DDD Architect** between BA (Step 1) and Developer (Step 2)
- DDD Architect designs domain model, decides logic placement (Action vs Service vs Observer)

## CI/CD Tasks

For tasks involving CI/CD, Docker, or deployment:
- Use **DevOps Agent** instead of Developer for infrastructure changes
- Use **CI/CD Engineer** for GitHub Actions workflow changes

## Bug Fix Pipeline (Simplified)

1. **Debugger Agent** — investigate root cause
2. **Developer Agent** — implement fix
3. **Tester Agent** — write regression test
4. Verify fix + existing tests pass

## General Rules

- Enter **plan mode** for ANY non-trivial task (3+ steps or architectural decisions)
- If something goes sideways, STOP and re-plan — don't keep pushing
- Use subagents liberally to keep main context clean
- Never mark a task complete without proving it works
- After ANY user correction: update `docs/lessons.md`
