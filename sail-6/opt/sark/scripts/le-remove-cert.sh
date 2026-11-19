#!/bin/bash
# Drop Let's Encrypt as the active identity and fall back (custom if present, else snakeoil).
# Does not delete /etc/letsencrypt archives (safe; re-issue can reuse).
# Usage: le-remove-cert.sh
# Run as root.

set -e
SCRIPT_DIR="$(dirname "$(readlink -f "$0" 2>/dev/null || echo "$0")")"
# shellcheck source=le-acme-common.sh
. "$SCRIPT_DIR/le-acme-common.sh"
APPLY_SCRIPT="$SCRIPT_DIR/apply-active-cert.sh"

ensure_identity_dir
rm -f "$LE_DOMAIN_FILE" "$LE_EMAIL_FILE"
le_run_script "$APPLY_SCRIPT"
echo "le-remove-cert: LE domain marker cleared; apply-active-cert ran"
