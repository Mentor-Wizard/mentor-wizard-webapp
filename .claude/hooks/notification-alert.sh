#!/usr/bin/env bash
set -euo pipefail

input=$(cat)
message=$(echo "$input" | jq -r '.message // "Claude Code needs your attention"' 2>/dev/null)

# Truncate long messages for notification
message="${message:0:200}"

# macOS desktop notification with sound
if command -v osascript &>/dev/null; then
  osascript -e "display notification \"$message\" with title \"Claude Code\" sound name \"Glass\"" 2>/dev/null || true
fi

exit 0
