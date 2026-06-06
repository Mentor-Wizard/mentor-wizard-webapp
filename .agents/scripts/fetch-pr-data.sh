#!/usr/bin/env bash

set -e

PR_NUMBER=$1

if [ -z "$PR_NUMBER" ]; then
  echo "Error: No PR number provided."
  exit 1
fi

# Fetch PR Metadata
PR_JSON=$(gh pr view "$PR_NUMBER" --json title,body 2>/dev/null || true)

if [ -z "$PR_JSON" ]; then
  echo "Error: Could not fetch metadata for PR #$PR_NUMBER."
  exit 1
fi

PR_TITLE=$(echo "$PR_JSON" | jq -r .title)
PR_BODY=$(echo "$PR_JSON" | jq -r .body)

# Fetch Diff
PR_DIFF=$(gh pr diff "$PR_NUMBER")

# Filter Diff (Exclusions: vendor/, node_modules/, public/build/, .css, .json)
# Using awk for robust chunk-based filtering
FILTERED_DIFF=$(echo "$PR_DIFF" | awk '
/^diff --git/ {
  if ($3 ~ /^(a\/vendor\/|a\/node_modules\/|a\/public\/build\/|.*\.css|.*\.json)/) {
    skip = 1
  } else {
    skip = 0
  }
}
{
  if (!skip) print $0
}')

# Output formatted data
echo "### PULL REQUEST #$PR_NUMBER"
echo "Title: $PR_TITLE"
echo "Description:"
echo "$PR_BODY"
echo ""
echo "---"
echo ""
echo "### FILTERED DIFF"
echo "$FILTERED_DIFF"
