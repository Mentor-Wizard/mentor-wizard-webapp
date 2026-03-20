#!/usr/bin/env bash
set -euo pipefail

input=$(cat)
file_path=$(echo "$input" | jq -r '.tool_input.file_path // empty' 2>/dev/null)
content=$(echo "$input" | jq -r '.tool_input.content // empty' 2>/dev/null)

if [ -z "$file_path" ]; then
  exit 0
fi

block() {
  echo "$1" >&2
  exit 2
}

basename_file=$(basename "$file_path")

# Block writing to .env (but allow .env.example, .env.ci, .env.testing)
if [[ "$basename_file" == ".env" ]]; then
  block "BLOCKED: Cannot write to .env directly. Use .env.example for templates."
fi

# Block sensitive file types
case "$basename_file" in
  *.pem|*.key|credentials.json|auth.json|service-account.json)
    block "BLOCKED: Cannot write credential/key files. Store secrets in environment variables."
    ;;
esac

if [ -z "$content" ]; then
  exit 0
fi

# Scan content for hardcoded secrets
if echo "$content" | grep -qE 'AKIA[0-9A-Z]{16}'; then
  block "BLOCKED: AWS Access Key detected in file content. Use environment variables instead."
fi

if echo "$content" | grep -qE 'sk-[a-zA-Z0-9]{20,}'; then
  block "BLOCKED: API secret key (OpenAI/Stripe pattern) detected. Use environment variables instead."
fi

if echo "$content" | grep -qE 'ghp_[a-zA-Z0-9]{36}'; then
  block "BLOCKED: GitHub Personal Access Token detected. Use environment variables instead."
fi

if echo "$content" | grep -qE '\-\-\-\-\-BEGIN (RSA |EC )?PRIVATE KEY\-\-\-\-\-'; then
  block "BLOCKED: Private key detected in file content. Store keys in secure vault."
fi

exit 0
