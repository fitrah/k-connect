#!/usr/bin/env bash
set -euo pipefail

repository="${1:-.}"

tracked_files="$(git -C "$repository" ls-files)"
forbidden_files="$(printf '%s\n' "$tracked_files" | grep -E '(^|/)\.env($|\.)|(^|/)(node_modules|vendor|public/build|coverage|coverage-html|htmlcov)(/|$)|(^|/)bootstrap/cache/.+\.php$|(^|/)storage/framework/(cache/data|sessions|views)/.+|(^|/)storage/logs/.+|(^|/)\.phpunit\.cache(/|$)|(^|/).+\.(sqlite|log|pem|key|p12|pfx)$|(^|/)(id_rsa|id_ed25519)$' | grep -vE '(^|/)\.env\.example$|(^|/)\.gitignore$' || true)"

if [[ -n "$forbidden_files" ]]; then
    printf 'Forbidden tracked secret or generated artifact:\n%s\n' "$forbidden_files"
    exit 1
fi
