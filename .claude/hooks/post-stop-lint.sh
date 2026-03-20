#!/usr/bin/env bash
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

changed_php=$(git diff --name-only --diff-filter=d HEAD -- '*.php' 2>/dev/null || true)
untracked_php=$(git ls-files --others --exclude-standard -- '*.php' 2>/dev/null || true)
all_php=$(echo -e "${changed_php}\n${untracked_php}" | sort -u | grep -v '^$' || true)

if [ -z "$all_php" ]; then
  exit 0
fi

file_count=$(echo "$all_php" | wc -l | tr -d ' ')

echo "$all_php" | xargs docker compose exec -T app ./vendor/bin/pint 2>/dev/null || true

echo "{\"systemMessage\": \"Pint: formatted ${file_count} PHP file(s)\"}"
