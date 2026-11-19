#!/usr/bin/env bash
set -euo pipefail

repository="${1:-.}"
workflow_directory="$repository/.github/workflows"

if [[ ! -d "$workflow_directory" ]]; then
    exit 0
fi

violations=()

while IFS= read -r workflow; do
    while IFS= read -r action_line; do
        line_number="${action_line%%:*}"
        action_ref="${action_line#*:}"
        action_ref="${action_ref#*uses:}"
        action_ref="${action_ref%%#*}"
        action_ref="$(printf '%s' "$action_ref" | tr -d '[:space:]' | tr -d "'\"")"

        if [[ "$action_ref" == ./* || "$action_ref" == docker://* ]]; then
            continue
        fi

        if [[ ! "$action_ref" =~ ^[^@[:space:]]+@[0-9a-fA-F]{40}$ ]]; then
            violations+=("$workflow:$line_number: $action_ref")
        fi
    done < <(grep -nE '^[[:space:]-]*uses:[[:space:]]*' "$workflow" || true)
done < <(find "$workflow_directory" -type f \( -name '*.yml' -o -name '*.yaml' \) -print | sort)

if (( ${#violations[@]} > 0 )); then
    printf 'Mutable or invalid GitHub Action refs found; pin every remote action to a full 40-character commit SHA:\n'
    printf '%s\n' "${violations[@]}"
    exit 1
fi