#!/usr/bin/env bash
set -euo pipefail

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
gate="$repository_root/scripts/check-pinned-actions.sh"
temporary_root="$(mktemp -d)"
trap 'rm -rf "$temporary_root"' EXIT

create_workflow() {
    local repository="$1"
    local action_ref="$2"

    mkdir -p "$repository/.github/workflows" "$repository/scripts"
    cp "$gate" "$repository/scripts/check-pinned-actions.sh"
    chmod +x "$repository/scripts/check-pinned-actions.sh"
    cat > "$repository/.github/workflows/ci.yml" <<YAML
name: CI
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: $action_ref
YAML
}

clean_repository="$temporary_root/clean"
create_workflow "$clean_repository" 'actions/checkout@d23441a48e516b6c34aea4fa41551a30e30af803 # v6'
"$clean_repository/scripts/check-pinned-actions.sh" "$clean_repository"

local_repository="$temporary_root/local"
create_workflow "$local_repository" './.github/actions/setup'
"$local_repository/scripts/check-pinned-actions.sh" "$local_repository"

mutable_refs=(
    'actions/checkout@v6'
    'shivammathur/setup-php@v2'
    'actions/setup-node@main'
    'actions/setup-node@49933ea'
)

for mutable_ref in "${mutable_refs[@]}"; do
    repository="$temporary_root/$(printf '%s' "$mutable_ref" | tr '/@.' '____')"
    create_workflow "$repository" "$mutable_ref"

    if "$repository/scripts/check-pinned-actions.sh" "$repository" >/dev/null 2>&1; then
        printf 'CI action policy accepted mutable ref: %s\n' "$mutable_ref"
        exit 1
    fi
done

printf 'Pinned action policy passed 2 clean and %d controlled-negative cases.\n' "${#mutable_refs[@]}"