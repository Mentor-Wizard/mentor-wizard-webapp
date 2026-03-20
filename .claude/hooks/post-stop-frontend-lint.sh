#!/usr/bin/env bash
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

changed_fe=$(git diff --name-only --diff-filter=d HEAD -- '*.vue' '*.ts' '*.js' 2>/dev/null || true)
untracked_fe=$(git ls-files --others --exclude-standard -- '*.vue' '*.ts' '*.js' 2>/dev/null || true)
all_fe=$(echo -e "${changed_fe}\n${untracked_fe}" | sort -u | grep -v '^$' || true)

if [ -z "$all_fe" ]; then
  exit 0
fi

file_count=$(echo "$all_fe" | wc -l | tr -d ' ')

# Run Prettier
echo "$all_fe" | xargs docker compose exec -T app npx prettier --write 2>/dev/null || true

# Run ESLint with --fix
echo "$all_fe" | xargs docker compose exec -T app npx eslint --fix 2>/dev/null || true

echo "{\"systemMessage\": \"Frontend: formatted ${file_count} Vue/TS/JS file(s)\"}"
