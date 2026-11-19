#!/usr/bin/env bash
set -euo pipefail

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
gate="$repository_root/scripts/check-repository-hygiene.sh"
temporary_root="$(mktemp -d)"
trap 'rm -rf "$temporary_root"' EXIT

create_repository() {
    local path="$1"

    git init -q "$path"
    git -C "$path" config user.email 'ci@example.test'
    git -C "$path" config user.name 'CI Gate Test'
    mkdir -p "$path/scripts"
    cp "$gate" "$path/scripts/check-repository-hygiene.sh"
    chmod +x "$path/scripts/check-repository-hygiene.sh"
    printf 'APP_NAME=K-Connect\n' > "$path/.env.example"
    git -C "$path" add .
}

clean_repository="$temporary_root/clean"
create_repository "$clean_repository"
"$clean_repository/scripts/check-repository-hygiene.sh" "$clean_repository"

forbidden_cases=(
    '.env'
    'public/build/manifest.json'
    'bootstrap/cache/config.php'
    'storage/framework/views/rendered.php'
    'coverage/clover.xml'
)

for forbidden_file in "${forbidden_cases[@]}"; do
    repository="$temporary_root/$(printf '%s' "$forbidden_file" | tr '/.' '__')"
    create_repository "$repository"
    mkdir -p "$(dirname "$repository/$forbidden_file")"
    printf 'controlled-negative-fixture\n' > "$repository/$forbidden_file"
    git -C "$repository" add -f "$forbidden_file"

    if "$repository/scripts/check-repository-hygiene.sh" "$repository" >/dev/null 2>&1; then
        printf 'Gate accepted forbidden tracked file: %s\n' "$forbidden_file"
        exit 1
    fi
done

printf 'Repository hygiene gate passed 1 clean and %d controlled-negative cases.\n' "${#forbidden_cases[@]}"
