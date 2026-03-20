#!/usr/bin/env bash
set -euo pipefail

input=$(cat)
agent_name=$(echo "$input" | jq -r '.agent_name // "unknown"' 2>/dev/null)
session_id=$(echo "$input" | jq -r '.session_id // "unknown"' 2>/dev/null)

timestamp=$(date '+%Y-%m-%d %H:%M:%S')
log_file="/tmp/claude-agents.log"

echo "[$timestamp] session=${session_id:0:8} agent=$agent_name" >> "$log_file"

exit 0
