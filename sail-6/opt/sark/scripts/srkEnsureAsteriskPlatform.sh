#!/bin/bash
#
# Reinstall SAIL Asterisk platform files that must not be permanently
# replaced by an older customer backup (PJSIP templates, include chain,
# modules/http policy, and the pjsip/modules/http symlinks).
#
# Safe to run after backup restore or any time /etc/asterisk was wiped.
# Runtime generated configs (sark_pjsip_*_phone.conf, ready_*.conf, etc.)
# are left alone.
#
set -e

PLATFORM=/opt/sark/cache/asterisk-platform
AST=/etc/asterisk

if [ ! -d "$PLATFORM" ]; then
	echo "srkEnsureAsteriskPlatform: missing $PLATFORM" >&2
	exit 1
fi

mkdir -p "$AST"

# Package-owned platform files (always take current SAIL versions)
for f in \
	sark_pjsip.conf \
	sark_modules.conf \
	sark_http.conf \
	sark_rtp.conf \
	sark_pjsip_globals.conf \
	sark_pjsip_anonymous.conf \
	sark_pjsip_notify.conf \
	sark_pjsip_phone.tmpl \
	sark_pjsip_transport.tmpl \
	sark_pjsip_webrtc.tmpl \
	sark_pjsip_trunk_rcvreg.tmpl \
	sark_pjsip_trunk_sndreg.tmpl \
	sark_pjsip_trunk_trusted.tmpl
do
	if [ -f "$PLATFORM/$f" ]; then
		cp -a "$PLATFORM/$f" "$AST/$f"
		chown asterisk:asterisk "$AST/$f" 2>/dev/null || true
		chmod 664 "$AST/$f" 2>/dev/null || true
	fi
done

# Ensure SAIL include/module/http wiring (stock samples from old backups
# often overwrite these as plain files).
ensure_link() {
	local link="$1"
	local target="$2"
	if [ ! -f "$AST/$target" ]; then
		echo "srkEnsureAsteriskPlatform: missing target $AST/$target" >&2
		return 1
	fi
	if [ -e "$AST/$link" ] && [ ! -L "$AST/$link" ]; then
		mv -f "$AST/$link" "$AST/${link}.install.bak"
	fi
	ln -sfn "$AST/$target" "$AST/$link"
}

ensure_link pjsip.conf sark_pjsip.conf
ensure_link modules.conf sark_modules.conf
ensure_link http.conf sark_http.conf

echo "srkEnsureAsteriskPlatform: SAIL Asterisk platform files restored"
exit 0
