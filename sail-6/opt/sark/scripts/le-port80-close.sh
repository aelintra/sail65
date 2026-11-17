#!/bin/bash
# Close the temporary Shorewall tcp/80 rule opened for Let's Encrypt HTTP-01.
# Leaves permanent sark_rules alone (LAN HTTP / HTTPS / SSH unchanged).
# Run as root. Called by le-first-cert.sh / le-renew.sh (EXIT trap).

set -e
RULE_FILE=/etc/shorewall/sark_inline_le

if [ ! -d /etc/shorewall ]; then
	exit 0
fi

# Closed state: comment-only stub (INCLUDE still valid)
printf '#\n' > "$RULE_FILE"
chown www-data:www-data "$RULE_FILE" 2>/dev/null || true

if command -v shorewall >/dev/null 2>&1; then
	shorewall check >/dev/null 2>&1 || true
	shorewall restart >/dev/null 2>&1 || true
fi
echo "le-port80-close: temporary tcp/80 rule cleared"
