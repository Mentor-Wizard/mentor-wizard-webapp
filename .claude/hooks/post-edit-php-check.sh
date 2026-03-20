#!/usr/bin/env bash
set -euo pipefail

input=$(cat)
file_path=$(echo "$input" | jq -r '.tool_input.file_path // empty' 2>/dev/null)

if [ -z "$file_path" ]; then
  exit 0
fi

# Only check PHP files
if [[ "$file_path" != *.php ]]; then
  exit 0
fi

# File must exist (already written/edited)
if [ ! -f "$file_path" ]; then
  exit 0
fi

warnings=()

# Check for declare(strict_types=1)
if ! grep -q 'declare(strict_types=1)' "$file_path"; then
  warnings+=("Missing declare(strict_types=1)")
fi

# Check for ->id usage (should use getKey())
id_lines=$(grep -n '->id\b' "$file_path" | grep -v 'getKey\|session_id\|user_id\|parent_id\|model_id\|->id(' | head -5 || true)
if [ -n "$id_lines" ]; then
  while IFS= read -r line; do
    line_num=$(echo "$line" | cut -d: -f1)
    warnings+=("Line $line_num: use getKey() instead of ->id")
  done <<< "$id_lines"
fi

# Check for DB:: facade usage (should use Eloquent)
db_lines=$(grep -n 'DB::' "$file_path" | head -3 || true)
if [ -n "$db_lines" ]; then
  while IFS= read -r line; do
    line_num=$(echo "$line" | cut -d: -f1)
    warnings+=("Line $line_num: prefer Eloquent over DB:: facade")
  done <<< "$db_lines"
fi

if [ ${#warnings[@]} -eq 0 ]; then
  exit 0
fi

basename_file=$(basename "$file_path")
warning_text=$(printf '\\n- %s' "${warnings[@]}")
context="⚠ Convention issues in ${basename_file}:${warning_text}"

echo "{\"hookSpecificOutput\": {\"hookEventName\": \"PostToolUse\", \"additionalContext\": \"$context\"}}"
