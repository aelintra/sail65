# Session handoff — 2026-11-19

Lab install of **6.5.0-23**, WebPhone/9005 unblock on webo1001, Java4Shell triage, Dependabot/npm harden for **sark65api_L12**, then evening **6.5.0-24** (`rtp.conf` → `sark_rtp.conf`) built, merged to **`main`**, deployed to `.135` + webo1001; media confirmed OK.

Prior: [2026-11-18-ldap-asterisk-tls-handoff.md](./2026-11-18-ldap-asterisk-tls-handoff.md).  
LDAP detail: [2026-11-17-ldap-contact-and-tenant-ou-fixes.md](./2026-11-17-ldap-contact-and-tenant-ou-fixes.md).

## Deploy / hosts

| Host | sail | Notes |
|------|------|--------|
| `192.168.1.135` (`ubu2204arm`) | **6.5.0-24** | Upgraded 19/11; `rtp.conf` → `sark_rtp.conf`, stock as `rtp.install.conf`; STUN live (was `0.0.0.0:0`). |
| `webo1001.terminator.cloud` | **6.5.0-24** | Upgraded 19/11 from **-22**; rtp/STUN live; LE YE2 kept; Shorewall **8089** + iptables OK; WSS `:8089/ws` up. User confirmed **media flowing** on WebRTC after upgrade. |
| `192.168.1.130` (`adler`) | unchanged this session | — |

**`main`** includes LE path through **6.5.0-24** (merge `573f3de`). Feature branch `feature/letsencrypt-apache-65018` pushed at `f0f4929` (same tip as pre-merge release commit).

## Lab `.135` — 6.5.0-23 install (earlier)

- Copied `sail_6.5.0-23_all.deb` → `dpkg -i` as `tech` (passwordless sudo).
- Pre/post compare: admin hash/salt + lastlogin, Cluster `ldapropwd`, globals (`PJSIP`, EXTLEN, AGENTSTART, LDAP*), table counts, `htpasswd`, console HMAC, Shorewall `rules`/`sark_rules`/`sark_inline_le`, `sark_customer_*.conf` — **all preserved**.
- ALWAYS seeds use `INSERT OR IGNORE` (admin not factory-reset). Once files all skipped.
- Expected churn only: `.sail-install-date` rewritten; **`sarkapi` Apache site re-enabled** (was absent from `sites-enabled` pre-upgrade; postinst re-links when API package tree present).
- Benign noise: `CREATE USER asterisk` already exists; `pjsip.conf` already a plain include file (symlink skip); helper `:7601` briefly down during generator; `sqlite_sequence` parse warning on create.
- LDAP fix files present (`srkLDAPHelperClass`, `sarkcluster` tenant OU create).

## webo1001 — WebRTC 9005

SPA/dev team overnight config change fixed the WebPhone WSS host bug (was opening `wss://dhbm8x.pbx3.com:8089/ws` instead of configured webo1001 proxy). User reports **9005 working OK**. Sail WSS path was already verified 18/11. Post-**-24** STUN: media still OK (user confirmed session end).

## CVE-2026-114938 (Java4Shell)

OpenJDK Base64/AVX-512 VBMI issue. **Does not affect SAIL** as deployed:

- No OpenJDK/`java` on `.135` (ARM64) or webo1001.
- webo1001 is x86_64 with AVX-512 but **no `avx512vbmi`** anyway.

Only revisit if a Java app/JRE is added later.

## Dependabot / sark65api_L12

| Repo | Result |
|------|--------|
| `aelintra/sail65` | **0 open** alerts (historical grunt alerts remain `fixed`). No manifests left. |
| `aelintra/sark65api_L12` | Alerts were disabled → enabled. Composer lock (128 pkgs) vs GitHub Advisory DB: **0 hits**. npm had no lockfile; caret floors for vite/axios/postcss admitted vulns. |

Merged PR: https://github.com/aelintra/sark65api_L12/pull/1 (`9e46114` on `main`)

- npm floors: vite ≥6.4.4, axios ≥1.20.0, postcss ≥8.5.23, laravel-vite-plugin ≥1.3.0; `package-lock.json` (`npm audit` **0**).
- `.github/dependabot.yml` weekly composer + npm; automated security updates on.
- `docs/installation.md` **Upgrading an existing install** expanded (appliance Composer-only; no npm on PBX; migrate/installer cautions).
- Local Mac checkout `/Users/jeffstokoe/GiT/sark65api_L12` pulled to `9e46114`.

Appliance apply after merge: `git pull` + `composer install --no-dev` under `/opt/sark65api_L12` (see that install doc). npm not required on the PBX.

## Decision — Asterisk TLS scanner noise vs Fail2Ban (do not revisit)

webo1001 `messages` shows intermittent `ast_iostream_start_tls` / `Unable to set up ssl connection with peer` ERRORs (e.g. `200.2.134.230`, `93.123.109.123`). These are internet scanners on open **8089** (WSS/TLS handshake fail / reset), not SIP auth.

**Accepted as expected noise.** Do **not** add a Fail2Ban jail for these lines: no credentials involved, false-positive risk on flaky clients, ban is after the cost is paid, and the existing `[asterisk]` jail already covers FailedAuth / wrong-password. Revisit only if volume becomes a real CPU/log load problem; prefer quieter logging or SG tightening then, not F2B-as-security.

## 6.5.0-24 — rtp.conf → sark_rtp.conf (done)

Omission: `sark_rtp.conf` was packaged/copied but never linked; stock Debian `rtp.conf` stayed active (STUN `0.0.0.0:0`).

Fix: `debian/postinst` + `srkEnsureAsteriskPlatform.sh` now move stock → `rtp.install.conf` and symlink `rtp.conf` → `sark_rtp.conf` (same pattern as modules/http/pjsip). Deb `sail_6.5.0-24_all.deb` on `main`.

Verified on `.135` and webo1001: symlink + live STUN; LE preserved on webo1001; media OK.

## Open / next

1. Smoke Directory contact add (empty org) + new tenant → LDAP OU on webo1001; OU backfill if needed.
2. Optional further WebRTC extensions.
3. ~~Optional: disable stock `certbot.timer`~~ **done** in **6.5.0-26** — see [2026-11-23-certbot-timer-mask-handoff.md](./2026-11-23-certbot-timer-mask-handoff.md); curly quote in WebRTC callerid generator.
4. Still open: `sark_includes` A/B/C; sail65-docs WebRTC page; deb prune policy; missing **6.5.0-16/17** debs if needed.
5. Optional: pull/merge API upgrade on lab `.135` `/opt/sark65api_L12` when convenient.
