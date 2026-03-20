#!/usr/bin/env bash
set -euo pipefail

input=$(cat)
command=$(echo "$input" | jq -r '.tool_input.command // empty' 2>/dev/null)

if [ -z "$command" ]; then
  exit 0
fi

block() {
  echo "$1" >&2
  exit 2
}

# Force push — forbidden by git-operations.md
echo "$command" | grep -qiE 'git\s+push\s+.*(-f|--force)' && \
  block "BLOCKED: git push --force is forbidden. Use regular git push instead."

# Hard reset without specific file — loses uncommitted changes
echo "$command" | grep -qiE 'git\s+reset\s+--hard' && \
  block "BLOCKED: git reset --hard can destroy uncommitted work. Use git stash or git checkout <file> instead."

# git clean -fd — removes untracked files
echo "$command" | grep -qiE 'git\s+clean\s+-[a-z]*f' && \
  block "BLOCKED: git clean -f removes untracked files permanently. Review files manually first."

# rm -rf dangerous paths
echo "$command" | grep -qiE 'rm\s+-[a-z]*r[a-z]*f[a-z]*\s+(/|\.|\*)' && \
  block "BLOCKED: rm -rf with dangerous path. Specify exact files to remove."

# Docker volumes destruction
echo "$command" | grep -qiE 'docker\s+compose\s+down\s+.*-v' && \
  block "BLOCKED: docker compose down -v destroys database volumes. Use 'docker compose down' without -v."

# Database destruction commands
echo "$command" | grep -qiE 'migrate:fresh|migrate:reset' && \
  block "BLOCKED: migrate:fresh/reset destroys all tables. Use migrate or migrate:rollback instead."

# SQL destruction via artisan
echo "$command" | grep -qiE 'DROP\s+TABLE|TRUNCATE\s+' && \
  block "BLOCKED: DROP TABLE / TRUNCATE detected. These destroy data irreversibly."

exit 0
