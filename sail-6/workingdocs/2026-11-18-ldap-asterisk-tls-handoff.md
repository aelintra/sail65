# Session handoff — 2026-11-18

LDAP Directory fixes ported from sail6 into sail65, packaged as **6.5.0-23** on branch **`feature/letsencrypt-apache-65018`**. Also verified Asterisk TLS/WSS on Brindley EC2 (`webo1001`).

Detail for LDAP: [2026-11-17-ldap-contact-and-tenant-ou-fixes.md](./2026-11-17-ldap-contact-and-tenant-ou-fixes.md).  
LE background: [2026-11-17-letsencrypt-apache-handoff.md](./2026-11-17-letsencrypt-apache-handoff.md).

## Git / packages

| Item | Value |
|------|--------|
| Branch | `feature/letsencrypt-apache-65018` (not merged to `main`) |
| LDAP fix commit | `3cf2978` (cherry-pick from `fix/ldap-contact-and-tenant-ou` / `e037415`) |
| Release commit | `903c47e` (changelog **6.5.0-23**) |
| Debs in repo | `9dc08e5` added **6.5.0-18..23** (`sail_6.5.0-*.deb`). Still no **16** or **17** on disk/builder. |
| Builder | `.78` built `sail_6.5.0-23_all.deb`; tree synced from GitHub tarball at `903c47e` |

### Deb retention (provisional)

Keep shipping the latest `.deb` in git with each release so installed revisions stay fetchable. Historically all builds were kept; **16/17** are missing. Prune policy for older debs still TBD — until then, keep what is committed.

## LDAP (6.5.0-23)

Same three bugs as sail6; all were present on sail65 `main`/LE tip before the port:

1. `Add()` missing RDN `uid` on contact entries
2. Empty `o` / org empty-check typo (`phone` vs `org`) → LDAP Invalid syntax
3. New tenant never created `ou=<tenant>,ou=contacts`

Files: `srkLDAPHelperClass`, `sarkldap/view.php`, `sarkcluster/view.php`.

**Not deployed yet** to webo1001 (still on **6.5.0-21** as of session checks). Install with `sudo dpkg -i sail_6.5.0-23_all.deb` (same-version / upgrade). After deploy, backfill missing tenant OUs if needed (snippet in LDAP workingdoc).

## webo1001 Asterisk TLS / WSS (checked 18/11)

| Check | Result |
|-------|--------|
| SSH | `ubuntu` + `Documents/pemfiles/Brindley-Webo.pem` OK |
| Historical log | `transport-tls` cert missing/unreadable **17/11 afternoon** (before LE/apply settled) |
| Current certs | LE YE2 for `webo1001.terminator.cloud`; `asterisk` in `ssl-cert`; can read sark symlinks + LE privkey |
| Transports | `transport-tls :5061`, `transport-wss :8089` up; HTTPS `/httpstatus` 200 on `:8089` |
| Shorewall | `ACCEPT net $FW tcp 8089` (and iptables `44300,8089`) |
| AWS SG | **8089 open** (confirmed by ops this session; was pending in prior handoff) |

Scanner-style `Internal SSL error` from random peers is noise, not a config failure.

### Brindley EC2 (unchanged access)

| Field | Value |
|-------|--------|
| FQDN | `webo1001.terminator.cloud` |
| Elastic IP | `99.80.166.113` |
| SG | `launch-wizard-56` / `launch-wizard-56b` (metadata) |
| SSH | `ubuntu` + `Brindley-Webo.pem` (also try `tech`) |

## Deploy status

| Host | sail | Notes |
|------|------|--------|
| `192.168.1.135` lab | **6.5.0-19** | Unchanged this session |
| `webo1001.terminator.cloud` | **6.5.0-21** | Prod LE OK; WSS/TLS OK; **not yet on 6.5.0-23** |

## Open / next

1. Deploy **6.5.0-23** to webo1001; smoke Directory contact add (empty org) + new tenant → LDAP OU; OU backfill if imported tenants missing.
2. Create WebRTC extensions → Commit (SG/Shorewall/Asterisk TLS path ready).
3. Optional: disable stock `certbot.timer` (use SAIL `le-renew.sh` only).
4. Still open: `sark_includes` A/B/C; `sark_rtp.conf` STUN; sail65-docs WebRTC page; decide deb prune policy; merge feature branch when soak OK.
5. Optional: locate/rebuild missing **6.5.0-16** / **17** debs if anyone still needs them.
