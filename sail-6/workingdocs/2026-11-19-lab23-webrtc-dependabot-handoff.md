# Session handoff — 2026-11-19

Lab install of **6.5.0-23**, WebPhone/9005 unblock on webo1001, Java4Shell triage, and Dependabot/npm harden for **sark65api_L12**.

Prior: [2026-11-18-ldap-asterisk-tls-handoff.md](./2026-11-18-ldap-asterisk-tls-handoff.md).  
LDAP detail: [2026-11-17-ldap-contact-and-tenant-ou-fixes.md](./2026-11-17-ldap-contact-and-tenant-ou-fixes.md).

## Deploy / hosts

| Host | sail | Notes |
|------|------|--------|
| `192.168.1.135` (`ubu2204arm`) | **6.5.0-23** | Installed from Mac repo deb; upgrade safety check OK (see below). |
| `webo1001.terminator.cloud` | **6.5.0-22** | Still not on **-23**. WebRTC ext **9005** working after SPA overnight config change. |
| `192.168.1.130` (`adler`) | unchanged this session | — |

Branch remains **`feature/letsencrypt-apache-65018`** (not merged to `main`).

## Lab `.135` — 6.5.0-23 install

- Copied `sail_6.5.0-23_all.deb` → `dpkg -i` as `tech` (passwordless sudo).
- Pre/post compare: admin hash/salt + lastlogin, Cluster `ldapropwd`, globals (`PJSIP`, EXTLEN, AGENTSTART, LDAP*), table counts, `htpasswd`, console HMAC, Shorewall `rules`/`sark_rules`/`sark_inline_le`, `sark_customer_*.conf` — **all preserved**.
- ALWAYS seeds use `INSERT OR IGNORE` (admin not factory-reset). Once files all skipped.
- Expected churn only: `.sail-install-date` rewritten; **`sarkapi` Apache site re-enabled** (was absent from `sites-enabled` pre-upgrade; postinst re-links when API package tree present).
- Benign noise: `CREATE USER asterisk` already exists; `pjsip.conf` already a plain include file (symlink skip); helper `:7601` briefly down during generator; `sqlite_sequence` parse warning on create.
- LDAP fix files present (`srkLDAPHelperClass`, `sarkcluster` tenant OU create).

## webo1001 — WebRTC 9005

SPA/dev team overnight config change fixed the WebPhone WSS host bug (was opening `wss://dhbm8x.pbx3.com:8089/ws` instead of configured webo1001 proxy). User reports **9005 working OK**. Sail WSS path was already verified 18/11.

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

## Open / next

1. Deploy **6.5.0-23** to webo1001; smoke Directory contact add (empty org) + new tenant → LDAP OU; OU backfill if needed.
2. Optional further WebRTC extensions now that 9005 works.
3. Optional: disable stock `certbot.timer` (use SAIL `le-renew.sh` only); curly quote in WebRTC callerid generator.
4. Merge `feature/letsencrypt-apache-65018` to `main` after Brindley soak.
5. Still open: `sark_includes` A/B/C; `sark_rtp.conf` STUN; sail65-docs WebRTC page; deb prune policy; missing **6.5.0-16/17** debs if needed.
6. Optional: pull/merge API upgrade on lab `.135` `/opt/sark65api_L12` when convenient.
