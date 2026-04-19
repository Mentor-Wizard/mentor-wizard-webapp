---
model: opus
---

Review a Pull Request in the Mentor-Wizard GitHub organization by dispatching the `reviewer` agent. The agent fetches, analyzes, and posts the review itself — the orchestrator only parses the input and summarizes the agent's return.

## Input

The user provided: `$ARGUMENTS`

## Step 1: Parse the PR reference

Supported formats:
- `123` or `#123` — PR in current repo (detect via `git remote`)
- `repo#123` — PR in `Mentor-Wizard/repo`
- `Mentor-Wizard/repo#123` — full org/repo path
- `https://github.com/Mentor-Wizard/mentor-wizard-webapp/pull/123` — full URL

If the input is empty or cannot be parsed, ask the user for the PR reference.

## Step 2: Dispatch the reviewer agent

Launch the `reviewer` agent (`subagent_type: "reviewer"`) with the prompt below. These instructions override the agent's defaults for Mentor-Wizard PR conventions.

> Review PR #<NUMBER> in `Mentor-Wizard/<REPO>`. Fetch data, analyze, post the review yourself. Return only the final summary — do not stream findings back to the orchestrator.
>
> **Fetch in parallel:**
> - Metadata: `GH_TOKEN=$MW_GITHUB_PERSONAL_ACCESS_TOKEN gh api repos/Mentor-Wizard/<REPO>/pulls/<NUMBER>`
> - Diff: `GITHUB_TOKEN=$MW_GITHUB_PERSONAL_ACCESS_TOKEN gh pr diff <NUMBER> --repo Mentor-Wizard/<REPO>`
> - CI checks: `GITHUB_TOKEN=$MW_GITHUB_PERSONAL_ACCESS_TOKEN gh pr checks <NUMBER> --repo Mentor-Wizard/<REPO>`
> - Existing comments: `GH_TOKEN=$MW_GITHUB_PERSONAL_ACCESS_TOKEN gh api repos/Mentor-Wizard/<REPO>/pulls/<NUMBER>/comments`
>
> **Context:** Read the actual changed files from the local codebase (not just the diff). Consult `CLAUDE.md` and `.claude/rules/{architecture,code-style,testing,git-operations}.md`.
>
> **PR comment conventions (override defaults):**
> - Comment bodies posted to GitHub must use plain-text severity labels: `Critical:`, `Important:`, `Suggestion:` — no emojis
> - NEVER mention AI, Claude, LLM, automation, or that this is an automated review
> - All comments in English, in natural human engineer tone
> - Inline (line-level) comments only — never a single summary comment
> - Do not duplicate existing comments on the same line
>
> **Post the review in one API call:**
> ```bash
> SHA=$(GH_TOKEN=$MW_GITHUB_PERSONAL_ACCESS_TOKEN gh api repos/Mentor-Wizard/<REPO>/pulls/<NUMBER> --jq '.head.sha')
>
> GH_TOKEN=$MW_GITHUB_PERSONAL_ACCESS_TOKEN gh api repos/Mentor-Wizard/<REPO>/pulls/<NUMBER>/reviews \
>   --method POST \
>   --field commit_id="$SHA" \
>   --field event="<COMMENT|REQUEST_CHANGES>" \
>   --field body="<one concise sentence — no emojis, no AI mention>" \
>   --field 'comments=[{"path":"<file>","line":<line>,"side":"RIGHT","body":"<comment>"}]'
> ```
> Use `side: "LEFT"` for deleted lines, `side: "RIGHT"` for added lines. Use `start_line` + `line` for multi-line comments. Use `event: "REQUEST_CHANGES"` if any critical findings were posted; otherwise `COMMENT`.
>
> **Return:** counts by severity (critical / important / suggestion) and the review event type. Nothing else.

## Step 3: Present summary to user

Based on the agent's return, show:
- Comment counts by severity (critical / important / suggestion)
- Review event type (COMMENT or REQUEST_CHANGES)
- PR link: `https://github.com/Mentor-Wizard/<REPO>/pull/<NUMBER>`
