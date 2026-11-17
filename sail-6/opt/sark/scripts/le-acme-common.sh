#!/bin/bash
# Shared Let's Encrypt helpers for SAIL (HTTP-01 webroot via Apache).
# Sourced by le-first-cert.sh, le-renew.sh, apply-active-cert.sh, le-remove-cert.sh.
# SAIL_LE_STAGING=1  -> certbot --test-cert (staging CA)

LE_WEBROOT=/opt/sark/var/acme-challenge
LE_DOMAIN_FILE=/opt/sark/etc/identity/le-domain
LE_EMAIL_FILE=/opt/sark/etc/identity/le-email
TLS_ACTIVE_JSON=/opt/sark/etc/identity/tls-active.json
CUSTOM_FULLCHAIN=/opt/sark/etc/ssl/custom/fullchain.pem
CUSTOM_PRIVKEY=/opt/sark/etc/ssl/custom/privkey.pem
# Stable paths referenced by Apache sark-certs.conf and (after apply) Asterisk
ACTIVE_CERT=/etc/ssl/certs/ssl-cert-sark-customer.pem
ACTIVE_KEY=/etc/ssl/private/ssl-cert-sark-customer.key
SNAKEOIL_CERT=/etc/ssl/certs/ssl-cert-snakeoil.pem
SNAKEOIL_KEY=/etc/ssl/private/ssl-cert-snakeoil.key
HTTP_CONF=/etc/asterisk/sark_http.conf
TRANSPORT_CONF=/etc/asterisk/sark_pjsip_transport.conf
TRANSPORT_TMPL=/etc/asterisk/sark_pjsip_transport.tmpl
SARK_CERTS_CONF=/opt/sark/etc/apache2/sites-available/sark-certs.conf

le_run_script() {
	/bin/bash "$@"
}

ensure_le_webroot() {
	mkdir -p "$LE_WEBROOT/.well-known/acme-challenge"
	chown -R www-data:www-data "$LE_WEBROOT" 2>/dev/null || true
	chmod 755 "$LE_WEBROOT" "$LE_WEBROOT/.well-known" "$LE_WEBROOT/.well-known/acme-challenge" 2>/dev/null || true
}

ensure_identity_dir() {
	mkdir -p /opt/sark/etc/identity /opt/sark/etc/ssl/custom
	chmod 755 /opt/sark/etc/identity /opt/sark/etc/ssl /opt/sark/etc/ssl/custom 2>/dev/null || true
}

le_certbot_staging_args() {
	[ "${SAIL_LE_STAGING:-}" = "1" ] && printf '%s' '--test-cert'
}

le_certbot_auth_args() {
	ensure_le_webroot
	printf '%s' "--webroot -w $LE_WEBROOT"
}

le_require_apache_acme() {
	if ! command -v apache2ctl >/dev/null 2>&1 && ! command -v apachectl >/dev/null 2>&1; then
		echo "ERROR: apache2ctl not found." >&2
		return 1
	fi
	if ! grep -q 'acme-challenge' /opt/sark/etc/apache2/sites-available/sark-http.conf 2>/dev/null \
		&& ! grep -q 'acme-challenge' /etc/apache2/sites-available/sark-http.conf 2>/dev/null; then
		echo "ERROR: Apache ACME Alias missing from sark-http.conf (upgrade sail)." >&2
		return 1
	fi
	if command -v apache2ctl >/dev/null 2>&1; then
		apache2ctl configtest >/dev/null 2>&1 || {
			echo "ERROR: apache2ctl configtest failed; fix Apache before LE." >&2
			return 1
		}
	fi
	systemctl is-active --quiet apache2 2>/dev/null || systemctl start apache2 2>/dev/null || true
	return 0
}
