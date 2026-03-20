#!/usr/bin/env bash
set -euo pipefail

input=$(cat)
prompt=$(echo "$input" | jq -r '.prompt // empty' 2>/dev/null)

if [ -z "$prompt" ]; then
  exit 0
fi

# Extract issue numbers from prompt: #123, issue 123, Issue 123
issue_numbers=$(echo "$prompt" | grep -oE '(#|[Ii]ssue\s+)([0-9]+)' | grep -oE '[0-9]+' | sort -u)

if [ -z "$issue_numbers" ]; then
  exit 0
fi

gh_token="${MW_GITHUB_PERSONAL_ACCESS_TOKEN:-}"
if [ -z "$gh_token" ]; then
  exit 0
fi

context_lines=()

while IFS= read -r issue_num; do
  issue_data=$(GH_TOKEN="$gh_token" gh api "repos/Mentor-Wizard/mentor-wizard-webapp/issues/$issue_num" \
    --jq '{title: .title, state: .state, labels: [.labels[].name] | join(", ")}' 2>/dev/null || true)

  if [ -n "$issue_data" ]; then
    title=$(echo "$issue_data" | jq -r '.title')
    state=$(echo "$issue_data" | jq -r '.state')
    labels=$(echo "$issue_data" | jq -r '.labels')
    context_lines+=("- #${issue_num}: ${title} [${labels}] — ${state}")
  fi
done <<< "$issue_numbers"

if [ ${#context_lines[@]} -eq 0 ]; then
  exit 0
fi

context="Referenced issues:\\n$(printf '%s\\n' "${context_lines[@]}")"

# Escape for JSON
context=$(echo "$context" | sed 's/"/\\"/g')

echo "{\"hookSpecificOutput\": {\"hookEventName\": \"UserPromptSubmit\", \"additionalContext\": \"$context\"}}"
