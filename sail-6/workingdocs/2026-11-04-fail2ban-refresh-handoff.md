# Session handoff — 2026-11-04

Fail2Ban refresh for Ubuntu 24.04 + Net panel UI. Branch / release **sail 6.5.0-15**.

## Environment

| Role | Host | Notes |
|------|------|--------|
| Lab | `192.168.1.130` (`adler`) | Passwordless sudo. Hot-patched during development; install **6.5.0-15** for clean package state. Ban email left **off** (`f2b-mail.pref=off`) — SUPEMAIL points at a real customer mailbox. |
| Deb builder | `192.168.1.78` | `debuild --no-lintian -us -uc -b` → `~/Git/sail65/sail_*_all.deb`. Push GitHub from Mac. |
| Mac | `/Users/jeffstokoe/GiT/sail65` | Branch `feature/fail2ban-refresh-65015` — release `e197fc3`, build `5b38ca3`. Deb: `sail_6.5.0-15_all.deb`. Merge to main as needed. |

Also assessed **CVE-2026-98432** (kernel IPv4 TCP UAF): OS/kernel issue, not SAIL. Ubuntu 24.04 fix still WIP (`6.8.0-149.x`). Lab was on `6.8.0-147-generic`. Disabling MPTCP alone is not a fix; `mailhub=mail` is a valid ssmtp hub name (not a placeholder).

## Releases

### 6.5.0-15

**Jail packaging (PBX3-style `jail.d`, Shorewall banaction):**

- Stop symlinking full `jail.local` (fought Debian `backend=systemd`; ignoreip sed never ran).
- Ship `jail.d/sail-jails.conf` — sshd, asterisk (file `/var/log/asterisk/messages`), recidive; `backend=auto`; `banaction=shorewall`.
- Ship `jail.d/sail-apache.conf` — `sail-apache-badbots` (distro `apache-badbots` filter).
- `setip` / `srkFail2banCtl.sh apply-ignoreip` merge LAN + extras into `ignoreip`.
- postinst: symlink jail.d fragments, remove legacy SAIL `jail.local`, `fail2ban-client -t` + reload.
- Keep optional `action.d/shorewall.local` (`blocktype=drop`).

**Ban email:**

- `srkFail2banMail.sh` writes `/etc/fail2ban/jail.d/sail-mail.conf` (`action_mw`) when SMTP mailhub is usable (including **`mail`**), Globals `SUPEMAIL`/`EMAILALERT` set, and pref not `off`.
- Pref: `/opt/sark/cache/f2b-mail.pref` (`on`/`off`).
- Network SMTP save still re-runs the mail helper (does not own the toggle).

**UI — Net → Fail2Ban** (`sarkfail2ban`, Panel `332`):

- Ban-email on/off, jail status, unban, extra whitelist.
- Ops: `srkFail2banCtl.sh` (status → `/opt/sark/cache/f2b-status.txt`; extras → `/opt/sark/cache/f2b-ignoreip.extra`).
- DB seed: `cache/1762292000.db_v4_f2b_panel` (+ help keys).

**Lab smoke (pre-deb):** config test OK; 4 jails; asterisk watches messages file; ignoreip includes LAN; ban/unban via shorewall/nft; whitelist add/del; mail pref off/on.

## Key files

| Area | Path |
|------|------|
| Jails | `opt/sark/etc/fail2ban/jail.d/sail-jails.conf`, `sail-apache.conf`, `README` |
| Helpers | `opt/sark/scripts/srkFail2banMail.sh`, `srkFail2banCtl.sh` |
| Panel | `opt/sark/php/sarkfail2ban/` |
| Install | `debian/postinst`, `debian/prerm`, `generator/setip.php` |
| DB | `db/db_v4_system.sql` (Panel 332 → Net), `cache/1762292000.db_v4_f2b_panel` |

## Open TODOs (unchanged)

1. Audio aliases — `/server-vmail`, sounds/MOH ([todo](./2026-10-26-todo-harden-audio-aliases.md)).
2. `sark_includes` — finish or retire ([todo](./2026-10-26-todo-apache-sark-includes.md)).

## Suggested next work

1. Install **6.5.0-15** on `.130` / customer VM; confirm Net → Fail2Ban after login.
2. Watch Ubuntu noble for CVE-2026-98432 kernel (`≥ 6.8.0-149`) + reboot.
3. Optional: panel polish after first customer feedback.
