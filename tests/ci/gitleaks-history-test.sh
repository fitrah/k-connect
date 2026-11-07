#!/usr/bin/env bash
set -euo pipefail

scanner="${GITLEAKS_BIN:-gitleaks}"
fixture="$(mktemp -d)"
trap 'rm -rf "$fixture"' EXIT

git init -q "$fixture"
git -C "$fixture" config user.email 'ci@example.test'
git -C "$fixture" config user.name 'CI Gate Test'
printf 'GITLAB_TOKEN=%s%s%s\n' 'gl' 'pat-' '9zQ4pLm7Nx2Vr8Ks5Hj3' > "$fixture/secret.env"
git -C "$fixture" add secret.env
git -C "$fixture" commit -qm 'add controlled secret'
git -C "$fixture" rm -q secret.env
git -C "$fixture" commit -qm 'remove controlled secret'

if "$scanner" git --no-banner --redact --log-opts='--all' "$fixture" >/dev/null 2>&1; then
    echo 'Gitleaks accepted a controlled secret that remained in Git history.'
    exit 1
fi

echo 'Gitleaks rejected the controlled historical secret after deletion from HEAD.'
