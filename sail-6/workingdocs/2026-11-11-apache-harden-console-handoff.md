# Session handoff — 2026-11-11

Apache audio-alias lockdown, Console session gate, HTTP harden. Releases **sail 6.5.0-16** / **6.5.0-17**. Merged to `main` (`d307433`).

## Environment

| Role | Host | Notes |
|------|------|--------|
| Lab | `192.168.1.130` (`adler`) | Passwordless sudo. Installed **6.5.0-17**. Smoke: unauth `/console` → **403**; audio Aliases gone; `event.php` absent (404); `console.hmac.key` present. |
| Deb builder | `192.168.1.78` | `debuild --no-lintian -us -uc -b` → `~/Git/sail65/sail_6.5.0-17_all.deb`. Mac→builder sync via `tar`/`scp` (no rsync on Mac). `.78` now has Mac `id_rsa` for outbound SSH to `.130`. Push GitHub from Mac. |
| Mac | `/Users/jeffstokoe/GiT/sail65` | `main` tip **6.5.0-17** (`d307433`). |

## Releases

### 6.5.0-16

**Audio (session-backed play, drop public Aliases):**

- `/server-vmail` removed; **sarkphone** play → `downloadg.php?inline=1`.
- `/server-moh` removed; **sarkcluster** MOH → `downloadg.php?inline=1`; allowlist `/usr/share/asterisk/moh-*`.
- `/server-sounds` removed; **sarkgreeting** play → `downloadg.php?inline=1` (sounds already allowlisted).

**Console (keep shellinabox, require SAIL login):**

- Iframe → `/php/console_gate.php` (session) sets signed `SAIL_CONSOLE` cookie (1h), redirects to `/console/`.
- Apache `RewriteMap` prg `/opt/sark/scripts/srkConsoleCookieCheck` forbids `/console` without valid cookie.
- ProxyPass → `127.0.0.1:63005` + ProxyPassReverse. postinst creates `/opt/sark/cache/console.hmac.key` (root:www-data 640).
- Lab: `shellinabox` still on localhost:63005; OS login prompt remains after gate.

**Other Apache harden:**

- Deleted `www/event.php` (dead AMI reader + hardcoded secret).
- DocumentRoot `-Indexes` (ssl / http / prov-ssl).
- HTTP→HTTPS for non-provisioning paths; keep `/provisioning`, `/public`, `/snom`, `Config*.cfg` on :80.
- Dropped `.phps` AddType, empty `cgi-bin` ScriptAliases, `Satisfy any`.
- Removed unused Apache 2.2 leftovers: `cache/httpd.conf`, `service/httpd-srk`.

Deb: `sail_6.5.0-16_all.deb` on builder `~/Git/sail65/` (still present).

### 6.5.0-17

- Rebuild so `www/event.php`, `cache/httpd.conf`, and `service/httpd-srk` are **not** packaged (6.5.0-16 deb was built from a stale `.78` tree and still shipped them).
- `debian/postinst` purges those paths on upgrade.
- Deb: `sail_6.5.0-17_all.deb` on builder; installed on `.130`.

## CVE-2026-98432 (kernel, not SAIL)

Canonical published fixes: https://ubuntu.com/security/CVE-2026-98432 (updated 2026-11-11). Critical IPv4 TCP UAF → RCE.

| Host | Running | Fix (noble) |
|------|---------|-------------|
| `.130` (`adler`) | **6.8.0-149-generic** (patched 2026-11-11) | **`6.8.0-149.149`** via `linux-image-generic` |

**Action (other Ubuntu 24.04 PBXes):** `sudo apt update && sudo apt upgrade`, then **reboot**. Confirm with `uname -r` → `6.8.0-149-generic` (or later). nft mitigation on the CVE page is optional/temporary only.

## Key files

| Area | Path |
|------|------|
| SSL / HTTP / prov | `opt/sark/etc/apache2/sites-available/sark-{ssl,http,prov-ssl}.conf` |
| Console gate | `opt/sark/php/console_gate.php`, `scripts/srkConsoleCookieCheck`, `sarkshell/view.php` |
| Play links | `sarkphone/view.php`, `sarkgreeting/view.php`, `sarkcluster/view.php`, `downloadg.php` |
| Install | `debian/postinst` (HMAC key + chmod checker), `debian/changelog` |

## Open TODOs

1. `sark_includes` — finish or retire ([todo](./2026-10-26-todo-apache-sark-includes.md)).
2. Kernel CVE-2026-98432 on **customer** hosts: apt upgrade → reboot → verify `uname -r` ≥ **6.8.0-149** (lab `.130` done).
3. Copy deb to Mac/GitHub artifact if desired (`scp` from `.78`).

## Suggested next work

1. Patch/reboot remaining customer VMs for CVE-2026-98432.
2. Customer soak on Console panel (login → Console → shellinabox OS login).
3. Decide A/B/C on `sark_includes`.
