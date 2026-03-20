#!/usr/bin/env bash
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

status_parts=()

# Check Docker containers
if command -v docker &>/dev/null && docker compose ps --format json &>/dev/null 2>&1; then
  app_running=$(docker compose ps --format json 2>/dev/null | jq -r 'select(.Service=="app" and .State=="running") | .Service' 2>/dev/null || true)
  db_running=$(docker compose ps --format json 2>/dev/null | jq -r 'select(.Service=="postgres" and .State=="running") | .Service' 2>/dev/null || true)
  redis_running=$(docker compose ps --format json 2>/dev/null | jq -r 'select(.Service=="redis" and .State=="running") | .Service' 2>/dev/null || true)

  docker_status="Docker:"
  [ -n "$app_running" ] && docker_status="$docker_status ✓ app" || docker_status="$docker_status ✗ app"
  [ -n "$db_running" ] && docker_status="$docker_status, ✓ db" || docker_status="$docker_status, ✗ db"
  [ -n "$redis_running" ] && docker_status="$docker_status, ✓ redis" || docker_status="$docker_status, ✗ redis"

  status_parts+=("$docker_status")

  if [ -n "$app_running" ] && [ -n "$db_running" ] && [ -n "$redis_running" ]; then
    docker_ok="true"
  else
    docker_ok="false"
    status_parts+=("⚠ Some Docker services are down — run 'docker compose up -d'")
  fi
else
  docker_ok="false"
  status_parts+=("Docker: not available")
fi

# Git context
git_branch=$(git branch --show-current 2>/dev/null || echo "detached")
changed_count=$(git diff --name-only 2>/dev/null | wc -l | tr -d ' ')
untracked_count=$(git ls-files --others --exclude-standard 2>/dev/null | wc -l | tr -d ' ')

status_parts+=("Branch: $git_branch")
status_parts+=("Changed: $changed_count, Untracked: $untracked_count")

# Write env vars for Claude
if [ -n "${CLAUDE_ENV_FILE:-}" ]; then
  echo "DOCKER_RUNNING=$docker_ok" >> "$CLAUDE_ENV_FILE"
  echo "GIT_BRANCH=$git_branch" >> "$CLAUDE_ENV_FILE"
  echo "GIT_CHANGED_COUNT=$changed_count" >> "$CLAUDE_ENV_FILE"
fi

# Build system message
message=$(IFS=' | '; echo "${status_parts[*]}")
echo "{\"systemMessage\": \"$message\"}"
