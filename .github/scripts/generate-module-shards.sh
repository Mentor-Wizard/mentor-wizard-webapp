#!/usr/bin/env bash
set -euo pipefail

LOOKUP='.github/ci-module-shards.json'
DEFAULT_TIMEOUT=20

if [[ -f modules_statuses.json ]]; then
  mapfile -t MODULES < <(jq -r 'to_entries[] | select(.value == true) | .key' modules_statuses.json)
else
  echo "::warning::modules_statuses.json missing — falling back to Modules/*/ glob (enabled/disabled state unknown)"
  echo "modules_statuses.json missing — used directory glob fallback" >> "$GITHUB_STEP_SUMMARY"
  mapfile -t MODULES < <(find Modules -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | sort)
fi

if [[ ${#MODULES[@]} -eq 0 ]]; then
  echo "::error::No enabled modules found — modules_statuses.json/Modules glob produced zero candidates. Refusing to emit an empty shard matrix (would silently vanish the mutation-modules job instead of failing it)."
  exit 1
fi

SHARDS='[]'
for MODULE in "${MODULES[@]}"; do
  if [[ ! -d "Modules/${MODULE}/tests/Unit" && ! -d "Modules/${MODULE}/tests/Feature" ]]; then
    echo "::warning::Module ${MODULE} has no tests/Unit or tests/Feature — skipping shard (would be vacuous)"
    echo "- ${MODULE}: no tests directory, shard skipped" >> "$GITHUB_STEP_SUMMARY"
    continue
  fi

  TIMEOUT=$(jq -r --arg m "$MODULE" --argjson d "$DEFAULT_TIMEOUT" '.[$m].timeout // $d' "$LOOKUP")
  TESTSUITE=$(jq -r --arg m "$MODULE" '.[$m].testsuite // "Modules"' "$LOOKUP")
  IGNORE=$(jq -r --arg m "$MODULE" '.[$m].ignore // ""' "$LOOKUP")

  SHARDS=$(jq -c -n --argjson shards "$SHARDS" \
    --arg id "$MODULE" \
    --arg name "Module ${MODULE}" \
    --arg path "Modules/${MODULE}/app" \
    --argjson timeout "$TIMEOUT" \
    --arg testsuite "$TESTSUITE" \
    --arg ignore "$IGNORE" \
    '$shards + [{id: $id, name: $name, path: $path, timeout: $timeout, testsuite: $testsuite, ignore: $ignore}]')
done

if [[ "$SHARDS" == '[]' ]]; then
  echo "::error::All candidate modules were skipped (no tests/Unit or tests/Feature directory) — refusing to emit an empty shard matrix (would silently vanish the mutation-modules job instead of failing it)."
  exit 1
fi

MATRIX=$(jq -c -n --argjson shard "$SHARDS" '{shard: $shard}')

{
  echo "matrix<<CI_MODULE_SHARDS_EOF"
  echo "$MATRIX"
  echo "CI_MODULE_SHARDS_EOF"
} >> "$GITHUB_OUTPUT"
