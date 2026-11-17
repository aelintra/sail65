#!/bin/bash
# Renew Let's Encrypt certificates and re-apply active cert (Apache + Asterisk).
# Used by Cert panel and /etc/cron.d/sark. Run as root.
# Port 80 is expected open via Shorewall (sark_rules ACCEPT net $FW tcp 80).

set -e
SCRIPT_DIR="$(dirname "$(readlink -f "$0" 2>/dev/null || echo "$0")")"
# shellcheck source=le-acme-common.sh
. "$SCRIPT_DIR/le-acme-common.sh"
APPLY_SCRIPT="$SCRIPT_DIR/apply-active-cert.sh"

if ! command -v certbot >/dev/null 2>&1; then
	echo "ERROR: certbot not installed." >&2
	exit 1
fi

le_require_apache_acme
ensure_le_webroot

# Always re-apply after renew so LE tree permissions + Asterisk :8089 stay correct.
certbot renew --quiet --deploy-hook "/bin/bash $APPLY_SCRIPT" 2>&1

# If nothing was due, still ensure apply state is consistent (permissions / sites).
if [ -f "$LE_DOMAIN_FILE" ]; then
	le_run_script "$APPLY_SCRIPT"
fi
