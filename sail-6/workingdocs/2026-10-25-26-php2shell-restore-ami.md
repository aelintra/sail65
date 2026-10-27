# Session handoff — 2026-10-25 / 2026-10-26

Customer go-live prep on Ubuntu 24.04. Packages released: **sail 6.5.0-9** then **6.5.0-10**.

## Environment

| Role | Host | Notes |
|------|------|--------|
| Lab / customer rebuild | `192.168.1.130` | Rebuilt late 26 Oct; reported OK with 6.5.0-9+ fixes. On **sail 6.5.0-10** as of late 26 Oct. |
| Other test | `192.168.1.12` (`iitac2404`) | Cleaner reference install; had all `.tmpl` files |
| **Deb builder** | `192.168.1.78` (`deb11-1`) | Canonical package build host. Repos in `~/Git/sail65`; `debuild --no-lintian -us -uc -b` from `sail-6/`; output `~/Git/sail65/sail_*_all.deb` |
| Mac workspace | `/Users/jeffstokoe/GiT/sail65` | Tracks `origin/main` (`aelintra/sail65`) |
| SSH | `tech@…` | Builder cannot push to GitHub over HTTPS (no creds); push from Mac |

### Sudo on `.130` (lab)

`tech` has **passwordless sudo** on `192.168.1.130` via `/etc/sudoers.d/` (lab agent access). Verified:

```bash
ssh tech@192.168.1.130 'sudo -n true && echo OK'
```

Use `sudo -n …` from the agent (no password prompt). Do **not** assume the same on `.12`, `.78`, or production.

Customer backup used for testing: `sarkbak.1792900801.zip`. Uncompressed ~486 MB; **~97% voicemail** under `var/spool`.

## Releases this session

### 6.5.0-9 (commit `f15e971`, build commit later)

PHP2Shell, AMI/extensions lock, safer Asterisk restore.

### 6.5.0-10 (commit `637fa25` / build `ebbf09b`)

Removed unused jQuery UI `package.json` that triggered Dependabot grunt CVEs. Open Dependabot alerts → **0**.

Debs on builder (and pushed to GitHub as `_all.deb` artifacts):

- `~/Git/sail65/sail_6.5.0-9_all.deb`
- `~/Git/sail65/sail_6.5.0-10_all.deb`

---

## 1. PHP2Shell (CVE-2026-109618 / CVE-2026-109619)

**Not an app bug** — RCE in the PHP engine. SAIL uses **Apache + `libapache2-mod-php`** (highest-risk SAPI).

Ubuntu 24.04 fixed package revision (backport; do **not** require upstream `8.3.36`):

- `libapache2-mod-php8.3` ≥ **`8.3.6-0ubuntu0.24.04.13`** (USN-8948-1)
- Must **`systemctl restart apache2`** after upgrade

### Package changes

- [`debian/postinst`](../debian/postinst) — on Ubuntu 24.04, version-check gate + warn with apt steps; symlink PHP drop-in into `/etc/php/*/apache2|cli/conf.d/`
- [`opt/sark/etc/php/99-sark-php2shell.ini`](../opt/sark/etc/php/99-sark-php2shell.ini) — `session.upload_progress.enabled=Off` (defense-in-depth only)
- [`opt/sark/docs/PHP2SHELL-UBUNTU-24.04.md`](../opt/sark/docs/PHP2SHELL-UBUNTU-24.04.md) — go-live verify + restore notes
- Shorewall defaults **left unchanged** (customer sets FW after install)

---

## 2. Missing `/etc/asterisk/*.tmpl` after backup restore

Both hosts showed `dpkg -l sail` **6.5.0-7** but `.130` was missing five PJSIP templates. `dpkg -V sail` reported them **missing conffiles**.

**Root cause:** Asterisk restore path in backup UI:

```text
rm -rf /etc/asterisk/*
cp -a $backup/etc/asterisk/* /etc/asterisk
```

Customer zip had **zero** `.tmpl` files and stock Asterisk samples → wiped package files.

Skipping “restore Asterisk files” would have preserved templates. Files reconstituted manually, then permanently fixed in package (below).

---

## 3. PJSIP endpoints not loading (same wipe)

After restore, `.130` had:

- Stock **78 KB** `pjsip.conf` (sample) instead of SAIL `#include` chain
- **Missing** `sark_pjsip.conf`, `sark_modules.conf`
- Generated `sark_pjsip_*_phone.conf` / `ready_phones.conf` present but **never included**

AMI: `PJSIPShowEndpoints` → “No endpoints found”. Fixed by restoring SAIL symlinks:

- `pjsip.conf` → `sark_pjsip.conf`
- `modules.conf` → `sark_modules.conf`
- `http.conf` → `sark_http.conf`

Then `systemctl restart asterisk` → **425 endpoints**.

---

## 4. Extensions panel “locked” SQLite

`lsof` showed Apache holding `sark.db`. Real issue: Extensions `showMain()` calls `get_pjsip_array()` which does **one AMI `PJSIPShowEndpoint` per extension** (~424). On missing endpoints, `_sendCommand` waited for `ListItems` until **3s stream timeout** → ~20+ minutes, FD held open. Other panels OK.

### Code fixes (in 6.5.0-9)

- [`AsteriskManager.php`](../opt/sark/php/AsteriskManager.php) — stop on `Response: Error` (drain to blank line)
- [`srkAmiHelperClass`](../opt/sark/php/srkAmiHelperClass) — skip error peers; remove per-row `logit`
- [`sarkextension/view.php`](../opt/sark/php/sarkextension/view.php) — `DB::closeInstance()` before AMI pass
- [`srkDbClass`](../opt/sark/php/srkDbClass) — `PRAGMA busy_timeout = 5000`; `closeInstance()`
- [`srkHelperClass`](../opt/sark/php/srkHelperClass) — cache `LOGLEVEL` in `logIt`

---

## 5. Backup restore must not break SAIL again

- Pristine copies: [`opt/sark/cache/asterisk-platform/`](../opt/sark/cache/asterisk-platform/) (not in customer backup zips)
- Script: [`opt/sark/scripts/srkEnsureAsteriskPlatform.sh`](../opt/sark/scripts/srkEnsureAsteriskPlatform.sh)
- Called from [`sarkbackup/view.php`](../opt/sark/php/sarkbackup/view.php) after Asterisk restore

Restores templates, `sark_pjsip*.conf` / modules / http / rtp fragments, and recreates symlinks (parks stock samples as `*.install.bak`).

---

## 6. Dependabot (cleared in 6.5.0-10)

Alerts were all **grunt** via leftover  
`opt/sark/www/sark-common/js/jquery-ui-1.12.1.custom/package.json` (dev metadata; runtime is static JS). File **deleted**. Open alerts = 0 after GitHub rescan.

---

## Build notes (192.168.1.78)

```bash
cd ~/Git/sail65 && git pull --ff-only origin main
cd sail-6
# If debuild fails on dpkg-genchanges looking for old *.buildinfo:
rm -f debian/files debian/sail.debhelper.log debian/sail.substvars
rm -f ../sail_VERSION_arm64.changes ../sail_VERSION_arm64.buildinfo  # as needed
debuild --no-lintian -us -uc -b
# Output: ~/Git/sail65/sail_X.Y.Z-N_all.deb
```

Push to GitHub from **Mac** (or machine with `gh`/HTTPS creds), not from builder.

---

## Suggested next work

- Optionally harden AMI further (bulk `PJSIPShowEndpoints` instead of N calls) for large installs
- Confirm production go-live checklist in `opt/sark/docs/PHP2SHELL-UBUNTU-24.04.md` on customer host
- Keep Shorewall customer-owned; review after restore if backup included shorewall rules
- Do not commit secrets into `workingdocs/`
