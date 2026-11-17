#!/bin/bash
# First-time Let's Encrypt certificate (HTTP-01 webroot via Apache).
# Usage: le-first-cert.sh <fqdn> <email>
# Run as root. Requires certbot, Apache ACME Alias; opens Shorewall tcp/80 for the challenge then closes it.
# SAIL_LE_STAGING=1 — staging CA (see le-acme-common.sh).
# Without staging: if a staging live cert exists for fqdn, fall back to snakeoil, delete
# that lineage, then issue production (avoids bricking Apache SSLCertificateFile).

set -e
if [ $# -lt 2 ]; then
	echo "Usage: $0 <fqdn> <email>" >&2
	exit 1
fi
FQDN="$1"
EMAIL="$2"
SCRIPT_DIR="$(dirname "$(readlink -f "$0" 2>/dev/null || echo "$0")")"
# shellcheck source=le-acme-common.sh
. "$SCRIPT_DIR/le-acme-common.sh"
APPLY_SCRIPT="$SCRIPT_DIR/apply-active-cert.sh"
OPEN_SCRIPT="$SCRIPT_DIR/le-port80-open.sh"
CLOSE_SCRIPT="$SCRIPT_DIR/le-port80-close.sh"

close_on_exit() {
	le_run_script "$CLOSE_SCRIPT" 2>/dev/null || true
}
trap close_on_exit EXIT

if ! command -v certbot >/dev/null 2>&1; then
	echo "ERROR: certbot not installed (apt install certbot)." >&2
	exit 1
fi

# Custom commercial cert wins over LE; refuse to confuse the active identity.
if [ -f "$CUSTOM_FULLCHAIN" ] && [ -f "$CUSTOM_PRIVKEY" ]; then
	echo "ERROR: custom certificate present at $CUSTOM_FULLCHAIN — remove custom first." >&2
	exit 1
fi

le_require_apache_acme
ensure_identity_dir
ensure_le_webroot

# Staging → production: --keep-until-expiring would keep the untrusted staging
# cert. Fall back to snakeoil first so certbot delete cannot brick Apache SSL paths.
live_pem=""
if live_pem=$(le_live_fullchain "$FQDN") && [ "${SAIL_LE_STAGING:-}" != "1" ]; then
	if le_cert_is_staging "$live_pem"; then
		echo "le-first-cert: replacing staging cert for $FQDN with production" >&2
		le_fallback_to_snakeoil
		certbot delete --cert-name "$FQDN" --non-interactive >/dev/null 2>&1 || true
	fi
fi

le_run_script "$OPEN_SCRIPT"

# shellcheck disable=SC2046
certbot certonly $(le_certbot_auth_args) $(le_certbot_staging_args) \
	-d "$FQDN" -m "$EMAIL" --agree-tos --non-interactive --keep-until-expiring

echo "$FQDN" > "$LE_DOMAIN_FILE"
echo "$EMAIL" > "$LE_EMAIL_FILE"
chmod 644 "$LE_DOMAIN_FILE" "$LE_EMAIL_FILE" 2>/dev/null || true

le_run_script "$APPLY_SCRIPT"
echo "le-first-cert: issued for $FQDN"
