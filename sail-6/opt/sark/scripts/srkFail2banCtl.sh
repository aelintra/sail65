#!/bin/bash
#
# Fail2ban helpers for SAIL panels (ua-helper / root).
# Usage:
#   srkFail2banCtl.sh status
#   srkFail2banCtl.sh unban <ip> [jail]
#   srkFail2banCtl.sh whitelist-list
#   srkFail2banCtl.sh whitelist-add <ip|cidr>
#   srkFail2banCtl.sh whitelist-del <ip|cidr>
#   srkFail2banCtl.sh apply-ignoreip [lan_cidr]
#   srkFail2banCtl.sh mail-pref [on|off|status]
#
set -e

JAILS=/etc/fail2ban/jail.d/sail-jails.conf
# Cache paths so the PHP UI can read them (ua-helper only returns first reply line)
EXTRA=/opt/sark/cache/f2b-ignoreip.extra
STATUSOUT=/opt/sark/cache/f2b-status.txt
MAILPREF=/opt/sark/cache/f2b-mail.pref
MAILSH=/opt/sark/scripts/srkFail2banMail.sh

cmd=${1:-}
shift || true

valid_ip_or_cidr() {
	# IPv4 host or CIDR; reject shell metacharacters
	case "$1" in
		*[!0-9./]*) return 1 ;;
	esac
	echo "$1" | grep -Eq '^([0-9]{1,3}\.){3}[0-9]{1,3}(/([0-9]|[12][0-9]|3[0-2]))?$'
}

get_lan_cidr() {
	if [ -n "${1:-}" ]; then
		echo "$1"
		return
	fi
	if [ -f /etc/shorewall/local.lan ]; then
		awk -F= '/^LAN=/ {print $2; exit}' /etc/shorewall/local.lan
		return
	fi
	python3 - <<'PY' 2>/dev/null || true
import ipaddress,subprocess
out=subprocess.check_output("ip -4 -o addr show scope global",shell=True,text=True)
parts=out.split()
for i,t in enumerate(parts):
  if t=="inet":
    iface=ipaddress.ip_interface(parts[i+1])
    print("%s/%s" % (iface.network.network_address, iface.network.prefixlen))
    break
PY
}

read_extras() {
	[ -f "$EXTRA" ] || return 0
	grep -E '^[0-9]' "$EXTRA" 2>/dev/null || true
}

apply_ignoreip() {
	local lan
	lan=$(get_lan_cidr "${1:-}")
	[ -f "$JAILS" ] || { echo "missing $JAILS" >&2; return 1; }
	local line="ignoreip = 127.0.0.1/8 ::1"
	[ -n "$lan" ] && line="$line $lan"
	line="$line 224.0.1.0/24"
	local e
	while read -r e; do
		[ -z "$e" ] && continue
		case " $line " in
			*" $e "*) ;;
			*) line="$line $e" ;;
		esac
	done < <(read_extras)
	sed -i --follow-symlinks "/^ignoreip/c $line" "$JAILS"
	if command -v fail2ban-client >/dev/null 2>&1; then
		fail2ban-client -t >/dev/null 2>&1 && fail2ban-client reload >/dev/null 2>&1 || true
	fi
	echo "$line"
}

case "$cmd" in
	status)
		mkdir -p "$(dirname "$STATUSOUT")"
		{
			if ! command -v fail2ban-client >/dev/null 2>&1; then
				echo "fail2ban-client not installed"
			else
				fail2ban-client status 2>/dev/null || true
				echo "----"
				for j in $(fail2ban-client status 2>/dev/null | sed -n 's/.*Jail list:\t*//p' | tr ',' ' '); do
					j=$(echo "$j" | tr -d ' ')
					[ -z "$j" ] && continue
					echo "### jail:$j"
					fail2ban-client status "$j" 2>/dev/null || true
				done
				echo "----"
				echo "ignoreip: $(fail2ban-client get sshd ignoreip 2>/dev/null | tr '\n' ' ' || true)"
			fi
			echo "mailconf: $([ -f /etc/fail2ban/jail.d/sail-mail.conf ] && echo on || echo off)"
			echo "mailpref: $(cat "$MAILPREF" 2>/dev/null || echo auto)"
		} > "$STATUSOUT"
		chmod 644 "$STATUSOUT" 2>/dev/null || true
		echo "wrote $STATUSOUT"
		;;

	unban)
		ip=${1:-}
		jail=${2:-}
		valid_ip_or_cidr "$ip" || { echo "invalid ip" >&2; exit 2; }
		if [ -n "$jail" ]; then
			fail2ban-client set "$jail" unbanip "$ip" 2>/dev/null || true
		else
			for j in $(fail2ban-client status 2>/dev/null | sed -n 's/.*Jail list:\t*//p' | tr ',' ' '); do
				j=$(echo "$j" | tr -d ' ')
				[ -z "$j" ] && continue
				fail2ban-client set "$j" unbanip "$ip" 2>/dev/null || true
			done
		fi
		echo "unban $ip done"
		;;

	whitelist-list)
		read_extras
		;;

	whitelist-add)
		ip=${1:-}
		valid_ip_or_cidr "$ip" || { echo "invalid ip/cidr" >&2; exit 2; }
		mkdir -p "$(dirname "$EXTRA")"
		touch "$EXTRA"
		chmod 644 "$EXTRA" 2>/dev/null || true
		if grep -qxF "$ip" "$EXTRA" 2>/dev/null; then
			echo "already listed: $ip"
		else
			echo "$ip" >> "$EXTRA"
			echo "added: $ip"
		fi
		apply_ignoreip
		;;

	whitelist-del)
		ip=${1:-}
		valid_ip_or_cidr "$ip" || { echo "invalid ip/cidr" >&2; exit 2; }
		[ -f "$EXTRA" ] || { echo "nothing to delete"; exit 0; }
		grep -vxF "$ip" "$EXTRA" > "$EXTRA.tmp" || true
		mv "$EXTRA.tmp" "$EXTRA"
		chmod 644 "$EXTRA" 2>/dev/null || true
		echo "removed: $ip"
		apply_ignoreip
		;;

	apply-ignoreip)
		apply_ignoreip "${1:-}"
		;;

	mail-pref)
		sub=${1:-status}
		mkdir -p "$(dirname "$MAILPREF")"
		case "$sub" in
			on|YES|yes)
				echo on > "$MAILPREF"
				[ -x "$MAILSH" ] && "$MAILSH" || true
				echo "mail pref=on"
				;;
			off|NO|no)
				echo off > "$MAILPREF"
				[ -x "$MAILSH" ] && "$MAILSH" || true
				echo "mail pref=off"
				;;
			status)
				echo "$(cat "$MAILPREF" 2>/dev/null || echo auto)"
				;;
			*)
				echo "usage: mail-pref on|off|status" >&2
				exit 2
				;;
		esac
		;;

	*)
		echo "usage: $0 status|unban|whitelist-list|whitelist-add|whitelist-del|apply-ignoreip|mail-pref" >&2
		exit 2
		;;
esac
