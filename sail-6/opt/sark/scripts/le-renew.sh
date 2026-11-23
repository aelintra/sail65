#!/bin/bash
# Renew Let's Encrypt certificates and re-apply active cert (Apache + Asterisk).
# Used by Cert panel and /etc/cron.d/sark. Run as root.
# Opens Shorewall tcp/80 for the challenge window, then closes it (PBX3-style).
# No-op when LE is not elected (no le-domain) — local/snakeoil/vendor cert installs.

set -e
SCRIPT_DIR="$(dirname "$(readlink -f "$0" 2>/dev/null || echo "$0")")"
# shellcheck source=le-acme-common.sh
. "$SCRIPT_DIR/le-acme-common.sh"
APPLY_SCRIPT="$SCRIPT_DIR/apply-active-cert.sh"
OPEN_SCRIPT="$SCRIPT_DIR/le-port80-open.sh"
CLOSE_SCRIPT="$SCRIPT_DIR/le-port80-close.sh"

# Skip entirely if this host is not using Let's Encrypt.
if [ ! -s "$LE_DOMAIN_FILE" ]; then
	exit 0
fi

close_on_exit() {
	le_run_script "$CLOSE_SCRIPT" 2>/dev/null || true
}
trap close_on_exit EXIT

if ! command -v certbot >/dev/null 2>&1; then
	echo "ERROR: certbot not installed." >&2
	exit 1
fi

le_require_apache_acme
ensure_le_webroot
le_run_script "$OPEN_SCRIPT"

# Always re-apply after renew so LE tree permissions + Asterisk :8089 stay correct.
certbot renew --quiet --deploy-hook "/bin/bash $APPLY_SCRIPT" 2>&1

# If nothing was due, still ensure apply state is consistent (permissions / sites).
le_run_script "$APPLY_SCRIPT"
