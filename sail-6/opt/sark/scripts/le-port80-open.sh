#!/bin/bash
# Temporarily open tcp/80 from net in Shorewall for Let's Encrypt HTTP-01.
# Managed via INCLUDE sark_inline_le (does not edit permanent sark_rules).
# Run as root. Called by le-first-cert.sh / le-renew.sh.

set -e
MARKER="# SAIL-LE-HTTP01 (managed)"
RULE_FILE=/etc/shorewall/sark_inline_le
RULE="ACCEPT net \$FW tcp 80 - - $MARKER"

if [ ! -d /etc/shorewall ]; then
	echo "le-port80-open: /etc/shorewall missing" >&2
	exit 1
fi

mkdir -p /etc/shorewall
if [ -f "$RULE_FILE" ] && grep -qF 'SAIL-LE-HTTP01 (managed)' "$RULE_FILE" 2>/dev/null; then
	# Already open; still ensure Shorewall is running
	:
else
	printf '%s\n' "$RULE" > "$RULE_FILE"
	chown www-data:www-data "$RULE_FILE" 2>/dev/null || true
fi

if command -v shorewall >/dev/null 2>&1; then
	shorewall check >/dev/null
	shorewall restart >/dev/null
fi
echo "le-port80-open: tcp/80 allowed from net (temporary)"
