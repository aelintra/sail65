#!/bin/bash
# Apply active TLS certificate to Apache + Asterisk (HTTP/WSS + PJSIP TLS).
# Selection order: custom -> Let's Encrypt -> snakeoil.
# Installs material at ssl-cert-sark-customer.{pem,key} (symlink) when custom/LE,
# rewrites Asterisk http + transport cert paths, reloads Apache, restarts Asterisk.
# See workingdocs/2026-11-17-letsencrypt-apache-handoff.md

set -e
SCRIPT_DIR="$(dirname "$(readlink -f "$0" 2>/dev/null || echo "$0")")"
# shellcheck source=le-acme-common.sh
. "$SCRIPT_DIR/le-acme-common.sh"

LE_LIVE_BASE=/etc/letsencrypt/live
LE_ARCHIVE_BASE=/etc/letsencrypt/archive

ensure_ssl_cert_principal() {
	if getent group ssl-cert >/dev/null 2>&1; then
		usermod -a -G ssl-cert asterisk 2>/dev/null || true
		usermod -a -G ssl-cert www-data 2>/dev/null || true
	fi
	# Asterisk HTTP TLS must open the key; private dir is often 710/750 root:ssl-cert
	chmod 751 /etc/ssl/private 2>/dev/null || true
}

ensure_le_tree_readable() {
	local domain="$1"
	[ -n "$domain" ] || return 0
	[ -d "$LE_LIVE_BASE" ] || return 0
	getent group ssl-cert >/dev/null 2>&1 || return 0

	chgrp -R ssl-cert "$LE_LIVE_BASE" "$LE_ARCHIVE_BASE" 2>/dev/null || true
	chmod 750 "$LE_LIVE_BASE" "$LE_ARCHIVE_BASE" 2>/dev/null || true
	[ -d "$LE_LIVE_BASE/$domain" ] && chmod 750 "$LE_LIVE_BASE/$domain" 2>/dev/null || true
	[ -d "$LE_ARCHIVE_BASE/$domain" ] && chmod 750 "$LE_ARCHIVE_BASE/$domain" 2>/dev/null || true
	find "$LE_LIVE_BASE" "$LE_ARCHIVE_BASE" -mindepth 1 -maxdepth 1 -type d -exec chmod 750 {} \; 2>/dev/null || true
	find "$LE_ARCHIVE_BASE" -type d -exec chmod 750 {} \; 2>/dev/null || true
	find "$LE_ARCHIVE_BASE" -name 'privkey*.pem' -exec chgrp ssl-cert {} \; 2>/dev/null || true
	find "$LE_ARCHIVE_BASE" -name 'privkey*.pem' -exec chmod 640 {} \; 2>/dev/null || true
	find "$LE_ARCHIVE_BASE" -name '*.pem' ! -name 'privkey*.pem' -exec chmod 644 {} \; 2>/dev/null || true
}

ensure_path_group_readable() {
	local path="$1"
	[ -f "$path" ] || [ -L "$path" ] || return 0
	getent group ssl-cert >/dev/null 2>&1 || return 0
	# Resolve for chgrp when symlink
	local real="$path"
	if [ -L "$path" ]; then
		real=$(readlink -f "$path" 2>/dev/null || echo "$path")
	fi
	[ -f "$real" ] || return 0
	chgrp ssl-cert "$real" 2>/dev/null || true
	case "$real" in
		*privkey*|*key*|*KEY*) chmod 640 "$real" 2>/dev/null || true ;;
		*) chmod a+r "$real" 2>/dev/null || true ;;
	esac
}

install_active_symlinks() {
	local cert="$1"
	local key="$2"
	# Replace previous customer material with symlinks to the active source
	rm -f "$ACTIVE_CERT" "$ACTIVE_KEY"
	ln -sfn "$cert" "$ACTIVE_CERT"
	ln -sfn "$key" "$ACTIVE_KEY"
	ensure_path_group_readable "$cert"
	ensure_path_group_readable "$key"
}

clear_active_customer() {
	rm -f "$ACTIVE_CERT" "$ACTIVE_KEY"
}

set_asterisk_cert_paths() {
	local cert="$1"
	local key="$2"
	local f
	for f in "$HTTP_CONF" "$TRANSPORT_CONF" "$TRANSPORT_TMPL"; do
		[ -f "$f" ] || continue
		if grep -qE '^(tlscertfile|cert_file)=' "$f" 2>/dev/null; then
			sed -i "s|^tlscertfile=.*|tlscertfile=$cert|" "$f" 2>/dev/null || true
			sed -i "s|^tlsprivatekey=.*|tlsprivatekey=$key|" "$f" 2>/dev/null || true
			sed -i "s|^cert_file=.*|cert_file=$cert|" "$f" 2>/dev/null || true
			sed -i "s|^priv_key_file=.*|priv_key_file=$key|" "$f" 2>/dev/null || true
		fi
	done
}

write_sark_certs_conf() {
	local cert="$1"
	local key="$2"
	mkdir -p "$(dirname "$SARK_CERTS_CONF")"
	printf 'SSLCertificateFile    %s\nSSLCertificateKeyFile %s\n' "$cert" "$key" > "$SARK_CERTS_CONF"
}

write_tls_active_json() {
	ensure_identity_dir
	local expires issuer expires_json issuer_json domain_json sans_json comma s esc _sans_tmp
	expires=""
	issuer=""
	expires_json="null"
	issuer_json="null"
	domain_json="null"
	sans_json="["
	comma=""
	if [ -f "$CERT" ] && command -v openssl >/dev/null 2>&1; then
		expires=$(openssl x509 -enddate -noout -in "$CERT" 2>/dev/null | sed -n 's/^notAfter=//p' | head -1)
		if [ -n "$expires" ]; then
			expires=$(date -u -d "$expires" '+%Y-%m-%d' 2>/dev/null || true)
		fi
		issuer=$(openssl x509 -noout -issuer -in "$CERT" 2>/dev/null | sed -n 's/^issuer=//p' | head -1)
		_sans_tmp=$(mktemp) || exit 1
		openssl x509 -noout -text -in "$CERT" 2>/dev/null | tr ',' '\n' | sed -n 's/^[[:space:]]*DNS://p' >"$_sans_tmp"
		while IFS= read -r s; do
			[ -z "$s" ] && continue
			esc=$(printf '%s' "$s" | sed 's/\\/\\\\/g; s/"/\\"/g')
			sans_json+="${comma}\"${esc}\""
			comma=","
		done <"$_sans_tmp"
		rm -f "$_sans_tmp"
	fi
	sans_json+="]"
	if [ "$sans_json" = "[]" ] && [ -n "${TLS_DOMAIN:-}" ]; then
		esc=$(printf '%s' "$TLS_DOMAIN" | sed 's/\\/\\\\/g; s/"/\\"/g')
		sans_json="[\"${esc}\"]"
	fi
	[ -n "$expires" ] && expires_json="\"$expires\""
	if [ -n "$issuer" ]; then
		esc=$(printf '%s' "$issuer" | sed 's/\\/\\\\/g; s/"/\\"/g')
		issuer_json="\"${esc}\""
	fi
	if [ -n "${TLS_DOMAIN:-}" ]; then
		esc=$(printf '%s' "$TLS_DOMAIN" | sed 's/\\/\\\\/g; s/"/\\"/g')
		domain_json="\"${esc}\""
	fi
	esc=$(printf '%s' "$CERT" | sed 's/\\/\\\\/g; s/"/\\"/g')
	printf '{"source":"%s","domain":%s,"fullchain":"%s","expires_at":%s,"issuer":%s,"cert_sans":%s}\n' \
		"${TLS_SOURCE:-snakeoil}" \
		"$domain_json" \
		"$esc" \
		"$expires_json" \
		"$issuer_json" \
		"$sans_json" > "$TLS_ACTIVE_JSON"
	chmod 644 "$TLS_ACTIVE_JSON" 2>/dev/null || true
}

reload_apache() {
	if command -v systemctl >/dev/null 2>&1; then
		systemctl reload apache2 2>/dev/null && return 0
	fi
	apache2ctl graceful 2>/dev/null || true
}

reload_asterisk_tls() {
	# core reload does not re-open TLS after a failed bind; restart so :8089/WSS sticks.
	if command -v systemctl >/dev/null 2>&1 && systemctl is-active --quiet asterisk 2>/dev/null; then
		systemctl restart asterisk 2>/dev/null && return 0
	fi
	if command -v asterisk >/dev/null 2>&1; then
		asterisk -rx 'core restart now' 2>/dev/null && return 0
		asterisk -rx 'core reload' 2>/dev/null || true
	fi
}

enable_customer_apache_site() {
	a2dissite sark-default-ssl.conf >/dev/null 2>&1 || a2dissite sark-default-ssl >/dev/null 2>&1 || true
	# Site file may be a symlink under sites-available; ensure enabled
	[ -L /etc/apache2/sites-available/sark-certs.conf ] || \
		[ -e /etc/apache2/sites-available/sark-certs.conf ] || \
		ln -sfn "$SARK_CERTS_CONF" /etc/apache2/sites-available/sark-certs.conf
	a2ensite sark-certs.conf >/dev/null 2>&1 || a2ensite sark-certs >/dev/null 2>&1 || true
}

enable_snakeoil_apache_site() {
	a2dissite sark-certs.conf >/dev/null 2>&1 || a2dissite sark-certs >/dev/null 2>&1 || true
	a2ensite sark-default-ssl.conf >/dev/null 2>&1 || a2ensite sark-default-ssl >/dev/null 2>&1 || true
}

ensure_ssl_cert_principal
ensure_identity_dir

TLS_SOURCE="snakeoil"
TLS_DOMAIN=""
CERT="$SNAKEOIL_CERT"
KEY="$SNAKEOIL_KEY"

if [ -f "$CUSTOM_FULLCHAIN" ] && [ -f "$CUSTOM_PRIVKEY" ]; then
	CERT="$CUSTOM_FULLCHAIN"
	KEY="$CUSTOM_PRIVKEY"
	TLS_SOURCE="custom"
	install_active_symlinks "$CERT" "$KEY"
	# Apache include points at stable customer paths (symlinks)
	write_sark_certs_conf "$ACTIVE_CERT" "$ACTIVE_KEY"
	set_asterisk_cert_paths "$ACTIVE_CERT" "$ACTIVE_KEY"
	enable_customer_apache_site
elif [ -f "$LE_DOMAIN_FILE" ]; then
	domain=$(tr -d '\n' <"$LE_DOMAIN_FILE")
	if [ -n "$domain" ] && [ -f "$LE_LIVE_BASE/$domain/fullchain.pem" ] && [ -f "$LE_LIVE_BASE/$domain/privkey.pem" ]; then
		ensure_le_tree_readable "$domain"
		CERT="$LE_LIVE_BASE/$domain/fullchain.pem"
		KEY="$LE_LIVE_BASE/$domain/privkey.pem"
		TLS_SOURCE="letsencrypt"
		TLS_DOMAIN="$domain"
		install_active_symlinks "$CERT" "$KEY"
		write_sark_certs_conf "$ACTIVE_CERT" "$ACTIVE_KEY"
		set_asterisk_cert_paths "$ACTIVE_CERT" "$ACTIVE_KEY"
		enable_customer_apache_site
	else
		clear_active_customer
		write_sark_certs_conf "$SNAKEOIL_CERT" "$SNAKEOIL_KEY"
		set_asterisk_cert_paths "$SNAKEOIL_CERT" "$SNAKEOIL_KEY"
		enable_snakeoil_apache_site
	fi
else
	clear_active_customer
	write_sark_certs_conf "$SNAKEOIL_CERT" "$SNAKEOIL_KEY"
	set_asterisk_cert_paths "$SNAKEOIL_CERT" "$SNAKEOIL_KEY"
	enable_snakeoil_apache_site
fi

if [ "$TLS_SOURCE" = "snakeoil" ]; then
	ensure_path_group_readable "$SNAKEOIL_KEY" 2>/dev/null || true
fi

write_tls_active_json
reload_apache
reload_asterisk_tls

echo "apply-active-cert: source=$TLS_SOURCE cert=$CERT"
